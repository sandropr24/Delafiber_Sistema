<?php

namespace App\Services;

interface PedidoCorreoTransportador
{
    public function enviar(string $destino, string $asunto, string $mensaje): void;
}
