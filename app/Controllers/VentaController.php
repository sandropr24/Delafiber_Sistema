<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DetVentaModel;
use App\Models\KardexModel;
use App\Models\VentaModel;
use App\Models\PersonaModel;
use App\Models\LocalModel;
use App\Models\ProductoModel;
use App\Models\TipoPagoModel;

class VentaController extends BaseController
{
    public function index()
    {
        $ventaModel = model(VentaModel::class);
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
        $kardexModel   = model(KardexModel::class);
        $tipoPagoModel = model(TipoPagoModel::class);

        $clientes = $personaModel->builder()
            ->whereNotIn('idpersona', static function ($builder) {
                return $builder->select('idpersona')->from('usuarios');
            })
            ->orderBy('nombres', 'ASC')
            ->get()
            ->getResultArray();

        $kardexData = $kardexModel->findAll();
        $stockPorLocal = [];
        foreach ($kardexData as $k) {
            $stockPorLocal[$k['idlocal']][$k['idproducto']] = (int)$k['stockactual'];
        }

        $data = [
            'titulo'        => 'Registrar Nueva Venta',
            'clientes'      => $clientes,
            'locales'       => $localModel->findAll(),
            'productos'     => $productoModel->findAll(),
            'tipospago'     => $tipoPagoModel->findAll(),
            'stockPorLocal' => json_encode($stockPorLocal)
        ];

        return view('ventas/crear', $data);
    }

    public function guardar()
    {
        $ventaModel = model(VentaModel::class);

        $idCliente = $this->request->getPost('idcliente');

        if (empty($idCliente) || $idCliente === 'false' || $idCliente === false) {
            return redirect()->back()->with('error', 'Por favor, seleccione un cliente válido antes de procesar la venta.')->withInput();
        }

        $dataVenta = [
            'idcliente'     => $idCliente,
            'idlocal'       => $this->request->getPost('idlocal'),
            'idpedido'      => $this->request->getPost('idpedido') ?: null,
            'idcajero'      => session()->get('idusuario') ?? 1,
            'tipodocumento' => $this->request->getPost('tipodocumento'),
            'serie'         => $this->request->getPost('serie'),
            'cdr'           => null,
            'numero'        => $this->request->getPost('numero'),
            'estafacturado' => 0,
            'fecha'         => date('Y-m-d H:i:s'),
            'total'         => $this->request->getPost('total')
        ];

        $detalles = $this->request->getPost('detalles');
        $pagos    = $this->request->getPost('pagos');

        if (empty($detalles)) {
            return redirect()->back()->with('error', 'Debes agregar al menos un producto a la venta.')->withInput();
        }

        if (empty($pagos)) {
            return redirect()->back()->with('error', 'Debes registrar al menos una forma de pago.')->withInput();
        }

        $resultado = $ventaModel->registrarVenta($dataVenta, $detalles, $pagos);

        if ($resultado) {
            return redirect()->to('/ventas')->with('success', 'Venta registrada exitosamente.');
        } else {
            $errorMsg = session()->getFlashdata('error') ?? 'El modelo no pudo registrar la venta.';
            return redirect()->back()->with('error', $errorMsg)->withInput();
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

   public function guardarClienteExpress()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $clienteModel = model(PersonaModel::class);

        $documento = $this->request->getPost('documento');
        $nombre    = $this->request->getPost('nombre');
        $telefono  = $this->request->getPost('telefono') ?? '';

        if (empty($documento) || empty($nombre)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'El documento y el nombre son obligatorios.'
            ]);
        }

        $data = [
            'apellidos' => '-',
            'nombres'   => $nombre,
            'tipodoc'   => strlen($documento) == 11 ? 'RUC' : 'DNI',
            'numerodoc' => $documento,
            'direccion' => '-',
            'telefono'  => $telefono,
            'email'     => ''
        ];

        try {
            if ($clienteModel->skipValidation(true)->insert($data)) {
                $idNewCliente = $clienteModel->getInsertID();

                return $this->response->setJSON([
                    'status'    => 'success',
                    'message'   => 'Cliente registrado con éxito.',
                    'idcliente' => $idNewCliente,
                    'nombre'    => $nombre,
                    'documento' => $documento
                ]);
            } else {
                $errores = implode(" - ", $clienteModel->errors());
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Error de validación del modelo: ' . $errores
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Error BD: ' . $e->getMessage()
            ]);
        }
    }
}