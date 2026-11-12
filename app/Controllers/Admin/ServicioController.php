<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrdenServicioModel;
use App\Models\HistorialOrdenServicioModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class ServicioController extends BaseController
{
    private OrdenServicioModel $ordenes;
    private HistorialOrdenServicioModel $historial;

    public function __construct()
    {
        $this->ordenes = model(OrdenServicioModel::class);
        $this->historial = model(HistorialOrdenServicioModel::class);
    }

    private function autorizado(): bool
    {
        return in_array(session()->get('rol'), ['Administrador', 'Operador'], true)
            && (int) session()->get('idusuario') > 0;
    }

    private function denegar(): RedirectResponse
    {
        return redirect()->to(base_url('admin/dashboard'))->with('error', 'No tienes permiso para gestionar servicios técnicos.');
    }

    private function consultarOrdenes(bool $soloAgenda = false): array
    {
        $db = db_connect();
        $consulta = $db->table('ordenes_servicio os')
            ->select("os.*, COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente, CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, '')) AS tecnico, pr.nombre AS servicio", false)
            ->join('personas p', 'p.idpersona = os.idcliente')
            ->join('tecnicos t', 't.idtecnico = os.idtecnico', 'left')
            ->join('productos pr', 'pr.idproducto = os.idservicio', 'left');

        if ($soloAgenda) {
            $consulta->whereIn('os.estado', ['PROGRAMADO', 'EN_PROCESO'])
                ->where('os.fecha_inicio >=', date('Y-m-d 00:00:00', strtotime('-7 days')))
                ->where('os.fecha_inicio <=', date('Y-m-d 23:59:59', strtotime('+60 days')))
                ->orderBy('os.fecha_inicio', 'ASC');
        } else {
            $consulta->orderBy('os.idorden', 'DESC');
        }
        return $consulta->limit(300)->get()->getResultArray();
    }

    public function index()
    {
        if (!$this->autorizado()) return $this->denegar();
        $db = db_connect();
        $resumen = ['PENDIENTE' => 0, 'PROGRAMADO' => 0, 'EN_PROCESO' => 0, 'FINALIZADO' => 0, 'CANCELADO' => 0];
        foreach ($db->table('ordenes_servicio')->select('estado, COUNT(*) AS cantidad')->groupBy('estado')->get()->getResultArray() as $fila) {
            $resumen[$fila['estado']] = (int) $fila['cantidad'];
        }
        return view('servicios/index', ['titulo' => 'Servicios Técnicos', 'ordenes' => $this->consultarOrdenes(), 'resumen' => $resumen]);
    }

    private function serviciosDeVenta(int $idVenta): array
    {
        $db = db_connect();
        $servicios = [];
        $directos = $db->table('detventa d')
            ->select('p.idproducto, p.nombre')->join('productos p', 'p.idproducto = d.idproducto')
            ->where('d.idventa', $idVenta)->where('p.tipo', 'SERVICIO')->get()->getResultArray();
        foreach ($directos as $s) $servicios[(int) $s['idproducto']] = $s;

        if ($db->tableExists('venta_combo_componentes')) {
            $combos = $db->table('venta_combo_componentes c')
                ->select('p.idproducto, p.nombre')->join('detventa d', 'd.iddetventa = c.iddetventa')
                ->join('productos p', 'p.idproducto = c.idproducto')
                ->where('d.idventa', $idVenta)->where('p.tipo', 'SERVICIO')->get()->getResultArray();
            foreach ($combos as $s) $servicios[(int) $s['idproducto']] = $s;
        }
        return array_values($servicios);
    }

    private function ventasConServicios(): array
    {
        $db = db_connect();
        $condiciones = ["EXISTS (SELECT 1 FROM detventa d INNER JOIN productos p ON p.idproducto = d.idproducto WHERE d.idventa = v.idventa AND p.tipo = 'SERVICIO')"];
        if ($db->tableExists('venta_combo_componentes')) {
            $condiciones[] = "EXISTS (SELECT 1 FROM detventa d INNER JOIN venta_combo_componentes c ON c.iddetventa = d.iddetventa INNER JOIN productos p ON p.idproducto = c.idproducto WHERE d.idventa = v.idventa AND p.tipo = 'SERVICIO')";
        }
        $sql = 'SELECT v.idventa, v.idcliente, v.serie, v.numero, v.fecha FROM ventas v WHERE v.fechaanulacion IS NULL AND (' . implode(' OR ', $condiciones) . ') ORDER BY v.idventa DESC LIMIT 150';
        return $db->query($sql)->getResultArray();
    }

    public function crear()
    {
        if (!$this->autorizado()) return $this->denegar();
        $db = db_connect();
        $ventaSeleccionada = null;
        $serviciosVenta = [];
        $idVenta = (int) $this->request->getGet('venta');
        if ($idVenta > 0) {
            $ventaSeleccionada = $db->table('ventas')->select('idventa, idcliente, serie, numero, fechaanulacion')->where('idventa', $idVenta)->get()->getRowArray();
            if (!$ventaSeleccionada || $ventaSeleccionada['fechaanulacion'] !== null) {
                return redirect()->to(base_url('admin/servicios/crear'))->with('error', 'La venta seleccionada no existe o está anulada.');
            }
            $serviciosVenta = $this->serviciosDeVenta($idVenta);
            if (!$serviciosVenta) return redirect()->to(base_url('admin/servicios/crear'))->with('error', 'Esta venta no contiene servicios para programar.');
        }
        $idClienteVenta = (int) ($ventaSeleccionada['idcliente'] ?? 0);
        $clientes = $db->query(
            "SELECT p.idpersona, p.telefono, p.direccion,
                    COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente
             FROM personas p
             WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.idpersona = p.idpersona)
                OR p.idpersona = ?
             ORDER BY (p.idpersona = ?) DESC, cliente LIMIT 1000",
            [$idClienteVenta, $idClienteVenta]
        )->getResultArray();
        $servicios = $ventaSeleccionada ? $serviciosVenta : $db->table('productos')->select('idproducto, nombre')->where('tipo', 'SERVICIO')->where('estado', 1)->orderBy('nombre')->get()->getResultArray();
        $tecnicos = $db->table('tecnicos')->select('idtecnico, nombres, apellidos, especialidad')->where('estado', 'ACTIVO')->orderBy('nombres')->get()->getResultArray();
        $ventasDisponibles = $this->ventasConServicios();
        return view('servicios/crear', compact('clientes', 'servicios', 'tecnicos', 'ventasDisponibles', 'ventaSeleccionada') + ['titulo' => 'Nueva orden de servicio']);
    }

    private function fechaFormulario(?string $texto): ?string
    {
        if (!$texto) return null;
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $texto);
        if (!$fecha || $fecha->format('Y-m-d\TH:i') !== $texto) throw new RuntimeException('La fecha y hora seleccionadas no son válidas.');
        return $fecha->format('Y-m-d H:i:s');
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
        if (!$this->autorizado()) return $this->denegar();
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
        // Las órdenes independientes usan clientes del catálogo. En órdenes vinculadas, se valida contra la venta bloqueada más abajo.
        if ($idVenta <= 0 && !$db->query('SELECT p.idpersona FROM personas p WHERE p.idpersona = ? AND NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.idpersona = p.idpersona) LIMIT 1', [$datos['idcliente']])->getRowArray()) return redirect()->back()->withInput()->with('error', 'Selecciona un cliente válido.');
        if (!$idVenta && $datos['idservicio'] && !$db->table('productos')->where('idproducto', $datos['idservicio'])->where('tipo', 'SERVICIO')->where('estado', 1)->countAllResults()) return redirect()->back()->withInput()->with('error', 'El servicio elegido no está disponible.');

        $transOpen = false;
        try {
            $inicio = $this->fechaFormulario($this->request->getPost('fecha_inicio'));
            $fin = $this->fechaFormulario($this->request->getPost('fecha_fin'));
            $asignada = $datos['idtecnico'] > 0 || $inicio !== null || $fin !== null;
            if ($asignada && (!$datos['idtecnico'] || !$inicio || !$fin)) throw new RuntimeException('Para programar debes seleccionar técnico, inicio y fin.');
            if ($asignada && ($inicio >= $fin || $inicio < date('Y-m-d H:i:s'))) throw new RuntimeException('El horario debe ser futuro y la hora de fin posterior al inicio.');
            $db->transBegin();
            $transOpen = true;
            if ($idVenta > 0) {
                // Bloquear la venta evita duplicados por clics o solicitudes concurrentes.
                $venta = $db->query('SELECT idventa, idcliente, fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE', [$idVenta])->getRowArray();
                if (!$venta || $venta['fechaanulacion'] !== null) throw new RuntimeException('La venta ya no está disponible.');
                if ((int) $venta['idcliente'] !== $datos['idcliente']) throw new RuntimeException('El cliente no coincide con el de la venta.');
                $vendidos = array_column($this->serviciosDeVenta($idVenta), 'idproducto');
                if (!$datos['idservicio'] || !in_array($datos['idservicio'], array_map('intval', $vendidos), true)) throw new RuntimeException('Este servicio no está incluido en la venta.');
                $duplicado = $db->query("SELECT idorden FROM ordenes_servicio WHERE idventa = ? AND idservicio = ? AND estado <> 'CANCELADO' LIMIT 1 FOR UPDATE", [$idVenta, $datos['idservicio']])->getRowArray();
                if ($duplicado) throw new RuntimeException('Este servicio ya tiene una orden activa o finalizada en la venta.');
            }
            if ($asignada) $this->disponibilidad($datos['idtecnico'], $inicio, $fin);
            $id = $this->ordenes->insert([
                'idcliente' => $datos['idcliente'],
                'idventa' => $idVenta ?: null,
                'idservicio' => $datos['idservicio'] ?: null,
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
        $orden = null;
        foreach ($this->consultarOrdenes() as $item) {
            if ((int) $item['idorden'] === $id) {
                $orden = $item;
                break;
            }
        }
        if (!$orden) {
            $db = db_connect();
            $orden = $db->table('ordenes_servicio os')
                ->select("os.*, COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, '')))) AS cliente, CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, '')) AS tecnico, pr.nombre AS servicio", false)
                ->join('personas p', 'p.idpersona = os.idcliente')
                ->join('tecnicos t', 't.idtecnico = os.idtecnico', 'left')
                ->join('productos pr', 'pr.idproducto = os.idservicio', 'left')->where('os.idorden', $id)->get()->getRowArray();
        }
        if (!$orden) return redirect()->to(base_url('admin/servicios'))->with('error', 'La orden solicitada no existe.');
        $db = db_connect();
        $historial = $db->table('historial_orden_servicio h')->select('h.*, u.nombreusuario')->join('usuarios u', 'u.idusuario = h.idusuario')->where('h.idorden', $id)->orderBy('h.idhistorial', 'DESC')->get()->getResultArray();
        $tecnicos = $db->table('tecnicos')->select('idtecnico, nombres, apellidos')->where('estado', 'ACTIVO')->orderBy('nombres')->get()->getResultArray();
        $materiales = $db->table('consumos_servicio cs')->select('cs.*, k.idproducto, k.idlocal, p.nombre AS producto, l.nombrelocal, u.nombreusuario')
            ->join('kardex k', 'k.idkardex = cs.idkardex')->join('productos p', 'p.idproducto = k.idproducto')
            ->join('locales l', 'l.idlocal = k.idlocal')->join('usuarios u', 'u.idusuario = cs.idusuario')
            ->where('cs.idorden', $id)->orderBy('cs.idconsumo', 'DESC')->get()->getResultArray();
        $stockMateriales = $db->query("SELECT k.idkardex, k.idproducto, k.idlocal, k.stockactual, p.nombre AS producto,
            l.nombrelocal, p.controlaserie, p.controlastock,
            COALESCE((SELECT SUM(r.cantidad) FROM reservas_stock r WHERE r.idkardex = k.idkardex AND r.estado = 'RESERVADO'), 0) AS reservado
            FROM kardex k JOIN productos p ON p.idproducto = k.idproducto JOIN locales l ON l.idlocal = k.idlocal
            WHERE p.tipo = 'PRODUCTO' AND p.controlastock = 1 AND p.controlaserie = 0 AND p.estado = 1
            ORDER BY l.nombrelocal, p.nombre")->getResultArray();
        $materialesDisponibles = [];
        foreach ($stockMateriales as $fila) {
            $fila['disponible'] = round((float) $fila['stockactual'] - (float) $fila['reservado'], 2);
            if ($fila['disponible'] <= 0) continue;
            if ($this->productoVendidoEnOrden((int) ($orden['idventa'] ?? 0), (int) $fila['idproducto'])) continue;
            $materialesDisponibles[] = $fila;
        }
        return view('servicios/detalles', [
            'titulo' => 'Orden de servicio',
            'orden' => $orden,
            'historial' => $historial,
            'tecnicos' => $tecnicos,
            'materiales' => $materiales,
            'materialesDisponibles' => $materialesDisponibles,
            'tokenConsumo' => bin2hex(random_bytes(16))
        ]);
    }

    public function agenda()
    {
        if (!$this->autorizado()) return $this->denegar();
        return view('servicios/agenda', ['titulo' => 'Agenda de Servicios', 'ordenes' => $this->consultarOrdenes(true)]);
    }

    public function reprogramar(int $id): RedirectResponse
    {
        if (!$this->autorizado()) return $this->denegar();
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
        if (!$this->autorizado()) return $this->denegar();
        $nuevo = strtoupper(trim((string) $this->request->getPost('estado')));
        $nota = trim((string) $this->request->getPost('observacion'));
        $cambios = [
            'PENDIENTE' => ['CANCELADO'],
            'PROGRAMADO' => ['EN_PROCESO', 'CANCELADO'],
            'EN_PROCESO' => ['FINALIZADO']
        ];
        $db = db_connect();
        $transOpen = false;
        try {
            if (mb_strlen($nota) > 255) throw new RuntimeException('La observación no puede superar 255 caracteres.');
            if ($nuevo === 'FINALIZADO' && mb_strlen($nota) < 5) throw new RuntimeException('Describe el trabajo realizado para finalizar.');
            if ($nuevo === 'CANCELADO' && mb_strlen($nota) < 5) throw new RuntimeException('Indica el motivo de cancelación.');
            $db->transBegin();
            $transOpen = true;
            $orden = $db->query('SELECT idorden, estado, fecha_inicio, fecha_fin FROM ordenes_servicio WHERE idorden = ? FOR UPDATE', [$id])->getRowArray();
            if (!$orden || !in_array($nuevo, $cambios[$orden['estado']] ?? [], true)) throw new RuntimeException('Este cambio de estado no está permitido.');
            if ($nuevo === 'EN_PROCESO' && !empty($orden['fecha_inicio']) && $orden['fecha_inicio'] > date('Y-m-d H:i:s') && mb_strlen($nota) < 10) {
                throw new RuntimeException('El trabajo está programado para una fecha futura. Si va a iniciar antes, escribe el motivo (mínimo 10 caracteres).');
            }
            $datos = ['estado' => $nuevo];
            if ($nuevo === 'FINALIZADO') {
                $datos['resultado'] = $nota;
                $datos['fecha_finalizacion'] = date('Y-m-d H:i:s');
            }
            if (!$this->ordenes->update($id, $datos)) throw new RuntimeException('No se pudo modificar el estado.');
            if (!$this->historial->insert(['idorden' => $id, 'tipoevento' => 'CAMBIO_ESTADO', 'estado_anterior' => $orden['estado'], 'estado_nuevo' => $nuevo, 'observacion' => $nota ?: 'Actualización del estado de la orden.', 'idusuario' => (int) session('idusuario'), 'fecha' => date('Y-m-d H:i:s')])) throw new RuntimeException('No se pudo registrar el historial.');
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

    private function productoVendidoEnOrden(int $idVenta, int $idProducto): bool
    {
        if ($idVenta <= 0) return false;
        $db = db_connect();
        if ($db->table('detventa')->where('idventa', $idVenta)->where('idproducto', $idProducto)->countAllResults() > 0) return true;
        if ($db->tableExists('venta_combo_componentes')) {
            return $db->table('venta_combo_componentes c')->join('detventa d', 'd.iddetventa = c.iddetventa')
                ->where('d.idventa', $idVenta)->where('c.idproducto', $idProducto)->countAllResults() > 0;
        }
        return false;
    }

    public function registrarMaterial(int $id): RedirectResponse
    {
        if (!$this->autorizado()) return $this->denegar();
        $destino = base_url('admin/servicios/detalles/' . $id);
        $idKardex = (int) $this->request->getPost('idkardex');
        $cantidadTexto = trim((string) $this->request->getPost('cantidad'));
        $nota = trim((string) $this->request->getPost('observacion'));
        $token = trim((string) $this->request->getPost('token_operacion'));
        $idUsuario = (int) session('idusuario');
        $db = db_connect();
        $transOpen = false;

        try {
            if ($id < 1 || $idKardex < 1 || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $cantidadTexto)) {
                throw new RuntimeException('Selecciona un material e ingresa una cantidad válida de hasta dos decimales.');
            }
            $cantidad = round((float) $cantidadTexto, 2);
            if ($cantidad <= 0 || mb_strlen($nota) > 255 || !preg_match('/^[a-f0-9]{32}$/', $token)) {
                throw new RuntimeException('La cantidad, observación o solicitud no es válida.');
            }
            $db->transBegin();
            $transOpen = true;
            $orden = $db->query('SELECT idorden, idventa, estado FROM ordenes_servicio WHERE idorden = ? FOR UPDATE', [$id])->getRowArray();
            if (!$orden || $orden['estado'] !== 'EN_PROCESO') throw new RuntimeException('Solo puedes registrar consumos cuando la orden está EN PROCESO.');
            if ($db->table('consumos_servicio')->where('token_operacion', $token)->countAllResults() > 0) {
                throw new RuntimeException('Este consumo ya se procesó. Actualiza la página para registrar otro.');
            }
            $kardex = $db->query("SELECT k.idkardex, k.idproducto, k.idlocal, k.stockactual, p.nombre,
                p.tipo, p.controlastock, p.controlaserie, p.estado
                FROM kardex k JOIN productos p ON p.idproducto = k.idproducto WHERE k.idkardex = ? FOR UPDATE", [$idKardex])->getRowArray();
            if (
                !$kardex || $kardex['tipo'] !== 'PRODUCTO' || (int) $kardex['controlastock'] !== 1 ||
                (int) $kardex['controlaserie'] !== 0 || (int) $kardex['estado'] !== 1
            ) {
                throw new RuntimeException('El material no está habilitado para consumo directo (los artículos con serie requieren otro procedimiento).');
            }
            if ($this->productoVendidoEnOrden((int) ($orden['idventa'] ?? 0), (int) $kardex['idproducto'])) {
                throw new RuntimeException('Este artículo ya pertenece a la venta. Su salida debe gestionarse desde Entregas.');
            }
            $reservado = (float) $db->table('reservas_stock')->selectSum('cantidad')->where('idkardex', $idKardex)
                ->where('estado', 'RESERVADO')->get()->getRow('cantidad');
            $disponible = round((float) $kardex['stockactual'] - $reservado, 2);
            if ($cantidad > $disponible + 0.001) {
                throw new RuntimeException('Stock insuficiente. Disponible sin reservas: ' . number_format(max(0, $disponible), 2) . '.');
            }
            $triggers = $db->query("SELECT TRIGGER_NAME FROM INFORMATION_SCHEMA.TRIGGERS
                WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'movimientos'
                AND EVENT_MANIPULATION = 'INSERT'")->getResultArray();
            if ($triggers) {
                throw new RuntimeException('Se detectó un trigger al insertar movimientos. Verifica si actualiza Kardex antes de registrar consumos.');
            }
            $nuevoStock = round((float) $kardex['stockactual'] - $cantidad, 2);
            $ahora = date('Y-m-d H:i:s');
            if (!$db->table('kardex')->where('idkardex', $idKardex)->update(['stockactual' => $nuevoStock])) {
                throw new RuntimeException('No se pudo actualizar el stock físico.');
            }
            if (!$db->table('movimientos')->insert([
                'idkardex' => $idKardex,
                'idusuario' => $idUsuario,
                'tipo' => 'SALIDA',
                'fecha' => $ahora,
                'cantidad' => $cantidad,
                'saldo' => $nuevoStock,
                'descripcion' => 'Material usado en orden OS-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
                'motivo' => 'Consumo servicio técnico'
            ])) throw new RuntimeException('No se pudo crear el movimiento de Kardex.');
            $idMovimiento = (int) $db->insertID();
            if (!$db->table('consumos_servicio')->insert([
                'idorden' => $id,
                'idkardex' => $idKardex,
                'idmovimiento' => $idMovimiento,
                'cantidad' => $cantidad,
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
                'observacion' => mb_substr('Material: ' . $kardex['nombre'] . ', cantidad ' . number_format($cantidad, 2) . '. ' . $nota, 0, 255),
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
}
