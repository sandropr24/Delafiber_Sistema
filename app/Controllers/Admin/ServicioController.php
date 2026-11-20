<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrdenServicioModel;
use App\Models\HistorialOrdenServicioModel;
use App\Services\InventoryMovementService;
use App\Services\PermissionService;
use App\Services\ServicioConsultaService;
use App\Services\ServicioWorkflow;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class ServicioController extends BaseController
{
    private OrdenServicioModel $ordenes;
    private HistorialOrdenServicioModel $historial;
    private PermissionService $permisos;
    private ServicioConsultaService $consultas;
    private ServicioWorkflow $workflow;

    public function __construct()
    {
        $this->ordenes = model(OrdenServicioModel::class);
        $this->historial = model(HistorialOrdenServicioModel::class);
        $this->permisos = new PermissionService();
        $this->consultas = new ServicioConsultaService();
        $this->workflow = new ServicioWorkflow();
    }

    private function autorizado(string $permiso = 'servicios.ver'): bool
    {
        return $this->permisos->can((int) session()->get('idusuario'), $permiso);
    }

    private function denegar(): RedirectResponse
    {
        return redirect()->to(base_url('admin/dashboard'))->with('error', 'No tienes permiso para gestionar servicios técnicos.');
    }

    public function index()
    {
        if (!$this->autorizado()) return $this->denegar();
        $filtros = ['buscar' => trim((string) $this->request->getGet('buscar')), 'estado' => strtoupper(trim((string) $this->request->getGet('estado')))];
        $resultado = $this->consultas->listar($filtros, max(1, (int) $this->request->getGet('pagina')));
        return view('servicios/index', ['titulo' => 'Servicios Técnicos', 'ordenes' => $resultado['items'], 'resumen' => $this->consultas->resumenPorEstado(), 'paginacion' => $resultado, 'filtros' => $filtros, 'etiquetas' => ServicioWorkflow::ETIQUETAS, 'puedeGestionar' => $this->autorizado('servicios.gestionar')]);
    }

    public function crear()
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->denegar();
        $db = db_connect();
        $ventaSeleccionada = null;
        $serviciosVenta = [];
        $idVenta = (int) $this->request->getGet('venta');
        if ($idVenta > 0) {
            $ventaSeleccionada = $db->table('ventas')->select('idventa, idcliente, serie, numero, fechaanulacion')->where('idventa', $idVenta)->get()->getRowArray();
            if (!$ventaSeleccionada || $ventaSeleccionada['fechaanulacion'] !== null) {
                return redirect()->to(base_url('admin/servicios/crear'))->with('error', 'La venta seleccionada no existe o está anulada.');
            }
            $serviciosVenta = array_values(array_filter($this->consultas->serviciosDeVenta($idVenta), fn(array $servicio): bool => $this->cantidadCentimos((string) $servicio['pendiente'], true) > 0));
            if (!$serviciosVenta) return redirect()->to(base_url('admin/servicios/crear'))->with('error', 'Esta venta no contiene servicios para programar.');
        }
        $idClienteVenta = (int) ($ventaSeleccionada['idcliente'] ?? old('idcliente') ?? 0);
        $clientes = $db->query(
            "SELECT p.idpersona, p.telefono, p.direccion,
                    COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente
             FROM personas p
              WHERE p.estado = 1 AND p.idpersona = ? AND (NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.idpersona = p.idpersona)
                 OR p.idpersona = ?)
              LIMIT 1",
            [$idClienteVenta, $idClienteVenta]
        )->getResultArray();
        $servicios = $ventaSeleccionada ? $serviciosVenta : $db->table('productos p')->select('p.idproducto, p.nombre, p.idunidad, um.abreviatura')->join('unidadesmedida um', 'um.idunidad = p.idunidad')->where('p.tipo', 'SERVICIO')->where('p.estado', 1)->orderBy('p.nombre')->get()->getResultArray();
        $tecnicos = $db->table('tecnicos')->select('idtecnico, nombres, apellidos, especialidad')->where('estado', 'ACTIVO')->orderBy('nombres')->get()->getResultArray();
        $ventasDisponibles = $ventaSeleccionada ? [$ventaSeleccionada] : [];
        return view('servicios/crear', compact('clientes', 'servicios', 'tecnicos', 'ventasDisponibles', 'ventaSeleccionada') + ['titulo' => 'Nueva orden de servicio']);
    }

    public function buscarClientes()
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        $buscar = trim((string) $this->request->getGet('q'));
        if (mb_strlen($buscar) < 2) return $this->response->setJSON([]);
        $db = db_connect();
        $constructor = $db->table('personas p')
            ->select("p.idpersona, p.telefono, p.direccion, p.numerodoc, COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente", false)
            ->where('p.estado', 1)
            ->where('NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.idpersona = p.idpersona)', null, false)
            ->groupStart()->like('p.nombres', $buscar)->orLike('p.apellidos', $buscar)->orLike('p.razonsocial', $buscar)->orLike('p.numerodoc', $buscar)->groupEnd()
            ->orderBy('cliente')->limit(20);
        return $this->response->setJSON(array_map(static fn(array $fila): array => [
            'id' => (int) $fila['idpersona'],
            'texto' => trim($fila['cliente'] . ' · ' . $fila['numerodoc']),
            'direccion' => $fila['direccion'] ?? '',
            'telefono' => $fila['telefono'] ?? '',
        ], $constructor->get()->getResultArray()));
    }

    public function buscarVentas()
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        $buscar = trim((string) $this->request->getGet('q'));
        if (mb_strlen($buscar) > 100) return $this->response->setStatusCode(400)->setJSON(['error' => 'La búsqueda es demasiado extensa.']);
        if ($buscar !== '' && !ctype_digit($buscar) && mb_strlen($buscar) < 2) return $this->response->setJSON([]);
        return $this->response->setJSON($this->consultas->buscarVentas($buscar));
    }

    public function buscarMateriales()
    {
        if (!$this->autorizado('inventario.consumir')) return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        $buscar = trim((string) $this->request->getGet('q'));
        if (mb_strlen($buscar) < 2) return $this->response->setJSON([]);
        $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        $filas = db_connect()->query("SELECT k.idkardex, k.idlocal, k.stockactual, p.nombre AS producto, l.nombrelocal,
            COALESCE((SELECT SUM(r.cantidad) FROM reservas_stock r WHERE r.idkardex = k.idkardex AND r.estado = 'RESERVADO'), 0) AS reservado
            FROM kardex k JOIN productos p ON p.idproducto = k.idproducto JOIN locales l ON l.idlocal = k.idlocal
            JOIN usuario_local ul ON ul.idlocal = k.idlocal AND ul.idusuario = ?
            WHERE p.tipo = 'PRODUCTO' AND p.controlastock = 1 AND p.controlaserie = 0 AND p.estado = 1
              AND (p.nombre LIKE ? ESCAPE '!' OR l.nombrelocal LIKE ? ESCAPE '!')
            ORDER BY p.nombre, l.nombrelocal LIMIT 20", [(int) session('idusuario'), $patron, $patron])->getResultArray();
        $resultado = [];
        foreach ($filas as $fila) {
            $disponible = round((float) $fila['stockactual'] - (float) $fila['reservado'], 2);
            if ($disponible <= 0) continue;
            $resultado[] = ['id' => (int) $fila['idkardex'], 'texto' => $fila['producto'] . ' · ' . $fila['nombrelocal'], 'disponible' => number_format($disponible, 2, '.', '')];
        }
        return $this->response->setJSON($resultado);
    }

    private function fechaFormulario(?string $texto): ?string
    {
        if (!$texto) return null;
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $texto);
        if (!$fecha || $fecha->format('Y-m-d\TH:i') !== $texto) throw new RuntimeException('La fecha y hora seleccionadas no son válidas.');
        return $fecha->format('Y-m-d H:i:s');
    }

    private function cantidadCentimos(string $cantidad, bool $permitirCero = false): int
    {
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', trim($cantidad), $partes)) {
            throw new RuntimeException('Ingresa una cantidad válida de hasta dos decimales.');
        }
        $valor = ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
        if (!$permitirCero && $valor < 1) throw new RuntimeException('La cantidad debe ser mayor que cero.');
        return $valor;
    }

    private function cantidadDecimal(int $centimos): string
    {
        return intdiv($centimos, 100) . '.' . str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }

    private function disponibilidad(int $idTecnico, string $inicio, string $fin, int $excluir = 0): void
    {
        $db = db_connect();
        $consulta = $db->query('SELECT idtecnico, estado FROM tecnicos WHERE idtecnico = ? FOR UPDATE', [$idTecnico])->getRowArray();
        if (!$consulta || $consulta['estado'] !== 'ACTIVO') throw new RuntimeException('El técnico seleccionado no está disponible.');
        $ocupado = $db->query("SELECT idorden FROM ordenes_servicio WHERE idtecnico = ? AND idorden <> ? AND estado IN ('PROGRAMADO', 'EN_PROCESO') AND fecha_inicio < ? AND fecha_fin > ? LIMIT 1 FOR UPDATE", [$idTecnico, $excluir, $fin, $inicio])->getRowArray();
        if ($ocupado) throw new RuntimeException('El técnico ya tiene una orden asignada dentro de ese horario.');
    }

    public function guardar(): RedirectResponse
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->denegar();
        $datos = [
            'idcliente' => (int) $this->request->getPost('idcliente'),
            'idservicio' => (int) $this->request->getPost('idservicio'),
            'idtecnico' => (int) $this->request->getPost('idtecnico'),
            'titulo' => trim((string) $this->request->getPost('titulo')),
            'descripcion' => trim((string) $this->request->getPost('descripcion')),
            'direccion' => trim((string) $this->request->getPost('direccion')),
            'telefono' => trim((string) $this->request->getPost('telefono')),
            'observaciones' => trim((string) $this->request->getPost('observaciones'))
        ];
        if (!$this->validateData($datos, [
            'idcliente' => 'required|integer|greater_than[0]',
            'idservicio' => 'permit_empty|integer',
            'idtecnico' => 'permit_empty|integer',
            'titulo' => 'required|max_length[150]',
            'descripcion' => 'permit_empty|max_length[5000]',
            'direccion' => 'permit_empty|max_length[255]',
            'telefono' => 'permit_empty|max_length[20]',
            'observaciones' => 'permit_empty|max_length[5000]'
        ])) return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));

        $db = db_connect();
        $idVenta = (int) $this->request->getPost('idventa');
        $cantidadSolicitada = trim((string) $this->request->getPost('cantidad_servicio')) ?: '1';
        // Las órdenes independientes usan clientes del catálogo. En órdenes vinculadas, se valida contra la venta bloqueada más abajo.
        if ($idVenta <= 0 && !$db->query('SELECT p.idpersona FROM personas p WHERE p.idpersona = ? AND p.estado = 1 AND NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.idpersona = p.idpersona) LIMIT 1', [$datos['idcliente']])->getRowArray()) return redirect()->back()->withInput()->with('error', 'Selecciona un cliente activo válido.');
        if (!$idVenta && $datos['idservicio'] && !$db->table('productos')->where('idproducto', $datos['idservicio'])->where('tipo', 'SERVICIO')->where('estado', 1)->countAllResults()) return redirect()->back()->withInput()->with('error', 'El servicio elegido no está disponible.');

        $transOpen = false;
        try {
            $cantidadCentimos = $this->cantidadCentimos($cantidadSolicitada);
            $inicio = $this->fechaFormulario($this->request->getPost('fecha_inicio'));
            $fin = $this->fechaFormulario($this->request->getPost('fecha_fin'));
            $asignada = $datos['idtecnico'] > 0 || $inicio !== null || $fin !== null;
            if ($asignada && (!$datos['idtecnico'] || !$inicio || !$fin)) throw new RuntimeException('Para programar debes seleccionar técnico, inicio y fin.');
            if ($asignada && ($inicio >= $fin || $inicio < date('Y-m-d H:i:s'))) throw new RuntimeException('El horario debe ser futuro y la hora de fin posterior al inicio.');
            $db->transBegin();
            $transOpen = true;
            $servicioElegido = null;
            if ($idVenta > 0) {
                // Bloquear la venta evita duplicados por clics o solicitudes concurrentes.
                $venta = $db->query('SELECT idventa, idcliente, fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE', [$idVenta])->getRowArray();
                if (!$venta || $venta['fechaanulacion'] !== null) throw new RuntimeException('La venta ya no está disponible.');
                if ((int) $venta['idcliente'] !== $datos['idcliente']) throw new RuntimeException('El cliente no coincide con el de la venta.');
                foreach ((new ServicioConsultaService($db))->serviciosDeVenta($idVenta) as $servicio) {
                    if ((int) $servicio['idproducto'] === $datos['idservicio']) $servicioElegido = $servicio;
                }
                if (!$servicioElegido) throw new RuntimeException('Este servicio no está incluido en la venta.');
                if ($cantidadCentimos > $this->cantidadCentimos((string) $servicioElegido['pendiente'], true)) {
                    throw new RuntimeException('La cantidad supera lo pendiente de programar (' . $servicioElegido['pendiente'] . ' ' . $servicioElegido['abreviatura'] . ').');
                }
            } elseif ($datos['idservicio']) {
                $servicioElegido = $db->table('productos p')->select('p.idunidad, um.abreviatura')->join('unidadesmedida um', 'um.idunidad = p.idunidad')
                    ->where('p.idproducto', $datos['idservicio'])->where('p.tipo', 'SERVICIO')->where('p.estado', 1)->get()->getRowArray();
            }
            if ($asignada) $this->disponibilidad($datos['idtecnico'], $inicio, $fin);
            $id = $this->ordenes->insert([
                'idcliente' => $datos['idcliente'],
                'idventa' => $idVenta ?: null,
                'idservicio' => $datos['idservicio'] ?: null,
                'cantidad_servicio' => $this->cantidadDecimal($cantidadCentimos),
                'idunidad_servicio' => $servicioElegido['idunidad'] ?? null,
                'unidad_servicio_snapshot' => $servicioElegido['abreviatura'] ?? 'SERV',
                'idtecnico' => $datos['idtecnico'] ?: null,
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'] ?: null,
                'direccion' => $datos['direccion'] ?: null,
                'telefono' => $datos['telefono'] ?: null,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado' => $asignada ? 'PROGRAMADO' : 'PENDIENTE',
                'observaciones' => $datos['observaciones'] ?: null,
                'idusuario_crea' => (int) session('idusuario')
            ], true);
            if (!$id) throw new RuntimeException('No se pudo registrar la orden de servicio.');
            if (!$this->historial->insert([
                'idorden' => $id,
                'tipoevento' => 'CREACION',
                'estado_anterior' => null,
                'estado_nuevo' => $asignada ? 'PROGRAMADO' : 'PENDIENTE',
                'observacion' => $idVenta ? 'Orden creada desde la venta N.º ' . $idVenta . '.' : 'Registro inicial de la orden.',
                'idusuario' => (int) session('idusuario'),
                'fecha' => date('Y-m-d H:i:s')
            ])) throw new RuntimeException('No se pudo registrar el historial.');
            if ($db->transStatus() === false) throw new RuntimeException('No se pudieron guardar los cambios.');
            $db->transCommit();
            $transOpen = false;
            return redirect()->to(base_url('admin/servicios/detalles/' . $id))->with('mensaje', 'Orden de servicio registrada correctamente.');
        } catch (Throwable $e) {
            if ($transOpen) $db->transRollback();
            log_message('error', '[Servicios] Error al registrar: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo registrar la orden. Revisa el registro de errores.');
        }
    }

    public function detalles(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        $db = db_connect();
        $orden = $db->table('ordenes_servicio os')
            ->select("os.*, COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente, CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, '')) AS tecnico, pr.nombre AS servicio", false)
            ->join('personas p', 'p.idpersona = os.idcliente')
            ->join('tecnicos t', 't.idtecnico = os.idtecnico', 'left')
            ->join('productos pr', 'pr.idproducto = os.idservicio', 'left')->where('os.idorden', $id)->get()->getRowArray();
        if (!$orden) return redirect()->to(base_url('admin/servicios'))->with('error', 'La orden solicitada no existe.');
        $historial = $db->table('historial_orden_servicio h')->select('h.*, u.nombreusuario')->join('usuarios u', 'u.idusuario = h.idusuario')->where('h.idorden', $id)->orderBy('h.idhistorial', 'DESC')->get()->getResultArray();
        $tecnicos = $db->table('tecnicos')->select('idtecnico, nombres, apellidos')->where('estado', 'ACTIVO')->orderBy('nombres')->get()->getResultArray();
        $materiales = $db->table('consumos_servicio cs')->select('cs.*, k.idproducto, k.idlocal, p.nombre AS producto, l.nombrelocal, u.nombreusuario, ac.idanulacionconsumo, ac.fecha AS fecha_anulacion, ac.motivo AS motivo_anulacion, ac.idmovimiento AS idmovimiento_anulacion')
            ->join('kardex k', 'k.idkardex = cs.idkardex')->join('productos p', 'p.idproducto = k.idproducto')
            ->join('locales l', 'l.idlocal = k.idlocal')->join('usuarios u', 'u.idusuario = cs.idusuario')
            ->join('anulaciones_consumo_servicio ac', 'ac.idconsumo = cs.idconsumo', 'left')
            ->where('cs.idorden', $id)->orderBy('cs.idconsumo', 'DESC')->get()->getResultArray();
        $tokensAnulacion = [];
        foreach ($materiales as $material) {
            if (empty($material['idanulacionconsumo'])) {
                $tokensAnulacion[(int) $material['idconsumo']] = bin2hex(random_bytes(16));
            }
        }
        return view('servicios/detalles', [
            'titulo' => 'Orden de servicio',
            'orden' => $orden,
            'historial' => $historial,
            'tecnicos' => $tecnicos,
            'materiales' => $materiales,
            'tokenConsumo' => bin2hex(random_bytes(16)),
            'tokensAnulacion' => $tokensAnulacion,
            'puedeGestionar' => $this->autorizado('servicios.gestionar'),
            'puedeConsumir' => $this->autorizado('inventario.consumir'),
            'puedeAnularConsumo' => $this->autorizado('inventario.anular_consumo'),
            'puedeVerVenta' => in_array((string) session('rol'), ['Administrador', 'Vendedor'], true),
            'etiquetas' => ServicioWorkflow::ETIQUETAS,
        ]);
    }

    public function agenda()
    {
        if (!$this->autorizado()) return $this->denegar();
        $hoy = new DateTimeImmutable('today');
        $filtros = [
            'desde' => trim((string) $this->request->getGet('desde')) ?: $hoy->modify('-7 days')->format('Y-m-d'),
            'hasta' => trim((string) $this->request->getGet('hasta')) ?: $hoy->modify('+60 days')->format('Y-m-d'),
            'estado' => strtoupper(trim((string) $this->request->getGet('estado'))),
            'tecnico' => max(0, (int) $this->request->getGet('tecnico')),
        ];
        try {
            $desde = DateTimeImmutable::createFromFormat('!Y-m-d', $filtros['desde']);
            $hasta = DateTimeImmutable::createFromFormat('!Y-m-d', $filtros['hasta']);
            if (!$desde || $desde->format('Y-m-d') !== $filtros['desde'] || !$hasta || $hasta->format('Y-m-d') !== $filtros['hasta']) {
                throw new RuntimeException('Selecciona un rango de fechas válido.');
            }
            if ($desde > $hasta) throw new RuntimeException('La fecha inicial no puede ser posterior a la fecha final.');
            if ($desde->diff($hasta)->days > 180) throw new RuntimeException('El rango de la agenda no puede superar 180 días.');
            if (!in_array($filtros['estado'], ['', ServicioWorkflow::PROGRAMADO, ServicioWorkflow::EN_PROCESO], true)) {
                throw new RuntimeException('El estado seleccionado no es válido para la agenda.');
            }
        } catch (RuntimeException $e) {
            return redirect()->to(base_url('admin/servicios/agenda'))->with('error', $e->getMessage());
        }
        $resultado = $this->consultas->agenda($filtros);
        $tecnicos = db_connect()->table('tecnicos')->select('idtecnico, nombres, apellidos')->where('estado', 'ACTIVO')->orderBy('nombres')->get()->getResultArray();
        return view('servicios/agenda', [
            'titulo' => 'Agenda de Servicios',
            'ordenes' => $resultado['items'],
            'agendaTruncada' => $resultado['truncada'],
            'filtros' => $filtros,
            'tecnicos' => $tecnicos,
            'etiquetas' => ServicioWorkflow::ETIQUETAS,
            'puedeGestionar' => $this->autorizado('servicios.gestionar'),
        ]);
    }

    public function reprogramar(int $id): RedirectResponse
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->denegar();
        $db = db_connect();
        $transOpen = false;
        try {
            $idTecnico = (int) $this->request->getPost('idtecnico');
            $inicio = $this->fechaFormulario($this->request->getPost('fecha_inicio'));
            $fin = $this->fechaFormulario($this->request->getPost('fecha_fin'));
            $nota = trim((string) $this->request->getPost('observacion'));
            if (!$idTecnico || !$inicio || !$fin || $inicio >= $fin || $inicio < date('Y-m-d H:i:s')) throw new RuntimeException('Selecciona un técnico y un horario futuro válido.');
            if (mb_strlen($nota) > 255) throw new RuntimeException('La observación no puede superar 255 caracteres.');
            $db->transBegin();
            $transOpen = true;
            $orden = $db->query('SELECT idorden, estado, idtecnico FROM ordenes_servicio WHERE idorden = ? FOR UPDATE', [$id])->getRowArray();
            if (!$orden || !in_array($orden['estado'], ['PENDIENTE', 'PROGRAMADO'], true)) throw new RuntimeException('Esta orden ya no admite programación.');
            $this->disponibilidad($idTecnico, $inicio, $fin, $id);
            if (!$this->ordenes->update($id, ['idtecnico' => $idTecnico, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'estado' => 'PROGRAMADO'])) throw new RuntimeException('No se pudo programar la orden.');
            if (!$this->historial->insert(['idorden' => $id, 'tipoevento' => 'REPROGRAMACION', 'estado_anterior' => $orden['estado'], 'estado_nuevo' => 'PROGRAMADO', 'observacion' => $nota ?: 'Se actualizó el técnico o el horario.', 'idusuario' => (int) session('idusuario'), 'fecha' => date('Y-m-d H:i:s')])) throw new RuntimeException('No se pudo registrar el historial.');
            if ($db->transStatus() === false) throw new RuntimeException('No se pudieron guardar los cambios.');
            $db->transCommit();
            $transOpen = false;
            return redirect()->to(base_url('admin/servicios/detalles/' . $id))->with('mensaje', 'Programación actualizada correctamente.');
        } catch (Throwable $e) {
            if ($transOpen) $db->transRollback();
            log_message('error', '[Servicios] Error al reprogramar: ' . $e->getMessage());
            return redirect()->to(base_url('admin/servicios/detalles/' . $id))->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo reprogramar la orden.');
        }
    }

    public function cambiarEstado(int $id): RedirectResponse
    {
        if (!$this->autorizado('servicios.gestionar')) return $this->denegar();
        $nuevo = strtoupper(trim((string) $this->request->getPost('estado')));
        $nota = trim((string) $this->request->getPost('observacion'));
        $resultado = trim((string) $this->request->getPost('resultado'));
        $db = db_connect();
        $transOpen = false;
        try {
            if (mb_strlen($nota) > 255 || mb_strlen($resultado) > 5000) throw new RuntimeException('La observación o el resultado superan la longitud permitida.');
            $db->transBegin();
            $transOpen = true;
            $orden = $db->query('SELECT idorden, estado, fecha_inicio, fecha_fin FROM ordenes_servicio WHERE idorden = ? FOR UPDATE', [$id])->getRowArray();
            if (!$orden) throw new RuntimeException('La orden no existe.');
            $this->workflow->validarTransicion($orden['estado'], $nuevo, $nuevo === 'FINALIZADO' ? $resultado : $nota);
            if ($nuevo === 'EN_PROCESO' && !empty($orden['fecha_inicio']) && $orden['fecha_inicio'] > date('Y-m-d H:i:s') && mb_strlen($nota) < 10) {
                throw new RuntimeException('El trabajo está programado para una fecha futura. Si va a iniciar antes, escribe el motivo (mínimo 10 caracteres).');
            }
            $datos = ['estado' => $nuevo];
            if ($nuevo === 'FINALIZADO') {
                $datos['resultado'] = $resultado;
                $datos['fecha_finalizacion'] = date('Y-m-d H:i:s');
            }
            if (!$this->ordenes->update($id, $datos)) throw new RuntimeException('No se pudo modificar el estado.');
            if (!$this->historial->insert(['idorden' => $id, 'tipoevento' => 'CAMBIO_ESTADO', 'estado_anterior' => $orden['estado'], 'estado_nuevo' => $nuevo, 'observacion' => $nota ?: ($nuevo === 'FINALIZADO' ? 'Servicio finalizado con resultado técnico registrado.' : 'Actualización del estado de la orden.'), 'idusuario' => (int) session('idusuario'), 'fecha' => date('Y-m-d H:i:s')])) throw new RuntimeException('No se pudo registrar el historial.');
            if ($db->transStatus() === false) throw new RuntimeException('No se pudieron guardar los cambios.');
            $db->transCommit();
            $transOpen = false;
            return redirect()->to(base_url('admin/servicios/detalles/' . $id))->with('mensaje', 'Estado actualizado correctamente.');
        } catch (Throwable $e) {
            if ($transOpen) $db->transRollback();
            log_message('error', '[Servicios] Error al cambiar estado: ' . $e->getMessage());
            return redirect()->to(base_url('admin/servicios/detalles/' . $id))->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo cambiar el estado.');
        }
    }

    public function registrarMaterial(int $id): RedirectResponse
    {
        if (!$this->autorizado('inventario.consumir')) return $this->denegar();
        $destino = base_url('admin/servicios/detalles/' . $id);
        $idKardex = (int) $this->request->getPost('idkardex');
        $cantidadTexto = trim((string) $this->request->getPost('cantidad'));
        $nota = trim((string) $this->request->getPost('observacion'));
        $token = trim((string) $this->request->getPost('token_operacion'));
        $idUsuario = (int) session('idusuario');
        $db = db_connect();
        $transOpen = false;

        try {
            if ($id < 1 || $idKardex < 1) {
                throw new RuntimeException('Selecciona un material e ingresa una cantidad válida de hasta dos decimales.');
            }
            $cantidadCentimos = $this->cantidadCentimos($cantidadTexto);
            if (mb_strlen($nota) > 255 || !preg_match('/^[a-f0-9]{32}$/D', $token)) {
                throw new RuntimeException('La cantidad, observación o solicitud no es válida.');
            }
            $db->transBegin();
            $transOpen = true;
            $orden = $db->query('SELECT idorden, estado FROM ordenes_servicio WHERE idorden = ? FOR UPDATE', [$id])->getRowArray();
            if (!$orden || $orden['estado'] !== 'EN_PROCESO') throw new RuntimeException('Solo puedes registrar consumos cuando la orden está EN PROCESO.');
            if ($db->table('consumos_servicio')->where('token_operacion', $token)->countAllResults() > 0) {
                throw new RuntimeException('Este consumo ya se procesó. Actualiza la página para registrar otro.');
            }
            $kardex = $db->query('SELECT idlocal FROM kardex WHERE idkardex = ? FOR UPDATE', [$idKardex])->getRowArray();
            if (!$kardex || !$this->permisos->canUseLocation($idUsuario, (int) $kardex['idlocal'])) {
                throw new RuntimeException('No tienes permiso para consumir inventario de este local.');
            }
            $ahora = date('Y-m-d H:i:s');
            $inventario = new InventoryMovementService($db);
            $movimiento = $inventario->consume(
                $idKardex,
                $this->cantidadDecimal($cantidadCentimos),
                $idUsuario,
                'Material usado en orden OS-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
                'Consumo servicio técnico'
            );
            if (!$db->table('consumos_servicio')->insert([
                'idorden' => $id,
                'idkardex' => $idKardex,
                'idmovimiento' => $movimiento['idmovimiento'],
                'cantidad' => $movimiento['cantidad'],
                'observacion' => $nota ?: null,
                'token_operacion' => $token,
                'idusuario' => $idUsuario,
                'fecha' => $ahora
            ])) throw new RuntimeException('No se pudo guardar el consumo del servicio.');
            if (!$this->historial->insert([
                'idorden' => $id,
                'tipoevento' => 'CONSUMO_MATERIAL',
                'estado_anterior' => $orden['estado'],
                'estado_nuevo' => $orden['estado'],
                'observacion' => mb_substr('Material: ' . $movimiento['kardex']['nombre'] . ', cantidad ' . $movimiento['cantidad'] . '. ' . $nota, 0, 255),
                'idusuario' => $idUsuario,
                'fecha' => $ahora
            ])) throw new RuntimeException('No se pudo registrar el historial del consumo.');
            if ($db->transStatus() === false) throw new RuntimeException('No se completó la transacción.');
            $db->transCommit();
            $transOpen = false;
            return redirect()->to($destino)->with('mensaje', 'Material consumido y salida de Kardex registrada correctamente.');
        } catch (Throwable $e) {
            if ($transOpen) $db->transRollback();
            log_message('error', '[Servicios] Consumo orden ' . $id . ': ' . $e->getMessage());
            return redirect()->to($destino)->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo registrar el consumo.');
        }
    }

    public function anularMaterial(int $id, int $idConsumo): RedirectResponse
    {
        if (!$this->autorizado('inventario.anular_consumo')) return $this->denegar();
        $destino = base_url('admin/servicios/detalles/' . $id);
        $motivo = trim((string) $this->request->getPost('motivo'));
        $token = trim((string) $this->request->getPost('token_operacion'));
        $idUsuario = (int) session('idusuario');
        $db = db_connect();
        $transOpen = false;

        try {
            if ($id < 1 || $idConsumo < 1 || mb_strlen($motivo) < 5 || mb_strlen($motivo) > 255 || !preg_match('/^[a-f0-9]{32}$/D', $token)) {
                throw new RuntimeException('Indica un motivo válido de al menos 5 caracteres.');
            }
            $db->transBegin();
            $transOpen = true;
            $consumo = $db->query('SELECT cs.*, k.idlocal, p.nombre AS producto, os.estado FROM consumos_servicio cs JOIN kardex k ON k.idkardex = cs.idkardex JOIN productos p ON p.idproducto = k.idproducto JOIN ordenes_servicio os ON os.idorden = cs.idorden WHERE cs.idconsumo = ? AND cs.idorden = ? FOR UPDATE', [$idConsumo, $id])->getRowArray();
            if (!$consumo) throw new RuntimeException('El consumo no existe en esta orden.');
            if ($consumo['estado'] !== 'EN_PROCESO') throw new RuntimeException('Solo puedes anular consumos mientras la orden está EN PROCESO.');
            if (!$this->permisos->canUseLocation($idUsuario, (int) $consumo['idlocal'])) throw new RuntimeException('No tienes acceso al local del consumo.');
            if ($db->table('anulaciones_consumo_servicio')->where('idconsumo', $idConsumo)->countAllResults()) throw new RuntimeException('Este consumo ya fue anulado.');
            if ($db->table('anulaciones_consumo_servicio')->where('token_operacion', $token)->countAllResults()) throw new RuntimeException('Esta anulación ya fue procesada.');

            $movimiento = (new InventoryMovementService($db))->reverse((int) $consumo['idmovimiento'], $idUsuario, $motivo);
            $ahora = date('Y-m-d H:i:s');
            if (!$db->table('anulaciones_consumo_servicio')->insert([
                'idconsumo' => $idConsumo,
                'idmovimiento' => $movimiento['idmovimiento'],
                'cantidad' => $movimiento['cantidad'],
                'motivo' => $motivo,
                'token_operacion' => $token,
                'idusuario' => $idUsuario,
                'fecha' => $ahora,
            ])) throw new RuntimeException('No se pudo registrar la anulación del consumo.');
            if (!$this->historial->insert([
                'idorden' => $id,
                'tipoevento' => 'ANULACION_CONSUMO',
                'estado_anterior' => $consumo['estado'],
                'estado_nuevo' => $consumo['estado'],
                'observacion' => mb_substr('Se anuló el consumo de ' . $consumo['producto'] . ' por ' . $movimiento['cantidad'] . '. Motivo: ' . $motivo, 0, 255),
                'idusuario' => $idUsuario,
                'fecha' => $ahora,
            ])) throw new RuntimeException('No se pudo registrar el historial de la anulación.');
            if ($db->transStatus() === false) throw new RuntimeException('No se completó la transacción.');
            $db->transCommit();
            $transOpen = false;
            return redirect()->to($destino)->with('mensaje', 'Consumo anulado y material devuelto al stock.');
        } catch (Throwable $e) {
            if ($transOpen) $db->transRollback();
            log_message('error', '[Servicios] Anulación consumo ' . $idConsumo . ': ' . $e->getMessage());
            return redirect()->to($destino)->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo anular el consumo.');
        }
    }
}
