<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EntregaModel;
use App\Models\TecnicoModel;

class EntregaController extends BaseController
{
    protected $entregaModel;
    protected $tecnicoModel;

    public function __construct()
    {
        helper(['url', 'form']);

        $this->entregaModel = model(EntregaModel::class);
        $this->tecnicoModel = model(TecnicoModel::class);
    }

    public function index()
    {
        return view('entregas/index', [
            'titulo'   => 'Gestión de Entregas y Logística de Campo',
            'entregas' => $this->entregaModel->obtenerEntregasFiltradas(),
            'tecnicos' => $this->tecnicoModel->activos(),
        ]);
    }


    public function token_csrf()
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON([
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ]);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('admin/entregas'));
        }

        $resultado = $this->entregaModel->obtenerEntregasFiltradas(
            $this->request->getGet('estado'),
            $this->request->getGet('idtecnico'),
            $this->request->getGet('q')
        );

        return $this->response->setJSON($resultado);
    }

    public function cambiar_estado()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Petición no válida.',
            ]);
        }

        $idEntrega = (int) $this->request->getPost('identrega');
        $nuevoEstado = (string) $this->request->getPost('estado');
        $idTecnico = $this->request->getPost('idtecnico');

        if ($idEntrega <= 0 || !in_array($nuevoEstado, ['PENDIENTE', 'EN_CAMINO', 'ENTREGADO'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Entrega o estado inválido.',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $entrega = $db->query(
                'SELECT * FROM entregas WHERE identrega = ? FOR UPDATE',
                [$idEntrega]
            )->getRowArray();

            if (!$entrega) {
                throw new \RuntimeException('Entrega no encontrada.');
            }

            $transiciones = [
                'PENDIENTE' => ['PENDIENTE', 'EN_CAMINO'],
                'EN_CAMINO' => ['EN_CAMINO', 'ENTREGADO'],
                'REPROGRAMADO' => ['REPROGRAMADO', 'EN_CAMINO'],
                'INCIDENCIA' => [],
                'RECHAZADO' => [],
                'ENTREGADO' => [],
                'CANCELADO' => [],
            ];
            $estadoAnterior = (string) $entrega['estado'];
            if (!in_array($nuevoEstado, $transiciones[$estadoAnterior] ?? [], true)) {
                throw new \RuntimeException('Transición no permitida: ' . $estadoAnterior . ' a ' . $nuevoEstado . '. Resuelve las incidencias antes de continuar.');
            }

            $actualizar = ['estado' => $nuevoEstado];

            if ($idTecnico !== null && $idTecnico !== '') {
                $tecnico = $this->tecnicoModel->find((int) $idTecnico);
                if (!$tecnico || $tecnico['estado'] !== 'ACTIVO') {
                    throw new \RuntimeException('Selecciona un técnico activo válido.');
                }
                $actualizar['idtecnico'] = (int) $idTecnico;
            }

            if ($nuevoEstado === 'ENTREGADO' && in_array($entrega['estado'], ['RECHAZADO', 'INCIDENCIA'], true)) {
                throw new \RuntimeException('Primero resuelve o reprograma la incidencia antes de confirmar la entrega.');
            }

            if ($nuevoEstado === 'EN_CAMINO' && empty($entrega['fechaencamino'])) {
                $actualizar['fechaencamino'] = date('Y-m-d H:i:s');
            }

            if ($nuevoEstado === 'ENTREGADO') {
                $this->confirmarSalidaInventario($db, $entrega);
                $actualizar['fechaentrega'] = date('Y-m-d H:i:s');
            }

            if (!$db->table('entregas')->where('identrega', $idEntrega)->update($actualizar)) {
                throw new \RuntimeException('No se pudo actualizar la entrega.');
            }

            if ($estadoAnterior !== $nuevoEstado) {
                $idUsuario = (int) session()->get('idusuario');
                if ($idUsuario <= 0) {
                    throw new \RuntimeException('No se identificó al usuario responsable del cambio.');
                }
                $ahora = date('Y-m-d H:i:s');
                if (!$db->table('entrega_incidencias')->insert([
                    'identrega' => $idEntrega,
                    'idusuario' => $idUsuario,
                    'idtecnico' => $actualizar['idtecnico'] ?? ($entrega['idtecnico'] ?: null),
                    'tipo' => 'CAMBIO_ESTADO',
                    'motivo' => 'Cambio de estado registrado desde Gestión de Entregas.',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $nuevoEstado,
                    'fechaintento' => $ahora,
                    'fechareprogramada' => null,
                    'created_at' => $ahora,
                ])) {
                    throw new \RuntimeException('No se pudo registrar el historial del cambio.');
                }
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Falló una operación de base de datos.');
            }

            $db->transCommit();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al confirmar entrega: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de la entrega. Inténtelo nuevamente.',
            ]);
        }
    }


    public function cancelar_definitivamente()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Solicitud inválida.']);
        }

        $id = (int) $this->request->getPost('identrega');
        $motivo = trim((string) $this->request->getPost('motivo'));
        $idUsuario = (int) session()->get('idusuario');
        if ($id <= 0 || $idUsuario <= 0 || mb_strlen($motivo) < 10 || mb_strlen($motivo) > 2000) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Indica una entrega válida y un motivo de 10 a 2000 caracteres.',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $entrega = $db->query('SELECT * FROM entregas WHERE identrega = ? FOR UPDATE', [$id])->getRowArray();
            if (!$entrega) {
                throw new \RuntimeException('Entrega no encontrada.');
            }
            $anterior = (string) $entrega['estado'];
            if (!in_array($anterior, ['PENDIENTE', 'RECHAZADO', 'REPROGRAMADO', 'INCIDENCIA'], true)) {
                throw new \RuntimeException('No es posible cancelar esta entrega. Si está en camino, verifica primero el retorno físico; si ya fue entregada, utiliza un proceso de devolución.');
            }

            if (!$db->table('entregas')->where('identrega', $id)->where('estado', $anterior)->update(['estado' => 'CANCELADO'])) {
                throw new \RuntimeException('No se pudo cerrar la entrega.');
            }
            $ahora = date('Y-m-d H:i:s');
            if (!$db->table('entrega_incidencias')->insert([
                'identrega' => $id,
                'idusuario' => $idUsuario,
                'idtecnico' => $entrega['idtecnico'] ?: null,
                'tipo' => 'CANCELACION_ENTREGA',
                'motivo' => $motivo,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'CANCELADO',
                'fechaintento' => $ahora,
                'fechareprogramada' => null,
                'created_at' => $ahora,
            ])) {
                throw new \RuntimeException('No se pudo guardar el historial de cancelación.');
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('La operación no se pudo completar.');
            }
            $db->transCommit();
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Entrega cancelada. La venta y las reservas permanecen vigentes hasta su resolución comercial.',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Cancelar entrega: ' . $e->getMessage());
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'No se pudo cancelar la entrega. Inténtelo nuevamente.']);
        }
    }

    public function registrar_incidencia()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Solicitud inválida.']);
        }

        $id = (int) $this->request->getPost('identrega');
        $tipo = trim((string) $this->request->getPost('tipo'));
        $motivo = trim((string) $this->request->getPost('motivo'));
        $fechaNueva = trim((string) $this->request->getPost('fechareprogramada'));
        $tipos = ['RECHAZO', 'CLIENTE_AUSENTE', 'DIRECCION_INCORRECTA', 'SOLICITUD_CLIENTE', 'PRODUCTO_DANADO', 'PRODUCTO_EQUIVOCADO', 'OTRO'];
        $idUsuario = (int) session()->get('idusuario');

        if ($id <= 0 || !in_array($tipo, $tipos, true) || mb_strlen($motivo) < 5 || mb_strlen($motivo) > 2000 || $idUsuario <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Verifica la entrega, el tipo y el motivo (mínimo 5 caracteres).']);
        }

        $estadoNuevo = $tipo === 'RECHAZO' ? 'RECHAZADO' : 'INCIDENCIA';
        if ($fechaNueva !== '') {
            $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fechaNueva);
            if (!$fecha || $fecha->format('Y-m-d\TH:i') !== $fechaNueva || $fecha <= new \DateTimeImmutable()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'La fecha de reprogramación debe ser futura.']);
            }
            $estadoNuevo = 'REPROGRAMADO';
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $entrega = $db->query('SELECT * FROM entregas WHERE identrega = ? FOR UPDATE', [$id])->getRowArray();
            if (!$entrega || in_array($entrega['estado'], ['ENTREGADO', 'CANCELADO'], true)) {
                throw new \RuntimeException('La entrega no existe o ya está cerrada.');
            }
            if (!$db->table('entregas')->where('identrega', $id)->update(['estado' => $estadoNuevo])) {
                throw new \RuntimeException('No se pudo actualizar el estado.');
            }
            $ahora = date('Y-m-d H:i:s');
            if (!$db->table('entrega_incidencias')->insert([
                'identrega' => $id,
                'idusuario' => $idUsuario,
                'idtecnico' => $entrega['idtecnico'] ?: null,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'estado_anterior' => $entrega['estado'],
                'estado_nuevo' => $estadoNuevo,
                'fechaintento' => $ahora,
                'fechareprogramada' => $fechaNueva !== '' ? $fecha->format('Y-m-d H:i:s') : null,
                'created_at' => $ahora,
            ])) {
                throw new \RuntimeException('No se pudo registrar la incidencia.');
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transacción no se completó.');
            }
            $db->transCommit();
            return $this->response->setJSON(['success' => true, 'message' => 'Incidencia registrada correctamente.']);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Registrar incidencia: ' . $e->getMessage());
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'No se pudo registrar la incidencia. Inténtelo nuevamente.']);
        }
    }

    public function historial_incidencias($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }
        $db = \Config\Database::connect();
        if (!$db->table('entregas')->where('identrega', (int) $id)->countAllResults()) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Entrega no encontrada.']);
        }
        $registros = $db->table('entrega_incidencias ri')
            ->select('ri.tipo, ri.motivo, ri.estado_anterior, ri.estado_nuevo, ri.fechaintento, ri.fechareprogramada, ri.created_at')
            ->where('ri.identrega', (int) $id)
            ->orderBy('ri.idincidencia', 'DESC')->get()->getResultArray();
        return $this->response->setJSON(['success' => true, 'registros' => $registros]);
    }

    private function confirmarSalidaInventario($db, array $entrega): void
    {
        $idVenta = (int) $entrega['idventa'];

        $venta = $db->query(
            'SELECT idventa, idlocal, idcajero FROM ventas WHERE idventa = ? FOR UPDATE',
            [$idVenta]
        )->getRowArray();

        if (!$venta) {
            throw new \RuntimeException('Venta no encontrada.');
        }

        $reservas = $db->query(
            'SELECT * FROM reservas_stock WHERE idventa = ? ORDER BY idkardex FOR UPDATE',
            [$idVenta]
        )->getResultArray();

        foreach ($reservas as $reserva) {
            if ($reserva['estado'] !== 'RESERVADO') {
                throw new \RuntimeException('La venta tiene reservas ya procesadas.');
            }
        }

        $detalles = $db->query(
            'SELECT d.idproducto, SUM(d.cantidad) AS cantidad
             FROM detventa d
             INNER JOIN productos p ON p.idproducto = d.idproducto
             WHERE d.idventa = ? AND p.controlastock = 1
             GROUP BY d.idproducto',
            [$idVenta]
        )->getResultArray();

        $detallesCombo = $db->query(
            'SELECT c.idproducto, SUM(c.cantidad) AS cantidad
             FROM venta_combo_componentes c
             INNER JOIN detventa d ON d.iddetventa = c.iddetventa
             INNER JOIN productos p ON p.idproducto = c.idproducto
             WHERE d.idventa = ? AND p.controlastock = 1
             GROUP BY c.idproducto',
            [$idVenta]
        )->getResultArray();
        $sumas = [];
        foreach (array_merge($detalles, $detallesCombo) as $detalle) {
            $id = (int) $detalle['idproducto'];
            $sumas[$id] = round(($sumas[$id] ?? 0) + (float) $detalle['cantidad'], 2);
        }
        $detalles = [];
        foreach ($sumas as $id => $cantidad) {
            $detalles[] = ['idproducto' => $id, 'cantidad' => $cantidad];
        }

        if (count($detalles) !== count($reservas)) {
            throw new \RuntimeException('Las reservas no coinciden con los productos físicos vendidos.');
        }

        $cantidadesPorProducto = [];

        foreach ($detalles as $detalle) {
            $cantidadesPorProducto[(int) $detalle['idproducto']] = round((float) $detalle['cantidad'], 2);
        }

        $idUsuario = (int) (session()->get('idusuario') ?: $venta['idcajero']);

        if ($idUsuario <= 0) {
            throw new \RuntimeException('No se encontró el usuario que confirma la entrega.');
        }

        foreach ($reservas as $reserva) {
            $kardex = $db->query(
                'SELECT idkardex, idproducto, idlocal, stockactual
                 FROM kardex WHERE idkardex = ? FOR UPDATE',
                [(int) $reserva['idkardex']]
            )->getRowArray();

            if (!$kardex || (int) $kardex['idlocal'] !== (int) $venta['idlocal']) {
                throw new \RuntimeException('La reserva apunta a un Kardex no válido.');
            }

            $idProducto = (int) $kardex['idproducto'];
            $cantidad = round((float) $reserva['cantidad'], 2);

            if (
                !isset($cantidadesPorProducto[$idProducto]) ||
                abs($cantidadesPorProducto[$idProducto] - $cantidad) > 0.001
            ) {
                throw new \RuntimeException('La cantidad reservada no coincide con la venta.');
            }

            $nuevoStock = round((float) $kardex['stockactual'] - $cantidad, 2);

            if ($cantidad <= 0 || $nuevoStock < 0) {
                throw new \RuntimeException('Stock insuficiente para confirmar la entrega.');
            }

            if (!$db->table('kardex')
                ->where('idkardex', $kardex['idkardex'])
                ->update(['stockactual' => $nuevoStock])) {
                throw new \RuntimeException('No se pudo actualizar Kardex.');
            }

            if (!$db->table('movimientos')->insert([
                'idkardex'   => $kardex['idkardex'],
                'idventa'    => $idVenta,
                'idusuario'  => $idUsuario,
                'tipo'       => 'SALIDA',
                'fecha'      => date('Y-m-d H:i:s'),
                'cantidad'   => $cantidad,
                'saldo'      => $nuevoStock,
                'descripcion' => 'Entrega de venta N° ' . $idVenta,
                'motivo'     => 'Entrega confirmada',
            ])) {
                throw new \RuntimeException('No se pudo registrar el movimiento de salida.');
            }

            $seriesRequeridas = $db->query(
                'SELECT COALESCE(SUM(cantidad), 0) AS cantidad FROM (
                    SELECT d.cantidad FROM detventa d JOIN productos p ON p.idproducto = d.idproducto
                    WHERE d.idventa = ? AND d.idproducto = ? AND p.controlaserie = 1
                    UNION ALL
                    SELECT c.cantidad FROM venta_combo_componentes c
                    JOIN detventa d ON d.iddetventa = c.iddetventa
                    JOIN productos p ON p.idproducto = c.idproducto
                    WHERE d.idventa = ? AND c.idproducto = ? AND p.controlaserie = 1
                ) x',
                [$idVenta, $idProducto, $idVenta, $idProducto]
            )->getRow('cantidad');
            if ((float) $seriesRequeridas > 0) {
                $seriesReservadas = $db->query(
                    'SELECT s.idserie FROM series s INNER JOIN detventa d ON d.iddetventa = s.iddetventa
                     WHERE d.idventa = ? AND s.idproducto = ? AND s.estado = ? AND s.idlocal = ? FOR UPDATE',
                    [$idVenta, $idProducto, 'RESERVADO', $venta['idlocal']]
                )->getResultArray();
                if (count($seriesReservadas) !== (int) $seriesRequeridas) {
                    throw new \RuntimeException('Las series reservadas no coinciden con la cantidad entregada.');
                }
                foreach ($seriesReservadas as $serie) {
                    if (!$db->table('series')->where('idserie', $serie['idserie'])
                        ->where('estado', 'RESERVADO')->update(['estado' => 'VENDIDO'])) {
                        throw new \RuntimeException('No se pudo confirmar la serie entregada.');
                    }
                }
            }

            if (!$db->table('reservas_stock')
                ->where('idreserva', $reserva['idreserva'])
                ->where('estado', 'RESERVADO')
                ->update([
                    'estado'     => 'CONSUMIDO',
                    'updated_at' => date('Y-m-d H:i:s'),
                ])) {
                throw new \RuntimeException('No se pudo consumir la reserva.');
            }
        }
    }
}
