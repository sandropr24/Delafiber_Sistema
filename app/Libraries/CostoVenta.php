<?php

namespace App\Libraries;

use InvalidArgumentException;

final class CostoVenta
{
    /**
     * Recibe datos del servidor, con productos y Kardex bloqueados dentro de
     * la transacción. Los componentes contienen las cantidades totales de cada
     * detalle, no la composición actual consultada después de vender.
     */
    public static function calcular(array $detalles, array $articulos, array $componentesPorDetalle, array $costosKardex): array
    {
        $costos = [];
        foreach ($detalles as $indice => $detalle) {
            $producto = $articulos[(int) $detalle['idproducto']];
            if ($producto['tipo'] === 'COMBO') {
                $componentes = $componentesPorDetalle[$indice] ?? [];
                $cantidad = (float) $detalle['cantidad'];
                if (!$componentes || $cantidad <= 0) {
                    throw new InvalidArgumentException('No se pudo determinar el costo del combo.');
                }
                $costoTotal = 0.0;
                foreach ($componentes as $componente) {
                    $articulo = $articulos[(int) $componente['idproducto']];
                    $costoTotal += self::costoArticulo($articulo, $costosKardex) * (float) $componente['cantidad'];
                }
                $unitario = $costoTotal / $cantidad;
            } else {
                $unitario = self::costoArticulo($producto, $costosKardex);
            }

            // detventa.costounitario es DECIMAL(10,2). Redondear solo al final,
            // conservando los cuatro decimales de Kardex durante el cálculo.
            $unitario = round($unitario, 2);
            if (!is_finite($unitario) || $unitario < 0 || $unitario > 99999999.99) {
                throw new InvalidArgumentException('El costo de un artículo supera el rango permitido.');
            }
            $costos[$indice] = number_format($unitario, 2, '.', '');
        }
        return $costos;
    }

    private static function costoArticulo(array $producto, array $costosKardex): float
    {
        $id = (int) $producto['idproducto'];
        if (!empty($producto['controlastock'])) {
            if (!array_key_exists($id, $costosKardex)) {
                throw new InvalidArgumentException('No existe un costo de Kardex para ' . $producto['nombre'] . '.');
            }
            $costo = $costosKardex[$id];
        } else {
            // No inventar costos de mano de obra ni exigir Kardex a servicios.
            $costo = $producto['preciocosto'] ?? 0;
        }
        if (!is_numeric($costo) || !is_finite((float) $costo) || (float) $costo < 0) {
            throw new InvalidArgumentException('El costo de ' . $producto['nombre'] . ' no es válido.');
        }
        return (float) $costo;
    }
}
