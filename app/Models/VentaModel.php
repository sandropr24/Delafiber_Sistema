<?php

namespace App\Models;

use CodeIgniter\Model;

class VentaModel extends Model
{
    protected $table            = 'ventas';
    protected $primaryKey       = 'idventa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'idcliente',
        'idlocal',
        'idpedido',
        'idcajero',
        'tipodocumento',
        'serie',
        'numero',
        'estafacturado',
        'fecha',
        'total'
    ];

    public function registrarVenta($dataVenta, $detalles)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $detVentaModel   = model(DetVentaModel::class);
            $kardexModel     = model(KardexModel::class);
            $movimientoModel = model(MovimientoModel::class);
            $productoModel   = model(ProductoModel::class);

            if (empty($dataVenta['idpedido'])) {
                $dataVenta['idpedido'] = null;
            }


            $this->insert($dataVenta);
            $idventa = $this->insertID();

            if (!$idventa) {
                throw new \RuntimeException('Error al registrar la cabecera de la venta.');
            }

            foreach ($detalles as $item) {
                $idproducto = $item['idproducto'];
                $cantidad   = (int)$item['cantidad'];
                $precioventa = (float)$item['precioventa'];

                $producto = $productoModel->find($idproducto);
                $costoUnitario = $producto['costounitario'] ?? ($precioventa * 0.7);

                $detInserted = $detVentaModel->insert([
                    'idventa'       => $idventa,
                    'idproducto'    => $idproducto,
                    'cantidad'      => $cantidad,
                    'precioventa'   => $precioventa,
                    'costounitario' => $costoUnitario
                ]);

                if (!$detInserted) {
                    throw new \RuntimeException('Error al registrar el detalle para el producto ID ' . $idproducto);
                }

                $kardexActual = $kardexModel->where('idproducto', $idproducto)
                    ->where('idlocal', $dataVenta['idlocal'])
                    ->first();

                $stockAnterior = $kardexActual ? (int)$kardexActual['stockactual'] : 0;
                $nuevoStock    = $stockAnterior - $cantidad;
                $costoPromedio = $kardexActual ? (float)$kardexActual['costopromedio'] : ($producto['costounitario'] ?? 0.00);

                if ($kardexActual) {
                    $idkardex = $kardexActual['idkardex'];
                    $kardexModel->update($idkardex, [
                        'stockactual' => $nuevoStock
                    ]);
                } else {
                    $idkardex = $kardexModel->insert([
                        'idproducto'    => $idproducto,
                        'idlocal'       => $dataVenta['idlocal'],
                        'stockactual'   => $nuevoStock,
                        'costopromedio' => $costoPromedio,
                        'minima'        => 5,
                        'maxima'        => 100
                    ]);

                    if (!$idkardex) {
                        throw new \RuntimeException('Error al inicializar el Kardex para el producto ID ' . $idproducto);
                    }
                }

                $movInserted = $movimientoModel->insert([
                    'idkardex'    => $idkardex,
                    'idventa'     => $idventa,
                    'idusuario'   => $dataVenta['idcajero'] ?? 1,
                    'tipo'        => 'SALIDA',
                    'fecha'       => date('Y-m-d H:i:s'),
                    'cantidad'    => $cantidad,
                    'saldo'       => $nuevoStock,
                    'descripcion' => 'Salida por Venta N° ' . $idventa . ' (' . $dataVenta['serie'] . '-' . $dataVenta['numero'] . ')',
                    'motivo'      => 'Venta de mercadería'
                ]);

                if (!$movInserted) {
                    throw new \RuntimeException('Error al registrar el movimiento de salida para el producto ID ' . $idproducto);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transacción de la venta falló en el commit.');
            }

            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error en VentaModel::registrarVenta: ' . $e->getMessage());
            return false;
        }
    }
}
