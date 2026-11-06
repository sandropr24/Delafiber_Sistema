<?php

namespace App\Controllers;

use App\Models\EntregaModel;
use App\Models\UsuarioModel;

class EntregaController extends BaseController
{
    protected $entregaModel;
    protected $usuarioModel;

    public function __construct()
    {
        helper(['url', 'form']);

        $this->entregaModel = model(EntregaModel::class);
        $this->usuarioModel = model(UsuarioModel::class);
    }

    public function index()
    {
        return view('entregas/index', [
            'titulo'   => 'Gestión de Entregas y Logística de Campo',
            'entregas' => $this->entregaModel->obtenerEntregasFiltradas(),
            'tecnicos' => $this->usuarioModel->where('estado', 1)->findAll(),
        ]);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('entregas'));
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

            if ($entrega['estado'] === 'ENTREGADO') {
                throw new \RuntimeException('Esta entrega ya fue confirmada.');
            }

            if ($nuevoEstado === 'PENDIENTE' && $entrega['estado'] !== 'PENDIENTE') {
                throw new \RuntimeException('No se permite retroceder el estado de la entrega.');
            }

            $actualizar = ['estado' => $nuevoEstado];

            if (!empty($idTecnico)) {
                $actualizar['idtecnico'] = $idTecnico;
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
                'message' => $e->getMessage(),
            ]);
        }
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
