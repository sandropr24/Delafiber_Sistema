<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class VentaAnulacionController extends BaseController
{
    private function rolActual($db, int $idUsuario): ?string
    {
        $usuario = $db->table('usuarios u')
            ->select('roles.rol')
            ->join('roles', 'roles.idrol = u.idrol')
            ->where('u.idusuario', $idUsuario)
            ->where('u.estado', 1)
            ->get()->getRowArray();

        return $usuario ? trim((string) $usuario['rol']) : null;
    }

    public function solicitar(int $idVenta)
    {
        $idUsuario = (int) session()->get('idusuario');
        $motivo = trim((string) $this->request->getPost('motivo'));
        $destino = 'ventas/detalles/' . $idVenta;

        if ($idVenta < 1 || $idUsuario < 1 || mb_strlen($motivo) < 15 || mb_strlen($motivo) > 2000) {
            return redirect()->to($destino)->with('error', 'Ingresa un motivo de 15 a 2000 caracteres.');
        }

        $db = \Config\Database::connect();
        if (!in_array($this->rolActual($db, $idUsuario), ['Administrador', 'Vendedor'], true)) {
            return redirect()->to('admin/ventas')->with('error', 'No tienes permiso para solicitar anulaciones.');
        }

        $db->transBegin();
        try {
            $venta = $db->query(
                'SELECT idventa, fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE',
                [$idVenta]
            )->getRowArray();

            if (!$venta || $venta['fechaanulacion'] !== null) {
                throw new \RuntimeException('La venta no existe o ya fue anulada.');
            }

            $entrega = $db->table('entregas')->select('estado')
                ->where('idventa', $idVenta)->get()->getRowArray();

            if (!$entrega || $entrega['estado'] !== 'CANCELADO') {
                throw new \RuntimeException('Primero debe cancelarse logísticamente la entrega.');
            }

            $existente = $db->table('solicitudes_anulacion')
                ->where('idventa', $idVenta)
                ->whereIn('estado', ['PENDIENTE', 'AUTORIZADA'])
                ->countAllResults();

            if ($existente) {
                throw new \RuntimeException('Esta venta ya tiene una solicitud pendiente o autorizada.');
            }

            if (!$db->table('solicitudes_anulacion')->insert([
                'idventa' => $idVenta,
                'idsolicitante' => $idUsuario,
                'motivo' => $motivo,
                'estado' => 'PENDIENTE',
                'fecha_solicitud' => date('Y-m-d H:i:s')
            ])) {
                throw new \RuntimeException('No se pudo registrar la solicitud.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La base de datos rechazó la operación.');
            }

            $db->transCommit();
            return redirect()->to($destino)->with('success', 'Solicitud registrada. La venta, el pago y el inventario siguen intactos.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Solicitar anulación: ' . $e->getMessage());
            return redirect()->to($destino)->with('error', $e->getMessage());
        }
    }

    public function revisar(int $idSolicitud)
    {
        $idUsuario = (int) session()->get('idusuario');
        $db = \Config\Database::connect();

        if ($idUsuario < 1 || $this->rolActual($db, $idUsuario) !== 'Administrador') {
            return redirect()->to('admin/ventas')->with('error', 'Solo un administrador puede revisar solicitudes.');
        }

        $decision = (string) $this->request->getPost('decision');
        $observacion = trim((string) $this->request->getPost('observacion'));

        if (
            !in_array($decision, ['AUTORIZADA', 'RECHAZADA'], true)
            || mb_strlen($observacion) < 10 || mb_strlen($observacion) > 2000
        ) {
            return redirect()->to('admin/ventas')->with('error', 'Elige una decisión e indica una observación de 10 a 2000 caracteres.');
        }

        $db->transBegin();
        $destino = 'ventas';

        try {
            $referencia = $db->table('solicitudes_anulacion')
                ->select('idventa')
                ->where('idsolicitud', $idSolicitud)
                ->get()->getRowArray();

            if (!$referencia) {
                throw new \RuntimeException('Solicitud no encontrada.');
            }

            $idVenta = (int) $referencia['idventa'];
            $destino = 'ventas/detalles/' . $idVenta;

            $venta = $db->query(
                'SELECT fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE',
                [$idVenta]
            )->getRowArray();

            $solicitud = $db->query(
                'SELECT * FROM solicitudes_anulacion WHERE idsolicitud = ? FOR UPDATE',
                [$idSolicitud]
            )->getRowArray();

            if (!$solicitud || $solicitud['estado'] !== 'PENDIENTE') {
                throw new \RuntimeException('La solicitud ya fue revisada o no existe.');
            }

            if (!$venta || $venta['fechaanulacion'] !== null) {
                throw new \RuntimeException('La venta ya no admite una autorización.');
            }

            if ($decision === 'AUTORIZADA') {
                $entrega = $db->table('entregas')->select('estado')
                    ->where('idventa', $idVenta)->get()->getRowArray();

                if (!$entrega || $entrega['estado'] !== 'CANCELADO') {
                    throw new \RuntimeException('La entrega debe continuar CANCELADA antes de autorizar el trámite.');
                }
            }

            $actualizado = $db->table('solicitudes_anulacion')
                ->where('idsolicitud', $idSolicitud)
                ->where('estado', 'PENDIENTE')
                ->update([
                    'estado' => $decision,
                    'idrevisor' => $idUsuario,
                    'observacion_revision' => $observacion,
                    'fecha_revision' => date('Y-m-d H:i:s')
                ]);

            if (!$actualizado || $db->affectedRows() !== 1) {
                throw new \RuntimeException('No se pudo registrar la decisión.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La base de datos rechazó la revisión.');
            }

            $db->transCommit();

            $mensaje = $decision === 'AUTORIZADA'
                ? 'Trámite autorizado. La venta NO está anulada y el dinero y las reservas NO se han modificado.'
                : 'Solicitud rechazada y decisión registrada en el historial.';

            return redirect()->to($destino)->with('success', $mensaje);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Revisar solicitud anulación: ' . $e->getMessage());
            return redirect()->to($destino)->with('error', $e->getMessage());
        }
    }

    public function ejecutar(int $idSolicitud)
    {
        $idUsuario = (int) session()->get('idusuario');
        $db = \Config\Database::connect();

        if ($idUsuario <= 0 || $this->rolActual($db, $idUsuario) !== 'Administrador') {
            return redirect()->to('admin/ventas')->with('error', 'Solo un administrador puede ejecutar anulaciones.');
        }

        $confirmado = (string) $this->request->getPost('inventario_verificado');
        $observacion = trim((string) $this->request->getPost('observacion_inventario'));

        if ($confirmado !== '1' || mb_strlen($observacion) < 15 || mb_strlen($observacion) > 1000) {
            return redirect()->to('admin/ventas')->with('error', 'Confirma el control físico e indica una observación de 15 a 1000 caracteres.');
        }

        $destino = 'ventas';
        $db->transBegin();

        try {
            $referencia = $db->table('solicitudes_anulacion')
                ->select('idventa')
                ->where('idsolicitud', $idSolicitud)
                ->get()->getRowArray();

            if (!$referencia) {
                throw new \RuntimeException('Solicitud inexistente.');
            }

            $idVenta = (int) $referencia['idventa'];
            $destino = 'ventas/detalles/' . $idVenta;

            $venta = $db->query(
                'SELECT * FROM ventas WHERE idventa = ? FOR UPDATE',
                [$idVenta]
            )->getRowArray();

            $solicitud = $db->query(
                'SELECT * FROM solicitudes_anulacion WHERE idsolicitud = ? FOR UPDATE',
                [$idSolicitud]
            )->getRowArray();

            $entrega = $db->query(
                'SELECT * FROM entregas WHERE idventa = ? FOR UPDATE',
                [$idVenta]
            )->getRowArray();

            if (!$venta || $venta['fechaanulacion'] !== null) {
                throw new \RuntimeException('La venta no existe o ya está anulada.');
            }

            if (!$solicitud || $solicitud['estado'] !== 'AUTORIZADA') {
                throw new \RuntimeException('La solicitud no está autorizada para ejecutarse.');
            }

            if (!$entrega || $entrega['estado'] !== 'CANCELADO' || !empty($entrega['fechaentrega'])) {
                throw new \RuntimeException('La entrega debe estar CANCELADA y nunca haberse confirmado como entregada.');
            }

            if ($venta['tipodocumento'] !== 'NOTA_VENTA' || (int) $venta['estafacturado'] !== 0) {
                throw new \RuntimeException('Los comprobantes tributarios o facturados requieren una revisión documental independiente.');
            }

            if ($venta['formapago'] !== 'CONTADO') {
                throw new \RuntimeException('Las ventas a cuotas, adelanto/saldo o crédito necesitan un procedimiento adicional antes de anularse.');
            }

            $estadoCancelado = $db->query(
                "SELECT idestado FROM estadoventa WHERE LOWER(TRIM(estado)) IN ('cancelada','cancelado') LIMIT 1"
            )->getRowArray();

            if (!$estadoCancelado) {
                throw new \RuntimeException('Falta configurar el estado Cancelada en estadoventa.');
            }

            if ($db->table('anulaciones_comerciales')->where('idventa', $idVenta)->countAllResults() > 0) {
                throw new \RuntimeException('Esta venta ya tiene una anulación ejecutada.');
            }

            if ($db->tableExists('ordenes_servicio')) {
                $ordenVinculada = $db->query(
                    "SELECT idorden, estado FROM ordenes_servicio
                     WHERE idventa = ? AND estado <> 'CANCELADO'
                     LIMIT 1 FOR UPDATE",
                    [$idVenta]
                )->getRowArray();

                if ($ordenVinculada) {
                    throw new \RuntimeException(
                        'No se puede anular esta venta porque tiene la orden de servicio N.º ' .
                            $ordenVinculada['idorden'] . ' en estado ' .
                            $ordenVinculada['estado'] . '. Revísala antes de continuar.'
                    );
                }
            }

            $reservas = $db->query(
                'SELECT * FROM reservas_stock WHERE idventa = ? ORDER BY idkardex FOR UPDATE',
                [$idVenta]
            )->getResultArray();

            foreach ($reservas as $reserva) {
                if ($reserva['estado'] !== 'RESERVADO') {
                    throw new \RuntimeException('Hay reservas consumidas o liberadas. Utiliza el proceso de devolución/revisión.');
                }
            }

            $movimientosSalida = $db->table('movimientos')
                ->where('idventa', $idVenta)
                ->where('tipo', 'SALIDA')
                ->countAllResults();

            if ($movimientosSalida > 0) {
                throw new \RuntimeException('Se detectaron salidas en Kardex: no se permite anular sin devolución física.');
            }

            $productos = $db->query(
                'SELECT d.idproducto, SUM(d.cantidad) cantidad, MAX(p.controlaserie) controlaserie
                 FROM detventa d JOIN productos p ON p.idproducto = d.idproducto
                 WHERE d.idventa = ? AND p.controlastock = 1 GROUP BY d.idproducto
                 UNION ALL
                 SELECT c.idproducto, SUM(c.cantidad) cantidad, MAX(p.controlaserie) controlaserie
                 FROM venta_combo_componentes c JOIN detventa d ON d.iddetventa = c.iddetventa
                 JOIN productos p ON p.idproducto = c.idproducto
                 WHERE d.idventa = ? AND p.controlastock = 1 GROUP BY c.idproducto',
                [$idVenta, $idVenta]
            )->getResultArray();

            $esperados = [];
            $esperadosSeries = [];

            foreach ($productos as $producto) {
                $idProducto = (int) $producto['idproducto'];
                $esperados[$idProducto] = round(
                    ($esperados[$idProducto] ?? 0) + (float) $producto['cantidad'],
                    2
                );

                if ((int) $producto['controlaserie'] === 1) {
                    $esperadosSeries[$idProducto] = round(
                        ($esperadosSeries[$idProducto] ?? 0) + (float) $producto['cantidad'],
                        2
                    );
                }
            }

            if (count($esperados) !== count($reservas)) {
                throw new \RuntimeException('Cantidad de reservas inconsistente con los productos de la venta.');
            }

            foreach ($reservas as $reserva) {
                $kardex = $db->query(
                    'SELECT idkardex, idproducto, idlocal, stockactual
                     FROM kardex WHERE idkardex = ? FOR UPDATE',
                    [(int) $reserva['idkardex']]
                )->getRowArray();

                if (!$kardex || (int) $kardex['idlocal'] !== (int) $venta['idlocal']) {
                    throw new \RuntimeException('La reserva apunta a un almacén o Kardex incorrecto.');
                }

                $idProducto = (int) $kardex['idproducto'];

                if ((float) $kardex['stockactual'] + 0.001 < (float) $reserva['cantidad']) {
                    throw new \RuntimeException('El stock físico registrado no cubre la reserva. Verifica el almacén.');
                }

                if (
                    !isset($esperados[$idProducto])
                    || abs((float) $reserva['cantidad'] - $esperados[$idProducto]) > 0.001
                    || (float) $reserva['cantidad'] <= 0
                ) {
                    throw new \RuntimeException('Las cantidades reservadas no coinciden con el detalle de venta.');
                }

                unset($esperados[$idProducto]);
            }

            if ($esperados) {
                throw new \RuntimeException('Existen productos sin su respectiva reserva.');
            }

            $series = $db->query(
                'SELECT s.idserie, s.idproducto, s.estado, s.idlocal FROM series s
                 INNER JOIN detventa d ON d.iddetventa = s.iddetventa
                 WHERE d.idventa = ? ORDER BY s.idserie FOR UPDATE',
                [$idVenta]
            )->getResultArray();

            $cantidadesSeries = [];

            foreach ($series as $serie) {
                $idProducto = (int) $serie['idproducto'];

                if (
                    $serie['estado'] !== 'RESERVADO'
                    || (int) $serie['idlocal'] !== (int) $venta['idlocal']
                ) {
                    throw new \RuntimeException('Serie asignada con estado o local incorrecto: ' . $serie['idserie']);
                }

                $cantidadesSeries[$idProducto] = ($cantidadesSeries[$idProducto] ?? 0) + 1;
            }

            foreach ($esperadosSeries as $idProducto => $cantidad) {
                if (abs($cantidad - (float) ($cantidadesSeries[$idProducto] ?? 0)) > 0.001) {
                    throw new \RuntimeException('Las series reservadas no coinciden con la venta.');
                }
                unset($cantidadesSeries[$idProducto]);
            }

            if ($cantidadesSeries) {
                throw new \RuntimeException('Existen series de productos que no deberían estar reservadas.');
            }

            $pagos = $db->query(
                'SELECT idpagoventa, monto FROM pagoventa
                 WHERE idventa = ? ORDER BY idpagoventa FOR UPDATE',
                [$idVenta]
            )->getResultArray();

            $centimos = 0;

            foreach ($pagos as $pago) {
                $monto = (float) $pago['monto'];

                if ($monto <= 0) {
                    throw new \RuntimeException('Se detectó un cobro de importe no válido.');
                }

                $centimos += (int) round($monto * 100);
            }

            $pagado = round($centimos / 100, 2);

            if ($pagado > (float) $venta['total'] + 0.01) {
                throw new \RuntimeException('Los pagos superan el total de la venta. Revisa el cobro antes de anular.');
            }

            $ahora = date('Y-m-d H:i:s');

            foreach ($series as $serie) {
                $actualizado = $db->table('series')
                    ->where('idserie', (int) $serie['idserie'])
                    ->where('estado', 'RESERVADO')
                    ->update([
                        'estado' => 'EN_STOCK',
                        'iddetventa' => null
                    ]);

                if (!$actualizado || $db->affectedRows() !== 1) {
                    throw new \RuntimeException('No se pudo liberar la serie ' . $serie['idserie']);
                }
            }

            foreach ($reservas as $reserva) {
                $actualizado = $db->table('reservas_stock')
                    ->where('idreserva', (int) $reserva['idreserva'])
                    ->where('estado', 'RESERVADO')
                    ->update([
                        'estado' => 'LIBERADO',
                        'updated_at' => $ahora
                    ]);

                if (!$actualizado || $db->affectedRows() !== 1) {
                    throw new \RuntimeException('No se pudo liberar la reserva de stock.');
                }
            }

            $actualizado = $db->table('ventas')
                ->where('idventa', $idVenta)
                ->where('fechaanulacion', null)
                ->update([
                    'idestado' => (int) $estadoCancelado['idestado'],
                    'fechaanulacion' => $ahora,
                    'idusuarioanula' => $idUsuario,
                    'motivoanulacion' => mb_substr((string) $solicitud['motivo'], 0, 150)
                ]);

            if (!$actualizado || $db->affectedRows() !== 1) {
                throw new \RuntimeException('No se pudo anular la venta.');
            }

            $estadoReembolso = $pagado > 0 ? 'PENDIENTE' : 'NO_APLICA';

            if (!$db->table('anulaciones_comerciales')->insert([
                'idventa' => $idVenta,
                'idsolicitud' => $idSolicitud,
                'idusuario' => $idUsuario,
                'fecha_anulacion' => $ahora,
                'observacion_inventario' => $observacion,
                'monto_pagado' => $pagado,
                'monto_pendiente_reembolso' => $pagado,
                'estado_reembolso' => $estadoReembolso
            ])) {
                throw new \RuntimeException('No se pudo guardar el acta de anulación.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Error al confirmar la transacción de anulación.');
            }

            $db->transCommit();

            return redirect()->to($destino)->with(
                'success',
                $estadoReembolso === 'PENDIENTE'
                    ? 'Venta anulada y reservas liberadas. Reembolso de S/ ' . number_format($pagado, 2) . ' PENDIENTE; no se ha devuelto dinero automáticamente.'
                    : 'Venta anulada y reservas liberadas. Sin pagos registrados para reembolsar.'
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Ejecutar anulación comercial: ' . $e->getMessage());
            return redirect()->to($destino)->with('error', $e->getMessage());
        }
    }
}
