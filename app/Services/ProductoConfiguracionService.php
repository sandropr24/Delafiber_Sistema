<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

class ProductoConfiguracionService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /**
     * Debe ser la primera lectura de una transacción nueva de edición.
     * VentaService toma los mismos bloqueos antes de leer la configuración y
     * crear reservas, evitando comprobar pendientes sobre una venta a medias.
     */
    public function validarCambio(int $idProducto, array $datos): array
    {
        $actual = $this->bloquearProducto($idProducto);
        if (!$actual) {
            throw new InvalidArgumentException('El producto ya no existe.');
        }

        $cambio = array_key_exists('tipo', $datos) && $datos['tipo'] !== $actual['tipo'];
        foreach (['controlastock', 'controlaserie'] as $campo) {
            $cambio = $cambio || (array_key_exists($campo, $datos) && (int) $datos[$campo] !== (int) $actual[$campo]);
        }
        if (!$cambio) {
            return $actual;
        }

        // Estas lecturas comparten una instantánea creada DESPUÉS de adquirir
        // el bloqueo del producto. No bloquean ventas/entregas en orden inverso.
        $consultas = [
            ["SELECT r.idreserva FROM reservas_stock r
                JOIN kardex k ON k.idkardex = r.idkardex
                WHERE k.idproducto = ? AND r.estado = 'RESERVADO' LIMIT 1",
                'reservas de inventario pendientes'],
            ["SELECT idserie FROM series
                WHERE idproducto = ? AND estado = 'RESERVADO' LIMIT 1",
                'números de serie reservados'],
            ["SELECT v.idventa FROM detventa d
                JOIN ventas v ON v.idventa = d.idventa
                LEFT JOIN entregas e ON e.idventa = v.idventa
                WHERE d.idproducto = ? AND v.fechaanulacion IS NULL
                  AND (e.identrega IS NULL OR e.estado <> 'ENTREGADO') LIMIT 1",
                'ventas con entrega o resolución comercial pendiente'],
            ["SELECT v.idventa FROM venta_combo_componentes c
                JOIN detventa d ON d.iddetventa = c.iddetventa
                JOIN ventas v ON v.idventa = d.idventa
                LEFT JOIN entregas e ON e.idventa = v.idventa
                WHERE c.idproducto = ? AND v.fechaanulacion IS NULL
                  AND (e.identrega IS NULL OR e.estado <> 'ENTREGADO') LIMIT 1",
                'ventas de combos con entrega o resolución comercial pendiente'],
        ];
        foreach ($consultas as [$sql, $motivo]) {
            if ($this->db->query($sql, [$idProducto])->getRowArray()) {
                throw new InvalidArgumentException(
                    'No se puede cambiar el tipo, el control de stock ni el control de series: el artículo tiene '
                    . $motivo . '. Complete la entrega o la anulación comercial antes de cambiar su configuración. '
                    . 'Puede seguir editando su nombre, descripción e imagen.'
                );
            }
        }

        return $actual;
    }

    /**
     * Se ejecuta dentro de la transacción de venta. Devuelve datos actuales,
     * no lecturas antiguas de la instantánea de MySQL REPEATABLE READ.
     */
    public function bloquearParaVenta(array $detalles): array
    {
        $directos = [];
        foreach ($detalles as $detalle) {
            if (!is_array($detalle) || !is_scalar($detalle['idproducto'] ?? null)
                || !ctype_digit((string) $detalle['idproducto']) || (int) $detalle['idproducto'] < 1) {
                throw new InvalidArgumentException('El artículo de la venta no es válido.');
            }
            $directos[(int) $detalle['idproducto']] = true;
        }
        if (!$directos) {
            throw new InvalidArgumentException('Agregue al menos un artículo a la venta.');
        }
        ksort($directos, SORT_NUMERIC);

        // Descubrimiento previo para bloquear productos y componentes en un
        // único orden, incluso si se venden juntos un combo y un componente.
        $marcas = implode(', ', array_fill(0, count($directos), '?'));
        $filas = $this->db->query(
            "SELECT DISTINCT idproducto FROM combo_detalle WHERE idcombo IN ({$marcas})",
            array_keys($directos)
        )->getResultArray();
        $ids = $directos;
        foreach ($filas as $fila) {
            $ids[(int) $fila['idproducto']] = true;
        }
        ksort($ids, SORT_NUMERIC);

        $productos = [];
        foreach (array_keys($ids) as $id) {
            $producto = $this->bloquearProducto($id);
            if ($producto) {
                $productos[$id] = $producto;
            } elseif (isset($directos[$id])) {
                throw new InvalidArgumentException('Un artículo de la venta ya no existe.');
            }
        }

        $componentes = [];
        foreach (array_keys($directos) as $id) {
            if ($productos[$id]['tipo'] !== 'COMBO') {
                continue;
            }
            $componentes[$id] = $this->db->query($this->lock(
                'SELECT idproducto, cantidad FROM combo_detalle WHERE idcombo = ? ORDER BY idproducto'
            ), [$id])->getResultArray();
            foreach ($componentes[$id] as $componente) {
                if (!isset($productos[(int) $componente['idproducto']])) {
                    // Cambió la composición durante el descubrimiento. No
                    // adquirir nuevos bloqueos fuera del orden establecido.
                    throw new InvalidArgumentException('La configuración del combo cambió. Revise los artículos y vuelva a registrar la venta.');
                }
            }
        }

        $unidades = array_values(array_unique(array_column($productos, 'idunidad')));
        $marcas = implode(', ', array_fill(0, count($unidades), '?'));
        $filas = $this->db->query(
            "SELECT idunidad, abreviatura FROM unidadesmedida WHERE idunidad IN ({$marcas})",
            $unidades
        )->getResultArray();
        $porUnidad = array_column($filas, 'abreviatura', 'idunidad');
        foreach ($productos as &$producto) {
            $producto['unidad'] = $porUnidad[$producto['idunidad']] ?? null;
        }
        unset($producto);

        return ['productos' => $productos, 'componentes' => $componentes];
    }

    private function bloquearProducto(int $id): ?array
    {
        return $this->db->query($this->lock('SELECT * FROM productos WHERE idproducto = ?'), [$id])->getRowArray();
    }

    private function lock(string $sql): string
    {
        return $this->db->DBDriver === 'MySQLi' ? $sql . ' FOR UPDATE' : $sql;
    }
}
