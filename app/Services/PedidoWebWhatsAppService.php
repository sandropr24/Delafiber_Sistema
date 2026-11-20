<?php

namespace App\Services;

final class PedidoWebWhatsAppService
{
    private string $destino;

    public function __construct(private ?PedidoWhatsAppTransportador $transportador = null, ?string $destino = null)
    {
        $this->transportador ??= new MetaWhatsAppTransportador();
        $this->destino = preg_replace('/\D+/', '', $destino ?? (string) env('whatsapp.recipient', '')) ?? '';
    }

    public function nuevo(array $pedido): ?bool
    {
        if ($this->destino === '') return null;
        if (!preg_match('/^[1-9][0-9]{7,14}$/D', $this->destino)) {
            log_message('error', 'Destinatario de WhatsApp inválido para pedidos web.');
            return false;
        }
        try {
            $this->transportador->enviar($this->destino, [
                'codigo' => (string) ($pedido['codigo'] ?? ''),
                'cliente' => (string) ($pedido['cliente'] ?? ''),
                'total' => 'S/ ' . number_format((float) ($pedido['total'] ?? 0), 2),
                'telefono' => (string) ($pedido['telefono'] ?? ''),
                'modalidad' => (string) ($pedido['modalidad'] ?? ''),
            ]);
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'WhatsApp de pedido no enviado. pedido={pedido}: {error}', [
                'pedido' => (string) ($pedido['codigo'] ?? ''),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
