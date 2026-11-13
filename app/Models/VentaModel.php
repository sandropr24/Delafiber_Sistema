<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\DescuentoVenta;

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
        'subtotal',
        'descuento',
        'tipodescuento',
        'valordescuento',
        'motivodescuento',
        'total'
    ];

    public function registrarVenta($dataVenta, $detalles, $pagos, $entrega = null)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $token = (string) ($dataVenta['request_token'] ?? '');
            if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
                throw new \InvalidArgumentException('Recargue la página para iniciar una nueva venta.');
            }
            $db->query('SELECT idusuario FROM usuarios WHERE idusuario = ? FOR UPDATE', [(int) $dataVenta['idvendedor']]);
            if (!empty($dataVenta['idpedido'])) {
                if (!ctype_digit((string) $dataVenta['idpedido'])) throw new \InvalidArgumentException('El pedido de origen no es válido.');
                $cotizacionPedido = $db->table('pedido')->where('idpedido', (int) $dataVenta['idpedido'])->get()->getRow('idcotizacion');
                if (!empty($dataVenta['idcotizacion']) && (int) $dataVenta['idcotizacion'] !== (int) $cotizacionPedido) throw new \InvalidArgumentException('La cotización no corresponde al pedido.');
                $dataVenta['idcotizacion'] = $cotizacionPedido ?: null;
            } else $dataVenta['idcotizacion'] = $dataVenta['idcotizacion'] ?? null;
            $anterior = $db->table('ventas')->where('request_token', $token)->get()->getRowArray();
            if ($anterior) {
                if ((int) ($anterior['idcotizacion'] ?? 0) !== (int) ($dataVenta['idcotizacion'] ?? 0)) throw new \InvalidArgumentException('Esta solicitud pertenece a otra cotización.');
                if ((int) ($anterior['idpedido'] ?? 0) !== (int) ($dataVenta['idpedido'] ?? 0)) {
                    throw new \InvalidArgumentException('Esta solicitud ya pertenece a otra venta u otro pedido.');
                }
                if ((int) $anterior['idvendedor'] !== (int) $dataVenta['idvendedor']) {
                    throw new \InvalidArgumentException('La solicitud de venta no pertenece a este usuario.');
                }
                $db->transRollback();
                return true;
            }
            $cliente = $db->table('personas')->where('idpersona', $dataVenta['idcliente'])->where('estado', 1)->get()->getRowArray();
            if (!$cliente) throw new \InvalidArgumentException('Seleccione un cliente activo.');
            foreach (['idlocal' => ['locales', 'idlocal'], 'idlinea' => ['lineas', 'idlinea'], 'idcanal' => ['canalesventa', 'idcanal'], 'idestado' => ['estadoventa', 'idestado']] as $campo => [$tabla, $clave]) {
                if (!$db->table($tabla)->where($clave, (int) ($dataVenta[$campo] ?? 0))->countAllResults()) {
                    throw new \InvalidArgumentException('Revise el local, línea, canal y estado seleccionados.');
                }
            }
            if (($dataVenta['tipodocumento'] ?? '') !== 'NOTA_VENTA') throw new \InvalidArgumentException('Seleccione nota de venta.');
            $rolVendedor = $db->table('usuarios u')->select('r.rol')->join('roles r', 'r.idrol = u.idrol')->where('u.idusuario', (int) $dataVenta['idvendedor'])->get()->getRow('rol');
            foreach ($detalles as &$detalleSeguro) {
                if (!is_array($detalleSeguro)) throw new \InvalidArgumentException('Detalle de venta inválido.');
                $articulo = $db->table('productos p')->select('p.*, um.abreviatura AS unidad')
                    ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
                    ->where('p.idproducto', (int) ($detalleSeguro['idproducto'] ?? 0))->get()->getRowArray();
                if (!$articulo || (int) $articulo['estado'] !== 1) {
                    throw new \InvalidArgumentException('Un artículo ya no está activo. Retírelo del carrito.');
                }
                $cantidad = $detalleSeguro['cantidad'] ?? '';
                if (!is_scalar($cantidad) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $cantidad) || (float) $cantidad <= 0) {
                    throw new \InvalidArgumentException('Ingrese una cantidad positiva con hasta dos decimales.');
                }
                if ((strtoupper($articulo['unidad'] ?? '') !== 'M' || $articulo['tipo'] !== 'PRODUCTO' || !empty($articulo['controlaserie'])) && floor((float) $cantidad) != (float) $cantidad) {
                    throw new \InvalidArgumentException($articulo['nombre'] . ' requiere unidades enteras.');
                }
                $precioSolicitado = $detalleSeguro['precioventa'] ?? $articulo['precioventa'];
                $precioCentimos = \App\Libraries\CobroVenta::centimos($precioSolicitado);
                if ($precioCentimos <= 0) throw new \InvalidArgumentException('El precio debe ser mayor que cero.');
                if ($precioCentimos < \App\Libraries\CobroVenta::centimos($articulo['precioventa']) && $rolVendedor !== 'Administrador') {
                    throw new \InvalidArgumentException('Solo un administrador puede reducir el precio de catálogo.');
                }
                $detalleSeguro['precioventa'] = number_format($precioCentimos / 100, 2, '.', '');
            }
            unset($detalleSeguro);
            $detVentaModel  = model(DetVentaModel::class);
            $productoModel  = model(ProductoModel::class);
            $pagoVentaModel = model(PagoVentaModel::class);

            if (empty($dataVenta['idpedido'])) {
                $dataVenta['idpedido'] = null;
            }
            $pedidoOrigen = null;
            if ($dataVenta['idpedido'] !== null) {
                if (!ctype_digit((string) $dataVenta['idpedido']) || (int) $dataVenta['idpedido'] < 1) throw new \InvalidArgumentException('El pedido de origen no es válido.');
                $pedidoOrigen = model(PedidoModel::class)->validarConversion($dataVenta, $detalles);
            }
            $cotizacionOrigen = null;
            if ($dataVenta['idcotizacion'] !== null) {
                if (!ctype_digit((string) $dataVenta['idcotizacion']) || (int) $dataVenta['idcotizacion'] < 1) throw new \InvalidArgumentException('La cotización de origen no es válida.');
                $cotizacionOrigen = model(CotizacionModel::class)->validarParaVenta($dataVenta, $pedidoOrigen);
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

            $importes = DescuentoVenta::calcular(
                $detalles,
                (string) ($dataVenta['tipodescuento'] ?? 'NINGUNO'),
                $dataVenta['valordescuento'] ?? '0',
                (string) ($dataVenta['motivodescuento'] ?? '')
            );

            $dataVenta = array_merge($dataVenta, $importes);
            if ($cotizacionOrigen) $dataVenta = array_merge($dataVenta, \App\Libraries\ImportesCotizacion::desglosar($importes['total'], $cotizacionOrigen['porcentajeigv']), ['porcentajeigv' => $cotizacionOrigen['porcentajeigv'], 'tipocambio' => $cotizacionOrigen['tipocambio']]);

            if ($importes['tipodescuento'] !== 'NINGUNO') {
                $rol = $db->table('usuarios u')
                    ->select('roles.rol')
                    ->join('roles', 'roles.idrol = u.idrol')
                    ->where('u.idusuario', (int) ($dataVenta['idvendedor'] ?? 0))
                    ->where('u.estado', 1)
                    ->get()
                    ->getRow('rol');

                if ($rol !== 'Administrador') {
                    throw new \InvalidArgumentException(
                        'Solo un administrador puede aplicar descuentos.'
                    );
                }
            }

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
                    $filas = $db->table('combo_detalle')
                        ->where('idcombo', $idproducto)
                        ->get()
                        ->getResultArray();

                    if (!$filas) {
                        throw new \RuntimeException('El combo no tiene componentes configurados.');
                    }

                    foreach ($filas as $fila) {
                        $idComponente = (int) $fila['idproducto'];
                        $componente = $db->table('productos p')->select('p.*, um.abreviatura AS unidad')
                            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
                            ->where('p.idproducto', $idComponente)->get()->getRowArray();

                        if (!$componente || (int) $componente['estado'] !== 1 || $componente['tipo'] === 'COMBO') {
                            throw new \InvalidArgumentException(
                                'El combo contiene un componente inactivo, inexistente o un combo anidado. Revise su configuración.'
                            );
                        }

                        $cantidadComponente = round($cantidad * (float) $fila['cantidad'], 2);
                        if (strtoupper($componente['unidad'] ?? '') !== 'M' && floor($cantidadComponente) != $cantidadComponente) {
                            throw new \InvalidArgumentException($componente['nombre'] . ' requiere unidades enteras dentro del combo.');
                        }
                        $componentes[] = [
                            'producto' => $componente,
                            'cantidad' => $cantidadComponente
                        ];
                    }
                } else {
                    $componentes[] = [
                        'producto' => $producto,
                        'cantidad' => $cantidad
                    ];
                }

                foreach ($componentes as $componenteFila) {
                    $p = $componenteFila['producto'];
                    $idFisico = (int) $p['idproducto'];
                    $cantidadFisica = $componenteFila['cantidad'];

                    if ($cantidadFisica <= 0) {
                        throw new \RuntimeException('Cantidad de componente inválida.');
                    }

                    if (
                        $p['tipo'] === 'SERVICIO' &&
                        (!empty($p['controlastock']) || !empty($p['controlaserie']))
                    ) {
                        throw new \RuntimeException(
                            'Un servicio no puede controlar stock ni serie.'
                        );
                    }

                    if (!empty($p['controlastock'])) {
                        $cantidades[$idFisico] = ($cantidades[$idFisico] ?? 0) + $cantidadFisica;
                    }

                    if (!empty($p['controlaserie'])) {
                        if (
                            empty($p['controlastock']) ||
                            (int) $cantidadFisica != $cantidadFisica
                        ) {
                            throw new \RuntimeException(
                                'Los componentes con serie requieren unidades enteras y stock.'
                            );
                        }

                        $ids = $producto['tipo'] === 'COMBO'
                            ? (array) ($item['series_componentes'][$idFisico] ?? [])
                            : (array) ($item['series'] ?? []);

                        $ids = array_map('intval', $ids);

                        if (
                            count($ids) !== (int) $cantidadFisica ||
                            in_array(0, $ids, true)
                        ) {
                            throw new \RuntimeException(
                                'Seleccione una serie por unidad del producto ' . $idFisico
                            );
                        }

                        $seriesSolicitadas[$idFisico] = array_merge(
                            $seriesSolicitadas[$idFisico] ?? [],
                            $ids
                        );

                        $seriesPorDetalle[$indice][$idFisico] = $ids;
                    }

                    if ($producto['tipo'] === 'COMBO') {
                        $componentesPorDetalle[$indice][] = [
                            'idproducto' => $idFisico,
                            'cantidad' => $cantidadFisica
                        ];
                    }
                }
            }

            $idsTodas = [];

            foreach ($seriesSolicitadas as $idproducto => $ids) {
                if (count($ids) !== count(array_unique($ids))) {
                    throw new \RuntimeException(
                        'Hay números de serie repetidos en el producto ' . $idproducto
                    );
                }

                foreach ($ids as $id) {
                    if (isset($idsTodas[$id])) {
                        throw new \RuntimeException(
                            'Una serie se seleccionó más de una vez.'
                        );
                    }

                    $idsTodas[$id] = true;

                    $serie = $db->query(
                        'SELECT idserie FROM series
                         WHERE idserie = ?
                         AND idproducto = ?
                         AND idlocal = ?
                         AND estado = ?
                         AND iddetcompra IS NOT NULL
                         FOR UPDATE',
                        [$id, $idproducto, $dataVenta['idlocal'], 'EN_STOCK']
                    )->getRowArray();

                    if (!$serie) {
                        throw new \RuntimeException(
                            'La serie ' . $id . ' ya no está disponible en este local.'
                        );
                    }
                }
            }

            ksort($cantidades, SORT_NUMERIC);
            $reservasPendientes = [];

            foreach ($cantidades as $idproducto => $cantidad) {
                $fila = $db->query(
                    'SELECT idkardex, stockactual
                     FROM kardex
                     WHERE idproducto = ? AND idlocal = ?
                     FOR UPDATE',
                    [$idproducto, $dataVenta['idlocal']]
                )->getRowArray();

                if (!$fila) {
                    throw new \RuntimeException(
                        'No existe Kardex para el producto ' . $idproducto
                    );
                }

                $totalReservado = $db->table('reservas_stock')
                    ->selectSum('cantidad', 'total')
                    ->where('idkardex', $fila['idkardex'])
                    ->where('estado', 'RESERVADO')
                    ->get()
                    ->getRow('total');

                $disponible = round(
                    (float) $fila['stockactual'] - (float) $totalReservado,
                    2
                );

                if ($disponible + 0.00001 < $cantidad) {
                    $nombreArticulo = $productoModel->find($idproducto)['nombre'] ?? (string) $idproducto;
                    throw new \InvalidArgumentException(
                        "Stock disponible insuficiente para {$nombreArticulo}. " .
                        "Disponible: {$disponible}; solicitado: {$cantidad}."
                    );
                }

                $reservasPendientes[] = [
                    'idkardex' => $fila['idkardex'],
                    'cantidad' => $cantidad
                ];
            }

            // Numeración única y correlativa bajo bloqueo, sin depender del formulario.
            $db->query("INSERT INTO correlativos (tipodocumento, prefijo, anio, ultimonumero) VALUES ('NOTA_VENTA', 'NV01', 0, 0) ON DUPLICATE KEY UPDATE idcorrelativo = idcorrelativo");
            $correlativo = $db->query("SELECT * FROM correlativos WHERE tipodocumento = 'NOTA_VENTA' AND prefijo = 'NV01' AND anio = 0 FOR UPDATE")->getRowArray();
            $maximo = $db->query("SELECT MAX(CAST(numero AS UNSIGNED)) AS maximo FROM ventas WHERE tipodocumento = 'NOTA_VENTA' AND serie = 'NV01'")->getRow('maximo');
            $siguiente = max((int) $correlativo['ultimonumero'], (int) $maximo) + 1;
            $db->table('correlativos')->where('idcorrelativo', $correlativo['idcorrelativo'])->update(['ultimonumero' => $siguiente]);
            $dataVenta['serie'] = 'NV01';
            $dataVenta['numero'] = str_pad((string) $siguiente, 8, '0', STR_PAD_LEFT);
            $insertado = $db->table('ventas')->insert($dataVenta);

            if (!$insertado) {
                $errorDB = $db->error();

                throw new \RuntimeException(
                    'Error SQL MySQL: ' . ($errorDB['message'] ?? 'Desconocido')
                );
            }

            $idventa = $db->insertID();

            $totalPagado = 0;

            foreach ($pagos as $pago) {
                if (!is_array($pago)) throw new \InvalidArgumentException('Pago inválido.');
                $metodo = $db->table('tipospago')->where('idtipopago', (int) ($pago['idtipopago'] ?? 0))->get()->getRow('tipopago');
                if (!$metodo) throw new \InvalidArgumentException('Seleccione un método de pago válido.');
                $pago = array_merge($pago, \App\Libraries\CobroVenta::normalizar($pago, $metodo));
                $montoPagado = (float) $pago['monto'];
                $totalPagado += \App\Libraries\CobroVenta::centimos($pago['monto']);

                if ($montoPagado <= 0) {
                    throw new \RuntimeException(
                        'Los pagos deben ser mayores que cero.'
                    );
                }

                $idPago = $pagoVentaModel->insert([
                    'idventa' => $idventa,
                    'idtipopago' => $pago['idtipopago'],
                    'idusuariocobro' => $dataVenta['idcajero'],
                    'monto' => $montoPagado,
                    'referencia' => $pago['referencia'] ?? null,
                    'montorecibido' => $pago['montorecibido'] ?? $montoPagado,
                    'vuelto' => $pago['vuelto'] ?? 0.00,
                    'fecha' => date('Y-m-d H:i:s')
                ]);

                if (!$idPago) {
                    throw new \RuntimeException('No se pudo registrar el pago.');
                }
            }

            if ($totalPagado !== \App\Libraries\CobroVenta::centimos($dataVenta['total'])) {
                throw new \InvalidArgumentException(
                    'En una venta de contado, los pagos deben coincidir con el total de la venta.'
                );
            }

            foreach ($detalles as $indice => $item) {
                $idproducto = $item['idproducto'];
                $cantidad = (float) $item['cantidad'];
                $precioventa = (float) $item['precioventa'];

                $producto = $productoModel->find($idproducto);
                $costoUnitario = $producto['preciocosto'] ?? 0;

                $idDetalle = $detVentaModel->insert([
                    'idventa' => $idventa,
                    'idproducto' => $idproducto,
                    'cantidad' => $cantidad,
                    'precioventa' => $precioventa,
                    'costounitario' => $costoUnitario,
                    'garantiameses' => $producto['garantiameses'] ?? 0
                ]);

                if (!$idDetalle) {
                    throw new \RuntimeException(
                        'No se pudo registrar el detalle de venta.'
                    );
                }

                foreach ($componentesPorDetalle[$indice] ?? [] as $componente) {
                    if (!$db->table('venta_combo_componentes')->insert([
                        'iddetventa' => $idDetalle,
                        'idproducto' => $componente['idproducto'],
                        'cantidad' => $componente['cantidad']
                    ])) {
                        throw new \RuntimeException(
                            'No se pudo registrar el componente del combo.'
                        );
                    }
                }

                foreach ($seriesPorDetalle[$indice] ?? [] as $idFisico => $ids) {
                    foreach ($ids as $idSerie) {
                        $actualizado = $db->table('series')
                            ->where('idproducto', $idFisico)
                            ->where('idserie', $idSerie)
                            ->where('estado', 'EN_STOCK')
                            ->update([
                                'estado' => 'RESERVADO',
                                'iddetventa' => $idDetalle
                            ]);

                        if (!$actualizado || $db->affectedRows() !== 1) {
                            throw new \RuntimeException(
                                'No se pudo reservar la serie ' . $idSerie
                            );
                        }
                    }
                }
            }

            foreach ($reservasPendientes as $reserva) {
                $reserva['idventa'] = $idventa;
                $reserva['estado'] = 'RESERVADO';
                $reserva['created_at'] = date('Y-m-d H:i:s');

                if (!$db->table('reservas_stock')->insert($reserva)) {
                    throw new \RuntimeException(
                        'No se pudo guardar la reserva de inventario.'
                    );
                }
            }

            // Registrar entrega.
            if ($entrega !== null) {
                $tipo = $entrega['tipoentrega'] ?? 'RECOJO_TIENDA';

                if (!in_array($tipo, ['RECOJO_TIENDA', 'DOMICILIO'], true)) {
                    throw new \RuntimeException('Tipo de entrega inválido.');
                }

                if ($tipo === 'DOMICILIO') {
                    if (!preg_match('/^[0-9]{9}$/D', (string) ($entrega['telefono'] ?? ''))) {
                        throw new \InvalidArgumentException('El teléfono de entrega debe tener 9 dígitos.');
                    }
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
                    'direccion' => $tipo === 'DOMICILIO'
                        ? $entrega['direccion']
                        : null,
                    'referencia' => $tipo === 'DOMICILIO'
                        ? ($entrega['referencia'] ?? null)
                        : null,
                    'telefono' => $tipo === 'DOMICILIO'
                        ? $entrega['telefono']
                        : null,
                    'idtecnico' => $tipo === 'DOMICILIO'
                        ? $entrega['idtecnico']
                        : null,
                    'estado' => 'PENDIENTE',
                    'cobrocontraentrega' => 0
                ];

                if (!model(EntregaModel::class)->insert($datosEntrega)) {
                    throw new \RuntimeException('No se pudo registrar la entrega.');
                }
            }

            if ($pedidoOrigen) {
                model(PedidoModel::class)->marcarConvertido((int) $pedidoOrigen['idpedido'], (int) $idventa, (int) $dataVenta['idvendedor']);
            }
            if ($cotizacionOrigen) model(CotizacionModel::class)->marcarVenta((int) $cotizacionOrigen['idcotizacion'], (int) $idventa, (int) $dataVenta['idvendedor']);
            if ($db->transStatus() === false || !$db->transCommit()) {
                throw new \RuntimeException(
                    'La transacción no pudo confirmarse.'
                );
            }

            return true;

        } catch (\InvalidArgumentException $e) {
            $db->transRollback();

            log_message('warning', 'Validación de venta: ' . $e->getMessage());
            session()->setFlashdata('error', $e->getMessage());

            return false;

        } catch (\Throwable $e) {
            $db->transRollback();

            log_message('error', 'Error en VentaModel::registrarVenta: ' . $e->getMessage());
            session()->setFlashdata(
                'error',
                'No se pudo registrar la venta. Inténtelo nuevamente.'
            );

            return false;
        }
    }
}
