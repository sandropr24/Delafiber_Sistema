<?php

namespace App\Controllers;

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
            'productos'   => $productoModel->findAll(),
            'proveedores' => $proveedorModel->findAll(),
            'locales'     => $localModel->findAll()
        ];

        return view('compras/nuevo', $data);
    }


    public function guardar()
    {
        $reglas = [
            'idproveedor'     => 'required|is_not_unique[proveedores.idproveedor]',
            'serie'           => 'required',
            'tipocomprobante' => 'required',
            'productos'       => 'required'
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('error', 'Por favor, complete todos los campos obligatorios de forma correcta.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $compraModel     = model(CompraModel::class);
            $detCompraModel  = model(DeCompraModel::class);
            $kardexModel     = model(KardexModel::class);
            $movimientoModel = model(MovimientoModel::class);

            $idproveedor     = $this->request->getPost('idproveedor');
            $serie           = $this->request->getPost('serie');
            $tipocomprobante = $this->request->getPost('tipocomprobante');
            $fechacompra     = $this->request->getPost('fechacompra') ?? date('Y-m-d');
            $idlocal         = $this->request->getPost('idlocal') ?? 1;

            $productos       = $this->request->getPost('productos');
            $cantidades      = $this->request->getPost('cantidades');
            $precios         = $this->request->getPost('precios');

            if (empty($productos) || !is_array($productos)) {
                return redirect()->back()->withInput()->with('error', 'El detalle de la compra no puede estar vacío.');
            }

            $totalCompra = 0;
            for ($i = 0; $i < count($productos); $i++) {
                $totalCompra += ((float)$cantidades[$i] * (float)$precios[$i]);
            }

            $idcompra = $compraModel->insert([
                'idproveedor'     => $idproveedor,
                'serie'           => $serie,
                'tipocomprobante' => $tipocomprobante,
                'fechacompra'     => $fechacompra,
                'fecharegistro'   => date('Y-m-d H:i:s'),
                'total'           => $totalCompra
            ]);

            if (!$idcompra) {
                throw new \RuntimeException('Error al registrar la cabecera de la compra: ' . implode(', ', $compraModel->errors()));
            }

            for ($i = 0; $i < count($productos); $i++) {
                $idproducto = $productos[$i];
                $cantidad   = (int)$cantidades[$i];
                $precio     = (float)$precios[$i];

                $detInserted = $detCompraModel->insert([
                    'idcompra'     => $idcompra,
                    'idproducto'   => $idproducto,
                    'cantidad'     => $cantidad,
                    'preciocompra' => $precio
                ]);

                if (!$detInserted) {
                    throw new \RuntimeException('Error al registrar el detalle del producto ID ' . $idproducto . ': ' . implode(', ', $detCompraModel->errors()));
                }

                $kardexActual = $kardexModel->where('idproducto', $idproducto)
                    ->where('idlocal', $idlocal)
                    ->first();

                $stockAnterior = $kardexActual ? (int)$kardexActual['stockactual'] : 0;
                $nuevoStock    = $stockAnterior + $cantidad;
                $costoAnterior = $kardexActual ? (float)$kardexActual['costopromedio'] : 0.00;

                $nuevoCostoPromedio = $nuevoStock > 0
                    ? (($stockAnterior * $costoAnterior) + ($cantidad * $precio)) / $nuevoStock
                    : $precio;

                if ($kardexActual) {
                    $idkardex = $kardexActual['idkardex'];
                    $kardexModel->update($idkardex, [
                        'stockactual'   => $nuevoStock,
                        'costopromedio' => $nuevoCostoPromedio
                    ]);
                } else {
                    $idkardex = $kardexModel->insert([
                        'idproducto'    => $idproducto,
                        'idlocal'       => $idlocal,
                        'stockactual'   => $nuevoStock,
                        'costopromedio' => $nuevoCostoPromedio,
                        'minima'        => 5,
                        'maxima'        => 100
                    ]);

                    if (!$idkardex) {
                        throw new \RuntimeException('Error al inicializar el Kardex para el producto ID ' . $idproducto);
                    }
                }

                $movInserted = $movimientoModel->insert([
                    'idkardex'    => $idkardex,
                    'idcompra'    => $idcompra,
                    'idusuario'   => session()->get('idusuario') ?? 1,
                    'tipo'        => 'ENTRADA',
                    'fecha'       => date('Y-m-d H:i:s'),
                    'cantidad'    => $cantidad,
                    'saldo'       => $nuevoStock,
                    'descripcion' => 'Ingreso por Compra N° ' . $idcompra,
                    'motivo'      => 'Reabastecimiento de inventario'
                ]);

                if (!$movInserted) {
                    throw new \RuntimeException('Error al registrar el movimiento de inventario para el producto ID ' . $idproducto);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transacción de la base de datos falló en el commit.');
            }

            return redirect()->to(base_url('compras'))->with('mensaje', 'Compra registrada con éxito y Kardex actualizado.');
        } catch (\Exception $e) {
            $db->transRollback();

            log_message('error', 'Error en Guardar Compra: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return redirect()->back()->withInput()->with('error', 'Ocurrió un error inesperado al procesar la compra: ' . $e->getMessage());
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
            return redirect()->to(base_url('compras'))->with('error', 'La compra solicitada no existe.');
        }

        $detalles = $detCompraModel->select('detcompras.*, productos.descripcion as producto')
            ->join('productos', 'productos.idproducto = detcompras.idproducto', 'left')
            ->where('detcompras.idcompra', $idcompra)
            ->findAll();

        $data = [
            'titulo'   => 'Detalle de Compra N° ' . $idcompra,
            'compra'   => $compra,
            'detalles' => $detalles
        ];

        return view('compras/detalles', $data);
    }
}
