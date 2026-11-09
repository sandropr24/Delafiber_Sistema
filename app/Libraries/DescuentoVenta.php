<?php

namespace App\Libraries;

final class DescuentoVenta
{
    private static function centimos($valor, string $campo): int
    {
        $texto = trim((string) $valor);
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $texto)) {
            throw new \InvalidArgumentException("{$campo} debe tener como máximo dos decimales.");
        }
        [$entero, $decimal] = array_pad(explode('.', $texto, 2), 2, '');
        return (int) $entero * 100 + (int) str_pad($decimal, 2, '0');
    }

    public static function calcular(array $detalles, string $tipo, $valor, string $motivo): array
    {
        if (!$detalles) {
            throw new \InvalidArgumentException('Agrega al menos un artículo.');
        }

        $subtotalCentimos = 0;
        foreach ($detalles as $item) {
            $precio = self::centimos($item['precioventa'] ?? '', 'El precio de venta');
            $cantidad = self::centimos($item['cantidad'] ?? '', 'La cantidad');
            if ($precio <= 0 || $cantidad <= 0) {
                throw new \InvalidArgumentException('La cantidad y el precio deben ser mayores que cero.');
            }
            $subtotalCentimos += intdiv($precio * $cantidad + 50, 100);
            if ($subtotalCentimos > 9999999999) {
                throw new \InvalidArgumentException('El importe de la venta supera el máximo permitido.');
            }
        }

        if ($tipo === 'NINGUNO') {
            if (self::centimos($valor, 'El descuento') !== 0 || trim($motivo) !== '') {
                throw new \InvalidArgumentException('Selecciona un tipo de descuento para aplicarlo.');
            }
            $descuentoCentimos = 0;
            $valorNormalizado = '0.00';
            $motivo = '';
        } elseif (in_array($tipo, ['PORCENTAJE', 'MONTO'], true)) {
            $unidades = self::centimos($valor, 'El valor del descuento');
            if ($unidades <= 0) {
                throw new \InvalidArgumentException('Ingresa un descuento mayor que cero.');
            }
            $motivo = trim($motivo);
            $longitudMotivo = function_exists('mb_strlen') ? mb_strlen($motivo) : strlen($motivo);
            if ($longitudMotivo < 5 || $longitudMotivo > 300) {
                throw new \InvalidArgumentException('El motivo del descuento debe tener entre 5 y 300 caracteres.');
            }
            if ($tipo === 'PORCENTAJE') {
                if ($unidades > 10000) {
                    throw new \InvalidArgumentException('El descuento no puede superar el 100 %.');
                }
                $descuentoCentimos = intdiv($subtotalCentimos * $unidades + 5000, 10000);
            } else {
                $descuentoCentimos = $unidades;
            }
            $valorNormalizado = number_format($unidades / 100, 2, '.', '');
        } else {
            throw new \InvalidArgumentException('Tipo de descuento no válido.');
        }

        if ($descuentoCentimos >= $subtotalCentimos) {
            throw new \InvalidArgumentException('El descuento debe ser menor que el subtotal de la venta.');
        }

        return [
            'subtotal' => number_format($subtotalCentimos / 100, 2, '.', ''),
            'descuento' => number_format($descuentoCentimos / 100, 2, '.', ''),
            'total' => number_format(($subtotalCentimos - $descuentoCentimos) / 100, 2, '.', ''),
            'tipodescuento' => $tipo,
            'valordescuento' => $valorNormalizado,
            'motivodescuento' => $motivo !== '' ? $motivo : null,
        ];
    }
}
