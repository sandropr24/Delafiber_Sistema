<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

final class PedidoWebCorreoService
{
    public function __construct(private ?BaseConnection $db = null, private ?PedidoCorreoTransportador $transportador = null)
    {
        $this->db ??= db_connect();
        $this->transportador ??= new CiEmailTransportador();
    }

    private function pedido(int $id): ?array
    {
        $fila = $this->db->table('pedido p')
            ->select('p.idpedido, p.codigo, p.tipopedido, p.total, pw.nombre_contacto, pw.email_contacto')
            ->join('pedido_web pw', 'pw.idpedido = p.idpedido')
            ->where('p.idpedido', $id)->where('p.tipopedido', 'TIENDA_WEB')->get()->getRowArray();
        if (!$fila || !filter_var($fila['email_contacto'], FILTER_VALIDATE_EMAIL)) return null;
        return $fila;
    }

    private function enviar(array $pedido, string $evento, string $asunto, string $mensaje): bool
    {
        try {
            $this->transportador->enviar($pedido['email_contacto'], $asunto, $mensaje);
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Correo de pedido no enviado. pedido={pedido} evento={evento}', [
                'pedido' => (int) $pedido['idpedido'], 'evento' => $evento,
            ]);
            return false;
        }
    }

    public function recibido(int $idPedido, string $token): ?bool
    {
        $pedido = $this->pedido($idPedido);
        if (!$pedido) return null;
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $enlace = base_url('seguimiento/' . $token);
        $mensaje = "Hola {$pedido['nombre_contacto']},\n\n"
            . "Recibimos tu pedido {$pedido['codigo']} por S/ " . number_format((float) $pedido['total'], 2) . ".\n"
            . "Actualmente está pendiente de confirmación. No se ha realizado ningún cobro.\n\n"
            . "Consulta su estado aquí:\n{$enlace}\n\n"
            . "Conserva este enlace de forma privada.\n\nDELAFIBER";
        return $this->enviar($pedido, 'RECIBIDO', 'Recibimos tu pedido ' . $pedido['codigo'], $mensaje);
    }

    public function cambioEstado(int $idPedido, string $estado, ?string $motivo = null): ?bool
    {
        $pedido = $this->pedido($idPedido);
        if (!$pedido) return null;
        $estado = strtoupper(trim($estado));
        if (!in_array($estado, ['CONFIRMADO', 'CANCELADO'], true)) return null;
        if ($estado === 'CONFIRMADO') {
            $asunto = 'Tu pedido ' . $pedido['codigo'] . ' fue confirmado';
            $detalle = "Nuestro equipo confirmó tu pedido. Nos comunicaremos contigo para coordinar los siguientes pasos.";
        } else {
            $asunto = 'Actualización de tu pedido ' . $pedido['codigo'];
            $detalle = "Tu pedido fue cancelado.\nMotivo: " . (trim((string) $motivo) ?: 'No especificado.');
        }
        $mensaje = "Hola {$pedido['nombre_contacto']},\n\n{$detalle}\n\n"
            . "Código: {$pedido['codigo']}\n"
            . "También puedes consultar tus pedidos desde " . base_url('cuenta') . ".\n\nDELAFIBER";
        return $this->enviar($pedido, $estado, $asunto, $mensaje);
    }
}
