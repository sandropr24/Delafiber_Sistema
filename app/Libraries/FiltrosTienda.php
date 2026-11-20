<?php

namespace App\Libraries;

final class FiltrosTienda
{
    public const ORDENES = [
        'nombre_asc' => 'Nombre: A a Z',
        'nombre_desc' => 'Nombre: Z a A',
        'precio_asc' => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
    ];

    public static function normalizar(array $entrada): array
    {
        $avisos = [];
        $minimo = self::precio($entrada['precio_min'] ?? '', $avisos);
        $maximo = self::precio($entrada['precio_max'] ?? '', $avisos);
        if ($minimo !== '' && $maximo !== '' && (float) $minimo > (float) $maximo) {
            $avisos[] = 'El precio mínimo no puede superar al máximo. No se aplicó el rango de precios.';
            $minimo = $maximo = '';
        }
        $tipo = self::texto($entrada['tipo'] ?? '');
        $orden = self::texto($entrada['orden'] ?? '');

        return [
            'q' => mb_substr(self::texto($entrada['q'] ?? ''), 0, 100),
            'tipo' => in_array($tipo, ['PRODUCTO', 'SERVICIO', 'COMBO'], true) ? $tipo : '',
            'categoria' => self::identificador($entrada['categoria'] ?? 0),
            'marca' => self::identificador($entrada['marca'] ?? 0),
            'precio_min' => $minimo,
            'precio_max' => $maximo,
            'orden' => isset(self::ORDENES[$orden]) ? $orden : 'nombre_asc',
            'avisos' => array_values(array_unique($avisos)),
        ];
    }

    private static function texto($valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }

    private static function identificador($valor): int
    {
        $valor = self::texto($valor);
        return ctype_digit($valor) && strlen($valor) <= 10 && (float) $valor <= 2147483647 ? (int) $valor : 0;
    }

    private static function precio($valor, array &$avisos): string
    {
        if ($valor === '' || $valor === null) return '';
        $texto = is_string($valor) || is_int($valor) || is_float($valor)
            ? str_replace(',', '.', self::texto($valor)) : '';
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $texto)) {
            $avisos[] = 'Se ignoró un precio inválido. Usa importes positivos en soles con hasta dos decimales.';
            return '';
        }
        return number_format((float) $texto, 2, '.', '');
    }
}
