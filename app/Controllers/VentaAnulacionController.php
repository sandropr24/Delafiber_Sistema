<?php

namespace App\Controllers;

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
            return redirect()->to('ventas')->with('error', 'No tienes permiso para solicitar anulaciones.');
        }

        $db->transBegin();
        try {
            $venta = $db->query('SELECT idventa, fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE', [$idVenta])->getRowArray();
            if (!$venta || $venta['fechaanulacion'] !== null) {
                throw new \RuntimeException('La venta no existe o ya fue anulada.');
            }
            $entrega = $db->table('entregas')->select('estado')->where('idventa', $idVenta)->get()->getRowArray();
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
                'fecha_solicitud' => date('Y-m-d H:i:s'),
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
            return redirect()->to('ventas')->with('error', 'Solo un administrador puede revisar solicitudes.');
        }

        $decision = (string) $this->request->getPost('decision');
        $observacion = trim((string) $this->request->getPost('observacion'));
        if (!in_array($decision, ['AUTORIZADA', 'RECHAZADA'], true)
            || mb_strlen($observacion) < 10 || mb_strlen($observacion) > 2000) {
            return redirect()->to('ventas')->with('error', 'Elige una decisión e indica una observación de 10 a 2000 caracteres.');
        }

        $db->transBegin();
        $destino = 'ventas';
        try {
            $referencia = $db->table('solicitudes_anulacion')->select('idventa')->where('idsolicitud', $idSolicitud)->get()->getRowArray();
            if (!$referencia) {
                throw new \RuntimeException('Solicitud no encontrada.');
            }
            $idVenta = (int) $referencia['idventa'];
            $destino = 'ventas/detalles/' . $idVenta;
            $venta = $db->query('SELECT fechaanulacion FROM ventas WHERE idventa = ? FOR UPDATE', [$idVenta])->getRowArray();
            $solicitud = $db->query('SELECT * FROM solicitudes_anulacion WHERE idsolicitud = ? FOR UPDATE', [$idSolicitud])->getRowArray();
            if (!$solicitud || $solicitud['estado'] !== 'PENDIENTE') {
                throw new \RuntimeException('La solicitud ya fue revisada o no existe.');
            }
            if (!$venta || $venta['fechaanulacion'] !== null) {
                throw new \RuntimeException('La venta ya no admite una autorización.');
            }
            if ($decision === 'AUTORIZADA') {
                $entrega = $db->table('entregas')->select('estado')->where('idventa', $idVenta)->get()->getRowArray();
                if (!$entrega || $entrega['estado'] !== 'CANCELADO') {
                    throw new \RuntimeException('La entrega debe continuar CANCELADA antes de autorizar el trámite.');
                }
            }
            if (!$db->table('solicitudes_anulacion')->where('idsolicitud', $idSolicitud)->where('estado', 'PENDIENTE')->update([
                'estado' => $decision,
                'idrevisor' => $idUsuario,
                'observacion_revision' => $observacion,
                'fecha_revision' => date('Y-m-d H:i:s'),
            ]) || $db->affectedRows() !== 1) {
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
}
