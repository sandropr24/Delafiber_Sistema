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
        'cdr',
        'numero',
        'estafacturado',
        'fecha',
        'total'
    ];

    public function registrarVenta($dataVenta, $detalles, $pagos)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $detVentaModel   = model(DetVentaModel::class);
            $kardexModel     = model(KardexModel::class);
            $movimientoModel = model(MovimientoModel::class);
            $productoModel   = model(ProductoModel::class);
            $pagoVentaModel  = model(PagoVentaModel::class);

            if (empty($dataVenta['idpedido'])) {
                $dataVenta['idpedido'] = null;
            }

            if (empty($pagos)) {
                throw new \RuntimeException('Debe registrar al menos una forma de pago.');
            }

            foreach ($detalles as $item) {
                $idproducto = $item['idproducto'];
                $cantidad   = (int)$item['cantidad'];

                $kardexCheck = $kardexModel->where('idproducto', $idproducto)
                    ->where('idlocal', $dataVenta['idlocal'])
                    ->first();

                $stockActual = $kardexCheck ? (int)$kardexCheck['stockactual'] : 0;

                if ($stockActual < $cantidad) {
                    throw new \RuntimeException("Stock insuficiente para el producto ID {$idproducto}. Disponible: {$stockActual}, Solicitado: {$cantidad}.");
                }
            }

            $insertado = $db->table('ventas')->insert($dataVenta);
            
            if (!$insertado) {
                $errorDB = $db->error();
                throw new \RuntimeException('Error SQL MySQL: ' . ($errorDB['message'] ?? 'Desconocido'));
            }

            $idventa = $db->insertID();

            $totalPagado = 0;
            foreach ($pagos as $pago) {
                $montoPagado = (float)$pago['monto'];
                $totalPagado += $montoPagado;

                $pagoVentaModel->insert([
                    'idventa'       => $idventa,
                    'idtipopago'    => $pago['idtipopago'],
                    'monto'         => $montoPagado,
                    'referencia'    => $pago['referencia'] ?? null,
                    'montorecibido' => $pago['montorecibido'] ?? $montoPagado,
                    'vuelto'        => $pago['vuelto'] ?? 0.00,
                    'fecha'         => date('Y-m-d H:i:s'),
                ]);
            }

            if (round($totalPagado, 2) < round($dataVenta['total'], 2)) {
                throw new \RuntimeException('El monto total pagado es menor al total de la venta.');
            }

            foreach ($detalles as $item) {
                $idproducto  = $item['idproducto'];
                $cantidad    = (int)$item['cantidad'];
                $precioventa = (float)$item['precioventa'];

                $producto = $productoModel->find($idproducto);
                $costoUnitario = $producto['costounitario'] ?? ($precioventa * 0.7);

                $detVentaModel->insert([
                    'idventa'       => $idventa,
                    'idproducto'    => $idproducto,
                    'cantidad'      => $cantidad,
                    'precioventa'   => $precioventa,
                    'costounitario' => $costoUnitario
                ]);

                $kardexActual = $kardexModel->where('idproducto', $idproducto)
                    ->where('idlocal', $dataVenta['idlocal'])
                    ->first();

                $stockAnterior = (int)$kardexActual['stockactual'];
                $nuevoStock    = $stockAnterior - $cantidad;
                $idkardex      = $kardexActual['idkardex'];

                $kardexModel->update($idkardex, [
                    'stockactual' => $nuevoStock
                ]);

                $movimientoModel->insert([
                    'idkardex'    => $idkardex,
                    'idventa'     => $idventa,
                    'idusuario'   => $dataVenta['idcajero'] ?? 1,
                    'tipo'        => 'SALIDA',
                    'fecha'       => date('Y-m-d H:i:s'),
                    'cantidad'    => $cantidad,
                    'saldo'       => $nuevoStock,
                    'descripcion' => 'Salida por Venta N° ' . $idventa,
                    'motivo'      => 'Venta de mercadería'
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transacción falló en el commit.');
            }

            return true;
        } catch (\Exception $e) {
            $db->transRollback();


            session()->setFlashdata('error', $e->getMessage());
            log_message('error', 'Error en VentaModel::registrarVenta: ' . $e->getMessage());
            return false;
        }
    }
}