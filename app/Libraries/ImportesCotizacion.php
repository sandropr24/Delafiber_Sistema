<?php
namespace App\Libraries;

use InvalidArgumentException;

final class ImportesCotizacion
{
    public static function unidades($valor, int $decimales, int $enteros = 8): int
    {
        if (!is_scalar($valor) || !preg_match('/^\d{1,' . $enteros . '}(?:\.\d{1,' . $decimales . '})?$/D', trim((string) $valor))) throw new InvalidArgumentException('Revisa el importe y su cantidad de decimales.');
        [$entero, $fraccion] = array_pad(explode('.', trim((string) $valor), 2), 2, '');
        return (int) $entero * (10 ** $decimales) + (int) str_pad($fraccion, $decimales, '0');
    }

    public static function texto(int $unidades, int $decimales = 2): string
    {
        $escala = 10 ** $decimales;
        return intdiv($unidades, $escala) . '.' . str_pad((string) ($unidades % $escala), $decimales, '0', STR_PAD_LEFT);
    }

    public static function desglosar($total, $porcentaje): array
    {
        $centimos = self::unidades($total, 2);
        $tasa = self::unidades($porcentaje, 2, 3);
        if ($tasa > 10000) throw new InvalidArgumentException('El IGV debe estar entre 0 y 100 %.');
        $divisor = 10000 + $tasa;
        $base = intdiv($centimos * 10000 + intdiv($divisor, 2), $divisor);
        return ['baseimponible' => self::texto($base), 'montoigv' => self::texto($centimos - $base)];
    }

    public static function calcular(array $detalles, string $moneda, $tipoCambio, $porcentaje, bool $incluye): array
    {
        if (!in_array($moneda, ['PEN', 'USD'], true) || !$detalles || count($detalles) > 200) throw new InvalidArgumentException('Selecciona moneda y entre 1 y 200 artículos.');
        $cambio = self::unidades($tipoCambio, 4, 4);
        $tasa = self::unidades($porcentaje, 2, 3);
        if ($cambio <= 0 || $tasa > 10000) throw new InvalidArgumentException('El tipo de cambio debe ser positivo y el IGV estar entre 0 y 100 %.');
        $total = 0; $lineas = [];
        foreach ($detalles as $d) {
            if (!is_array($d)) throw new InvalidArgumentException('Detalle de cotización inválido.');
            $precio = self::unidades($d['precio_origen'] ?? '', 4);
            $cantidad = self::unidades($d['cantidad'] ?? '', 2);
            if ($precio <= 0 || $cantidad <= 0 || ($moneda === 'USD' && $precio > intdiv(PHP_INT_MAX - 500000, $cambio))) throw new InvalidArgumentException('Cantidad o precio fuera de rango.');
            $pen = $moneda === 'USD' ? intdiv($precio * $cambio + 500000, 1000000) : intdiv($precio + 50, 100);
            if (!$incluye) $pen = intdiv($pen * (10000 + $tasa) + 5000, 10000);
            if ($pen <= 0 || $pen > 9999999999 || $cantidad > intdiv(PHP_INT_MAX - 50, $pen)) throw new InvalidArgumentException('El precio final en soles está fuera de rango.');
            $importe = intdiv($cantidad * $pen + 50, 100);
            $total += $importe;
            if ($total > 9999999999) throw new InvalidArgumentException('El total supera el máximo permitido.');
            $lineas[] = ['idproducto' => (int) ($d['idproducto'] ?? 0), 'cantidad' => self::texto($cantidad), 'precio_origen' => self::texto($precio, 4), 'precioventa' => self::texto($pen), 'importe' => self::texto($importe), 'descripcion' => trim((string) ($d['descripcion'] ?? ''))];
        }
        if ($total <= 0) throw new InvalidArgumentException('El total debe ser mayor que cero.');
        $desglose = self::desglosar(self::texto($total), self::texto($tasa));
        return ['subtotal' => $desglose['baseimponible'], 'montoigv' => $desglose['montoigv'], 'total' => self::texto($total), 'tipocambio' => self::texto($cambio, 4), 'porcentajeigv' => self::texto($tasa), 'detalles' => $lineas];
    }
}
