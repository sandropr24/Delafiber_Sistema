<?php

namespace App\Services;

interface PedidoWhatsAppTransportador
{
    public function enviar(string $destino, array $datos): void;
}
