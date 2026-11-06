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
        'idlinea',
        'idcanal',
        'idvendedor',
        'idestado',
        'formapago',
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

    public function registrarVenta($dataVenta, $detalles, $pagos, $entrega = null)
    {
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $detVentaModel   = model(DetVentaModel::class);
            $productoModel   = model(ProductoModel::class);
            $pagoVentaModel  = model(PagoVentaModel::class);

            if (empty($dataVenta['idpedido'])) {
                $dataVenta['idpedido'] = null;
            }

            if (empty($pagos)) {
                throw new \RuntimeException('Debe registrar al menos una forma de pago.');
            }

            if (empty($detalles) || empty($dataVenta['idlocal'])) {
                throw new \RuntimeException('Debe indicar productos y almacén.');
            }
            if ($entrega === null) {
                throw new \RuntimeException('La venta requiere una entrega para gestionar sus reservas.');
            }

            // Expandir combos en el servidor: nunca confiar en componentes enviados por el navegador.
            $cantidades = [];
            $seriesSolicitadas = [];
            $seriesPorDetalle = [];
            $componentesPorDetalle = [];
            foreach ($detalles as $indice => $item) {
                $idproducto = (int) ($item['idproducto'] ?? 0);
                $cantidad = (float) ($item['cantidad'] ?? 0);
                if ($idproducto <= 0 || $cantidad <= 0 || round($cantidad, 2) != $cantidad) {
                    throw new \RuntimeException('Detalle de venta con cantidad inválida.');
                }
                $producto = $productoModel->find($idproducto);
                if (!$producto) {
                    throw new \RuntimeException('Producto inexistente: ' . $idproducto);
                }
                $componentes = [];
                if ($producto['tipo'] === 'COMBO') {
                    $filas = $db->table('combo_detalle')->where('idcombo', $idproducto)->get()->getResultArray();
                    if (!$filas) {
                        throw new \RuntimeException('El combo no tiene componentes configurados.');
                    }
                    foreach ($filas as $fila) {
                        $idComponente = (int) $fila['idproducto'];
                        $componente = $productoModel->find($idComponente);
                        if (!$componente || $componente['tipo'] === 'COMBO') {
                            throw new \RuntimeException('El combo contiene un componente inexistente o un combo anidado.');
                        }
                        $componentes[] = ['producto' => $componente, 'cantidad' => round($cantidad * (float) $fila['cantidad'], 2)];
                    }
                } else {
                    $componentes[] = ['producto' => $producto, 'cantidad' => $cantidad];
                }
                foreach ($componentes as $componenteFila) {
                    $p = $componenteFila['producto'];
                    $idFisico = (int) $p['idproducto'];
                    $cantidadFisica = $componenteFila['cantidad'];
                    if ($cantidadFisica <= 0) {
                        throw new \RuntimeException('Cantidad de componente inválida.');
                    }
                    if ($p['tipo'] === 'SERVICIO' && (!empty($p['controlastock']) || !empty($p['controlaserie']))) {
                        throw new \RuntimeException('Un servicio no puede controlar stock ni serie.');
                    }
                    if (!empty($p['controlastock'])) {
                        $cantidades[$idFisico] = ($cantidades[$idFisico] ?? 0) + $cantidadFisica;
                    }
                    if (!empty($p['controlaserie'])) {
                        if (empty($p['controlastock']) || (int) $cantidadFisica != $cantidadFisica) {
                            throw new \RuntimeException('Los componentes con serie requieren unidades enteras y stock.');
                        }
                        $ids = $producto['tipo'] === 'COMBO'
                            ? (array) ($item['series_componentes'][$idFisico] ?? [])
                            : (array) ($item['series'] ?? []);
                        $ids = array_map('intval', $ids);
                        if (count($ids) !== (int) $cantidadFisica || in_array(0, $ids, true)) {
                            throw new \RuntimeException('Seleccione una serie por unidad del producto ' . $idFisico);
                        }
                        $seriesSolicitadas[$idFisico] = array_merge($seriesSolicitadas[$idFisico] ?? [], $ids);
                        $seriesPorDetalle[$indice][$idFisico] = $ids;
                    }
                    if ($producto['tipo'] === 'COMBO') {
                        $componentesPorDetalle[$indice][] = [
                            'idproducto' => $idFisico,
                            'cantidad' => $cantidadFisica,
                        ];
                    }
                }
            }
            $idsTodas = [];
            foreach ($seriesSolicitadas as $idproducto => $ids) {
                if (count($ids) !== count(array_unique($ids))) {
                    throw new \RuntimeException('Hay números de serie repetidos en el producto ' . $idproducto);
                }
                foreach ($ids as $id) {
                    if (isset($idsTodas[$id])) throw new \RuntimeException('Una serie se seleccionó más de una vez.');
                    $idsTodas[$id] = true;
                    $serie = $db->query(
                        'SELECT idserie FROM series WHERE idserie = ? AND idproducto = ? AND idlocal = ? AND estado = ? AND iddetcompra IS NOT NULL FOR UPDATE',
                        [$id, $idproducto, $dataVenta['idlocal'], 'EN_STOCK']
                    )->getRowArray();
                    if (!$serie) throw new \RuntimeException('La serie ' . $id . ' ya no está disponible en este local.');
                }
            }
            ksort($cantidades, SORT_NUMERIC);
            $reservasPendientes = [];
            foreach ($cantidades as $idproducto => $cantidad) {
                $fila = $db->query(
                    'SELECT idkardex, stockactual FROM kardex WHERE idproducto = ? AND idlocal = ? FOR UPDATE',
                    [$idproducto, $dataVenta['idlocal']]
                )->getRowArray();
                if (!$fila) {
                    throw new \RuntimeException('No existe Kardex para el producto ' . $idproducto);
                }
                $totalReservado = $db->table('reservas_stock')
                    ->selectSum('cantidad', 'total')
                    ->where('idkardex', $fila['idkardex'])
                    ->where('estado', 'RESERVADO')
                    ->get()->getRow('total');
                $disponible = round((float)$fila['stockactual'] - (float)$totalReservado, 2);
                if ($disponible + 0.00001 < $cantidad) {
                    throw new \RuntimeException("Stock disponible insuficiente para producto {$idproducto}. Disponible: {$disponible}; solicitado: {$cantidad}.");
                }
                $reservasPendientes[] = ['idkardex' => $fila['idkardex'], 'cantidad' => $cantidad];
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

                if ($montoPagado <= 0) {
                    throw new \RuntimeException('Los pagos deben ser mayores que cero.');
                }
                $idPago = $pagoVentaModel->insert([
                    'idventa'       => $idventa,
                    'idtipopago'    => $pago['idtipopago'],
                    'idusuariocobro' => $dataVenta['idcajero'],
                    'monto'         => $montoPagado,
                    'referencia'    => $pago['referencia'] ?? null,
                    'montorecibido' => $pago['montorecibido'] ?? $montoPagado,
                    'vuelto'        => $pago['vuelto'] ?? 0.00,
                    'fecha'         => date('Y-m-d H:i:s'),
                ]);
                if (!$idPago) {
                    throw new \RuntimeException('No se pudo registrar el pago.');
                }
            }

            if (round($totalPagado, 2) !== round((float)$dataVenta['total'], 2)) {
                throw new \RuntimeException('En una venta de contado, los pagos deben coincidir con el total de la venta.');
            }

            foreach ($detalles as $indice => $item) {
                $idproducto  = $item['idproducto'];
                $cantidad    = (float)$item['cantidad'];
                $precioventa = (float)$item['precioventa'];

                $producto = $productoModel->find($idproducto);
                $costoUnitario = $producto['preciocosto'] ?? 0;

                $idDetalle = $detVentaModel->insert([
                    'idventa'       => $idventa,
                    'idproducto'    => $idproducto,
                    'cantidad'      => $cantidad,
                    'precioventa'   => $precioventa,
                    'costounitario' => $costoUnitario,
                    'garantiameses' => $producto['garantiameses'] ?? 0
                ]);
                if (!$idDetalle) {
                    throw new \RuntimeException('No se pudo registrar el detalle de venta.');
                }
                foreach ($componentesPorDetalle[$indice] ?? [] as $componente) {
                    if (!$db->table('venta_combo_componentes')->insert([
                        'iddetventa' => $idDetalle,
                        'idproducto' => $componente['idproducto'],
                        'cantidad' => $componente['cantidad'],
                    ])) {
                        throw new \RuntimeException('No se pudo registrar el componente del combo.');
                    }
                }
                foreach ($seriesPorDetalle[$indice] ?? [] as $idFisico => $ids) {
                    foreach ($ids as $idSerie) {
                        $actualizado = $db->table('series')
                            ->where('idproducto', $idFisico)
                            ->where('idserie', $idSerie)
                            ->where('estado', 'EN_STOCK')
                            ->update(['estado' => 'RESERVADO', 'iddetventa' => $idDetalle]);
                        if (!$actualizado || $db->affectedRows() !== 1) {
                            throw new \RuntimeException('No se pudo reservar la serie ' . $idSerie);
                        }
                    }
                }
            }

            foreach ($reservasPendientes as $reserva) {
                $reserva['idventa'] = $idventa;
                $reserva['estado'] = 'RESERVADO';
                $reserva['created_at'] = date('Y-m-d H:i:s');
                if (!$db->table('reservas_stock')->insert($reserva)) {
                    throw new \RuntimeException('No se pudo guardar la reserva de inventario.');
                }
            }

            if ($entrega !== null) {
                $tipo = $entrega['tipoentrega'] ?? 'RECOJO_TIENDA';
                if (!in_array($tipo, ['RECOJO_TIENDA', 'DOMICILIO'], true)) {
                    throw new \RuntimeException('Tipo de entrega inválido.');
                }
                if ($tipo === 'DOMICILIO') {
                    if (
                        empty(trim((string) ($entrega['direccion'] ?? ''))) ||
                        empty(trim((string) ($entrega['telefono'] ?? '')))
                    ) {
                        throw new \RuntimeException(
                            'La entrega a domicilio requiere dirección y teléfono.'
                        );
                    }
                }
                $datosEntrega = [
                    'idventa' => $idventa,
                    'tipoentrega' => $tipo,
                    'direccion' => $tipo === 'DOMICILIO' ? $entrega['direccion'] : null,
                    'referencia' => $tipo === 'DOMICILIO' ? ($entrega['referencia'] ?? null) : null,
                    'telefono' => $tipo === 'DOMICILIO' ? $entrega['telefono'] : null,
                    'idtecnico' => $tipo === 'DOMICILIO' ? $entrega['idtecnico'] : null,
                    'estado' => 'PENDIENTE',
                    'cobrocontraentrega' => 0,
                ];
                if (!model(EntregaModel::class)->insert($datosEntrega)) {
                    throw new \RuntimeException('No se pudo registrar la entrega.');
                }
            }

            if ($db->transStatus() === false || !$db->transCommit()) {
                throw new \RuntimeException('La transacción no pudo confirmarse.');
            }

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();


            session()->setFlashdata('error', $e->getMessage());
            log_message('error', 'Error en VentaModel::registrarVenta: ' . $e->getMessage());
            return false;
        }
    }
}
