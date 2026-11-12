<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompraModel;
use App\Models\DeCompraModel;
use App\Models\KardexModel;
use App\Models\LocalModel;
use App\Models\MovimientoModel;
use App\Models\ProductoModel;
use App\Models\ProveedorModel;

class CompraController extends BaseController
{
    public function __construct()
    {
        helper(['url', 'form']);
    }


    public function index()
    {
        $compraModel = model(CompraModel::class);

        $data = [
            'titulo'  => 'Listado de Compras',
            'compras' => $compraModel->select('compras.*, proveedores.razonsocial as proveedor')
                ->join('proveedores', 'proveedores.idproveedor = compras.idproveedor', 'left')
                ->orderBy('compras.fechacompra', 'DESC')
                ->findAll()
        ];

        return view('compras/index', $data);
    }


    public function nuevo()
    {
        $productoModel  = model(ProductoModel::class);
        $proveedorModel = model(ProveedorModel::class);
        $localModel     = model(LocalModel::class);

        $data = [
            'titulo'      => 'Registrar Nueva Compra',
            'productos'   => $productoModel->select('productos.*, unidadesmedida.unidad AS nombreunidad, unidadesmedida.abreviatura AS abreviaturaunidad')->join('unidadesmedida', 'unidadesmedida.idunidad = productos.idunidad', 'left')->where('productos.tipo', 'PRODUCTO')->where('productos.controlastock', 1)->where('productos.estado', 1)->findAll(),
            'proveedores' => $proveedorModel->findAll(),
            'locales'     => $localModel->findAll()
        ];

        return view('compras/nuevo', $data);
    }


    public function guardar()
    {
        $productos = $this->request->getPost('productos');
        $cantidades = $this->request->getPost('cantidades');
        $precios = $this->request->getPost('precios');
        $seriesItems = $this->request->getPost('series_items');
        $moneda = $this->request->getPost('moneda');
        $tipoCambio = (float) $this->request->getPost('tipocambio');
        $idlocal = (int) $this->request->getPost('idlocal');
        $idusuario = (int) session()->get('idusuario');

        $reglas = [
            'idproveedor' => 'required|is_not_unique[proveedores.idproveedor]',
            'idlocal' => 'required|is_not_unique[locales.idlocal]',
            'tipocomprobante' => 'required|in_list[FACTURA,BOLETA,GUIA]',
            'serie' => 'required|max_length[20]',
            'numero' => 'required|max_length[20]',
            'fechacompra' => 'required|valid_date[Y-m-d]',
            'moneda' => 'required|in_list[PEN,USD]',
        ];
        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('error', 'Revise los datos del comprobante, proveedor y almacén.');
        }
        if (!$idusuario) {
            return redirect()->back()->withInput()->with('error', 'La sesión no identifica al usuario responsable de la compra.');
        }
        if (
            !is_array($productos) || !$productos || !is_array($cantidades) || !is_array($precios)
            || count($productos) !== count($cantidades) || count($productos) !== count($precios)
        ) {
            return redirect()->back()->withInput()->with('error', 'Agregue al menos un producto válido.');
        }
        if (
            !in_array($moneda, ['PEN', 'USD'], true) || !is_finite($tipoCambio)
            || ($moneda === 'USD' && $tipoCambio <= 0)
        ) {
            return redirect()->back()->withInput()->with('error', 'El tipo de cambio debe ser mayor a cero para compras en dólares.');
        }
        if ($moneda === 'PEN') {
            $tipoCambio = 1.0;
        }

        $productoModel = model(ProductoModel::class);
        $items = [];
        $total = 0.0;
        $seriesEnCompra = [];
        foreach ($productos as $i => $id) {
            $producto = $productoModel->find((int)$id);
            $cantidadRaw = (string)($cantidades[$i] ?? '');
            $precioRaw = (string)($precios[$i] ?? '');
            if (
                !$producto || $producto['tipo'] !== 'PRODUCTO' || (int)$producto['controlastock'] !== 1
                || !preg_match('/^\d+(?:\.\d{1,2})?$/', $cantidadRaw)
                || !preg_match('/^\d+(?:\.\d{1,2})?$/', $precioRaw)
                || (float)$cantidadRaw <= 0 || (float)$precioRaw < 0
            ) {
                return redirect()->back()->withInput()->with('error', 'Solo se pueden comprar artículos físicos con stock; revise cantidades y costos.');
            }
            $unidadFila = \Config\Database::connect()->table('unidadesmedida')->select('abreviatura')->where('idunidad', (int)$producto['idunidad'])->get()->getRowArray();
            $unidad = $unidadFila['abreviatura'] ?? null;
            $admiteDecimales = $unidad !== null && strtoupper($unidad) === 'M';
            if (!$admiteDecimales && floor((float)$cantidadRaw) !== (float)$cantidadRaw) {
                return redirect()->back()->withInput()->with('error', 'Este producto se compra en unidades enteras; revise la cantidad.');
            }
            $cantidad = (float)$cantidadRaw;
            $precio = (float)$precioRaw;
            $controlaSerie = (int) ($producto['controlaserie'] ?? 0) === 1;
            $series = [];
            if ($controlaSerie) {
                $series = json_decode((string) ($seriesItems[$i] ?? ''), true);
                if (!is_array($series) || $cantidad > 100 || floor($cantidad) !== $cantidad || count($series) !== (int) $cantidad) {
                    return redirect()->back()->withInput()->with('error', 'Debe ingresar una serie por cada unidad del producto ' . $producto['nombre'] . '.');
                }
                foreach ($series as &$serie) {
                    if (!is_string($serie)) {
                        return redirect()->back()->withInput()->with('error', 'Número de serie inválido.');
                    }
                    $serie = trim($serie);
                    $clave = (int) $id . ':' . strtoupper($serie);
                    if ($serie === '' || strlen($serie) > 60 || isset($seriesEnCompra[$clave])) {
                        return redirect()->back()->withInput()->with('error', 'Hay números de serie vacíos, repetidos o demasiado largos.');
                    }
                    $seriesEnCompra[$clave] = true;
                }
                unset($serie);
            }
            $dbSeries = \Config\Database::connect();
            foreach ($series as $serie) {
                $existente = $dbSeries->table('series')->where('idproducto', (int) $id)
                    ->where('numeroserie', $serie)->get()->getRowArray();
                if ($existente && ($existente['iddetcompra'] !== null || $existente['idlocal'] !== null)) {
                    return redirect()->back()->withInput()->with('error', 'La serie ' . $serie . ' ya fue ingresada al inventario.');
                }
            }
            $total += round($cantidad * $precio, 2);
            $items[] = ['idproducto' => (int)$id, 'cantidad' => $cantidad, 'precio' => $precio, 'series' => $series];
        }
        if (!is_finite($total) || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El importe de la compra supera el límite permitido.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $compraModel = model(CompraModel::class);
            $detalleModel = model(DeCompraModel::class);
            $kardexModel = model(KardexModel::class);
            $movimientoModel = model(MovimientoModel::class);
            $idcompra = $compraModel->insert([
                'idproveedor' => (int)$this->request->getPost('idproveedor'),
                'tipocomprobante' => $this->request->getPost('tipocomprobante'),
                'serie' => trim($this->request->getPost('serie')),
                'numero' => trim($this->request->getPost('numero')),
                'fechacompra' => $this->request->getPost('fechacompra'),
                'fecharegistro' => date('Y-m-d H:i:s'),
                'moneda' => $moneda,
                'tipocambio' => $tipoCambio,
                'total' => round($total, 2),
            ]);
            if (!$idcompra) {
                throw new \RuntimeException('No se pudo guardar la cabecera.');
            }

            foreach ($items as $item) {
                $idproducto = $item['idproducto'];
                $cantidad = $item['cantidad'];
                $precio = $item['precio'];
                $iddetcompra = $detalleModel->insert([
                    'idcompra' => $idcompra,
                    'idproducto' => $idproducto,
                    'cantidad' => $cantidad,
                    'preciocompra' => $precio,
                ]);
                if (!$iddetcompra) {
                    throw new \RuntimeException('No se pudo guardar un detalle.');
                }
                foreach ($item['series'] as $serie) {
                    $existente = $db->table('series')->where('idproducto', $idproducto)
                        ->where('numeroserie', $serie)->get()->getRowArray();
                    if ($existente) {
                        if ($existente['iddetcompra'] !== null || $existente['idlocal'] !== null) {
                            throw new \RuntimeException('Serie ya ingresada: ' . $serie);
                        }
                        $okSerie = $db->table('series')->where('idserie', $existente['idserie'])
                            ->where('iddetcompra', null)->where('idlocal', null)
                            ->update(['iddetcompra' => $iddetcompra, 'idlocal' => $idlocal, 'estado' => 'EN_STOCK']);
                    } else {
                        $okSerie = $db->table('series')->insert([
                            'idproducto' => $idproducto,
                            'numeroserie' => $serie,
                            'idlocal' => $idlocal,
                            'iddetcompra' => $iddetcompra,
                            'estado' => 'EN_STOCK',
                        ]);
                    }
                    if (!$okSerie) {
                        throw new \RuntimeException('No se pudo guardar la serie ' . $serie);
                    }
                }

                $row = $db->query(
                    'SELECT * FROM kardex WHERE idproducto = ? AND idlocal = ? FOR UPDATE',
                    [$idproducto, $idlocal]
                )->getRowArray();
                $costoPEN = $precio * $tipoCambio;
                if ($row) {
                    $anterior = (float)$row['stockactual'];
                    $nuevo = $anterior + $cantidad;
                    $costoPromedio = (($anterior * (float)$row['costopromedio']) + ($cantidad * $costoPEN)) / $nuevo;
                    $idkardex = (int)$row['idkardex'];
                    if (!$kardexModel->update($idkardex, ['stockactual' => $nuevo, 'costopromedio' => round($costoPromedio, 4)])) {
                        throw new \RuntimeException('No se pudo actualizar Kardex.');
                    }
                } else {
                    $nuevo = $cantidad;
                    $idkardex = $kardexModel->insert([
                        'idproducto' => $idproducto,
                        'idlocal' => $idlocal,
                        'stockactual' => $nuevo,
                        'costopromedio' => round($costoPEN, 4),
                        'minima' => 0,
                        'maxima' => 0,
                    ]);
                    if (!$idkardex) {
                        throw new \RuntimeException('No se pudo crear Kardex.');
                    }
                }
                if (!$movimientoModel->insert([
                    'idkardex' => $idkardex,
                    'idcompra' => $idcompra,
                    'idusuario' => $idusuario,
                    'tipo' => 'ENTRADA',
                    'fecha' => date('Y-m-d H:i:s'),
                    'cantidad' => $cantidad,
                    'saldo' => $nuevo,
                    'descripcion' => 'Compra ' . $idcompra,
                    'motivo' => 'Ingreso por compra',
                ])) {
                    throw new \RuntimeException('No se pudo registrar movimiento de stock.');
                }
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Error de base de datos.');
            }
            $db->transCommit();
            return redirect()->to(base_url('admin/compras'))->with('mensaje', 'Compra registrada y Kardex actualizado.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al guardar compra: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'No se pudo registrar la compra. Revise el comprobante y los datos.');
        }
    }


    public function detalles($idcompra)
    {
        $compraModel    = model(CompraModel::class);
        $detCompraModel = model(DeCompraModel::class);

        $compra = $compraModel->select('compras.*, proveedores.razonsocial as proveedor, proveedores.ruc')
            ->join('proveedores', 'proveedores.idproveedor = compras.idproveedor', 'left')
            ->where('compras.idcompra', $idcompra)
            ->first();

        if (!$compra) {
            return redirect()->to(base_url('admin/compras'))->with('error', 'La compra solicitada no existe.');
        }

        $detalles = $detCompraModel->select('detcompra.*, productos.nombre as producto')
            ->join('productos', 'productos.idproducto = detcompra.idproducto', 'left')
            ->where('detcompra.idcompra', $idcompra)
            ->findAll();

        $data = [
            'titulo'   => 'Detalle de Compra N° ' . $idcompra,
            'compra'   => $compra,
            'detalles' => $detalles
        ];

        return view('compras/detalles', $data);
    }
}
