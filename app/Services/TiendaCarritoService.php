<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

class TiendaCarritoService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public static function cantidad($valor): string
    {
        if (!is_scalar($valor) || !preg_match('/^\d{1,4}(?:\.\d{1,2})?$/D', (string) $valor) || (float) $valor <= 0 || (float) $valor > 9999) {
            throw new InvalidArgumentException('La cantidad no es valida.');
        }
        return number_format((float) $valor, 2, '.', '');
    }

    /** Prepara el nuevo carrito sin escribir sesión, stock ni pedidos. */
    public function agregar(array $carrito, int $id, $cantidad): array
    {
        if ($id <= 0) throw new InvalidArgumentException('El artículo no está disponible.');
        $cantidad = self::cantidad($cantidad);
        $carrito[$id] = self::cantidad((float) ($carrito[$id] ?? 0) + (float) $cantidad);
        $resumen = $this->resolver($carrito);
        $producto = null;
        foreach ($resumen['items'] as $item) {
            if ((int) $item['idproducto'] === $id) $producto = $item;
        }
        if (!$producto) throw new InvalidArgumentException('El artículo no está disponible.');
        foreach ($resumen['no_disponibles'] as $noDisponible) unset($carrito[$noDisponible]);

        return [
            'carrito' => $carrito, 'resumen' => $resumen, 'producto' => $producto,
            'cantidad_agregada' => $cantidad, 'retirados' => count($resumen['no_disponibles']),
        ];
    }

    public function resolver(array $carrito): array
    {
        if (!$carrito) {
            return ['items' => [], 'total' => '0.00', 'cantidad' => 0, 'no_disponibles' => []];
        }
        $cantidades = [];
        foreach ($carrito as $id => $cantidad) {
            $id = (int) $id;
            if ($id > 0) {
                $cantidades[$id] = self::cantidad($cantidad);
            }
        }
        if (!$cantidades) {
            return ['items' => [], 'total' => '0.00', 'cantidad' => 0, 'no_disponibles' => []];
        }
        $productos = $this->db->table('productos p')->select('p.idproducto, p.tipo, p.nombre, p.precioventa, p.imagen, p.controlaserie, um.abreviatura AS unidad')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
            ->where('p.estado', 1)->whereIn('p.idproducto', array_keys($cantidades))->get()->getResultArray();
        $items = [];
        $totalCentimos = 0;
        foreach ($productos as $producto) {
            $id = (int) $producto['idproducto'];
            $cantidad = (float) $cantidades[$id];
            if (($producto['tipo'] !== 'PRODUCTO' || strtoupper((string) $producto['unidad']) !== 'M' || (int) $producto['controlaserie'] === 1) && floor($cantidad) !== $cantidad) {
                throw new InvalidArgumentException($producto['nombre'] . ' requiere unidades enteras.');
            }
            $precioCentimos = (int) round((float) $producto['precioventa'] * 100);
            $importeCentimos = (int) round($precioCentimos * (float) $cantidades[$id]);
            $items[] = $producto + [
                'cantidad' => $cantidades[$id],
                'importe' => number_format($importeCentimos / 100, 2, '.', ''),
            ];
            $totalCentimos += $importeCentimos;
        }
        $encontrados = array_map('intval', array_column($productos, 'idproducto'));

        return [
            'items' => $items,
            'total' => number_format($totalCentimos / 100, 2, '.', ''),
            'cantidad' => count($items),
            'no_disponibles' => array_values(array_diff(array_keys($cantidades), $encontrados)),
        ];
    }
}
