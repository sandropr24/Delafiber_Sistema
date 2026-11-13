<?php

namespace App\Libraries;

use InvalidArgumentException;

final class CobroVenta
{
    public static function centimos($valor): int
    {
        if (!is_scalar($valor) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $valor)) {
            throw new InvalidArgumentException('Ingrese un importe válido con hasta dos decimales.');
        }
        [$entero, $decimal] = array_pad(explode('.', (string) $valor), 2, '');
        return (int) $entero * 100 + (int) str_pad($decimal, 2, '0');
    }

    public static function normalizar(array $pago, string $metodo): array
    {
        $monto = self::centimos($pago['monto'] ?? '');
        $efectivo = mb_strtolower(trim($metodo)) === 'efectivo';
        $recibido = $efectivo ? self::centimos($pago['montorecibido'] ?? $pago['monto']) : $monto;
        $referencia = trim((string) ($pago['referencia'] ?? ''));
        if ($monto <= 0 || $recibido < $monto) {
            throw new InvalidArgumentException('El pago debe ser positivo y el efectivo recibido debe cubrir el monto aplicado.');
        }
        if ((!$efectivo && $referencia === '') || mb_strlen($referencia) > 50) {
            throw new InvalidArgumentException('Indique una referencia de operación de hasta 50 caracteres para pagos electrónicos.');
        }
        return [
            'monto' => number_format($monto / 100, 2, '.', ''),
            'montorecibido' => number_format($recibido / 100, 2, '.', ''),
            'vuelto' => number_format(($recibido - $monto) / 100, 2, '.', ''),
            'referencia' => $referencia ?: null,
        ];
    }
}
