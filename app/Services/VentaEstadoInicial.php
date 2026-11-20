<?php

namespace App\Services;

final class VentaEstadoInicial
{
    public static function permitido(?string $estado): bool
    {
        return in_array(mb_strtolower(trim((string) $estado)), ['pendiente', 'confirmada'], true);
    }
}
