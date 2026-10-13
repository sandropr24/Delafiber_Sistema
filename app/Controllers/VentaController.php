<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DetVentaModel;
use App\Models\VentaModel;
use App\Models\PersonaModel;
use App\Models\LocalModel;
use App\Models\ProductoModel;

class VentaController extends BaseController
{
    public function index()
    {
        $ventaModel = new VentaModel();
        $data = [
            'titulo' => 'Historial de Ventas',
            'ventas' => $ventaModel->select('ventas.*, CONCAT(personas.nombres, " ", personas.apellidos) as cliente, locales.nombrelocal as local')
                ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
                ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
                ->findAll()
        ];
        return view('ventas/index', $data);
    }

    public function crear()
    {
        $personaModel  = model(PersonaModel::class);
        $localModel    = model(LocalModel::class);
        $productoModel = model(ProductoModel::class);

        $clientes = $personaModel->builder()
            ->whereNotIn('idpersona', static function ($builder) {
                return $builder->select('idpersona')->from('usuarios');
            })
            ->orderBy('nombres', 'ASC')
            ->get()
            ->getResultArray();

        $data = [
            'titulo'    => 'Registrar Nueva Venta',
            'clientes'  => $clientes,
            'locales'   => $localModel->findAll(),
            'productos' => $productoModel->findAll()
        ];

        return view('ventas/crear', $data);
    }

    public function guardar()
    {
        $ventaModel = new VentaModel();

        $dataVenta = [
            'idcliente'     => $this->request->getPost('idcliente'),
            'idlocal'       => $this->request->getPost('idlocal'),
            'idpedido'      => $this->request->getPost('idpedido') ?: null,
            'idcajero'      => session()->get('idusuario') ?? 1,
            'tipodocumento' => $this->request->getPost('tipodocumento'),
            'serie'         => $this->request->getPost('serie'),
            'numero'        => $this->request->getPost('numero'),
            'estafacturado' => 0,
            'fecha'         => date('Y-m-d H:i:s'),
            'total'         => $this->request->getPost('total')
        ];

        $detalles = $this->request->getPost('detalles');

        if (empty($detalles)) {
            return redirect()->back()->with('error', 'Debes agregar al menos un producto a la venta.');
        }

        $resultado = $ventaModel->registrarVenta($dataVenta, $detalles);

        if ($resultado) {
            return redirect()->to('/ventas')->with('success', 'Venta registrada y stock descontado con éxito.');
        } else {
            return redirect()->back()->with('error', 'Error al procesar la venta. Inténtalo de nuevo.');
        }
    }

    public function detalles($idventa)
    {
        $ventaModel    = model(VentaModel::class);
        $detVentaModel = model(DetVentaModel::class);


        $venta = $ventaModel->select('ventas.*, CONCAT(personas.nombres, " ", personas.apellidos) as cliente, personas.numerodoc, locales.nombrelocal as local')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
            ->where('ventas.idventa', $idventa)
            ->first();

        if (!$venta) {
            return redirect()->to(base_url('ventas'))->with('error', 'La venta solicitada no existe.');
        }

        $detalles = $detVentaModel->select('detventa.*, productos.nombre as producto, productos.codigobarras')
            ->join('productos', 'productos.idproducto = detventa.idproducto', 'left')
            ->where('detventa.idventa', $idventa)
            ->findAll();

        $data = [
            'titulo'   => 'Detalle de Venta N° ' . $idventa,
            'venta'    => $venta,
            'detalles' => $detalles
        ];

        return view('ventas/detalles', $data);
    }
}
