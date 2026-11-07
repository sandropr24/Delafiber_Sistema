<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DetVentaModel;
use App\Models\KardexModel;
use App\Models\VentaModel;
use App\Models\PersonaModel;
use App\Models\LocalModel;
use App\Models\PagoVentaModel;
use App\Models\ProductoModel;
use App\Models\TipoPagoModel;

class VentaController extends BaseController
{
    public function index()
    {
        $ventaModel = model(VentaModel::class);
        $db = \Config\Database::connect();
        $puedeAutorizar = session()->get('rol') === 'Administrador';
        $solicitudesPendientes = $puedeAutorizar
            ? $db->table('solicitudes_anulacion s')
                ->select('s.idsolicitud, s.idventa, s.motivo, s.fecha_solicitud, v.numero, v.serie')
                ->join('ventas v', 'v.idventa = s.idventa')
                ->where('s.estado', 'PENDIENTE')
                ->orderBy('s.fecha_solicitud', 'ASC')
                ->get()->getResultArray()
            : [];
        $data = [
            'titulo' => 'Historial de Ventas',
            'ventas' => $ventaModel->select('ventas.*, IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) as cliente, locales.nombrelocal as local')
                ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
                ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
                ->orderBy('ventas.fecha', 'DESC')
                ->findAll(),
            'solicitudesPendientes' => $solicitudesPendientes,
            'puedeAutorizar' => $puedeAutorizar,
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
        $db = \Config\Database::connect();

        $clientes = $personaModel->builder()
            ->whereNotIn('idpersona', static function ($builder) {
                return $builder->select('idpersona')->from('usuarios');
            })
            ->orderBy('nombres', 'ASC')
            ->get()
            ->getResultArray();

        $kardexData = $db->query("SELECT k.idlocal, k.idproducto, k.stockactual,
                COALESCE(SUM(CASE WHEN r.estado = 'RESERVADO' THEN r.cantidad ELSE 0 END), 0) AS reservado
            FROM kardex k LEFT JOIN reservas_stock r ON r.idkardex = k.idkardex
            GROUP BY k.idkardex, k.idlocal, k.idproducto, k.stockactual")->getResultArray();
        $stockPorLocal = [];
        foreach ($kardexData as $k) {
            $stockPorLocal[$k['idlocal']][$k['idproducto']] = max(0, round((float)$k['stockactual'] - (float)$k['reservado'], 2));
        }

        $seriesDisponibles = [];
        $series = $db->table('series')
            ->select('idserie, idproducto, idlocal, numeroserie')
            ->where('estado', 'EN_STOCK')
            ->where('iddetcompra IS NOT NULL', null, false)
            ->where('idlocal IS NOT NULL', null, false)
            ->get()->getResultArray();
        foreach ($series as $serie) {
            $seriesDisponibles[$serie['idlocal']][$serie['idproducto']][] = [
                'id' => (int) $serie['idserie'],
                'numero' => $serie['numeroserie'],
            ];
        }

        $data = [
            'titulo'        => 'Registrar Nueva Venta',
            'clientes'      => $clientes,
            'locales'       => $localModel->findAll(),
            'productos'     => $productoModel->where('tipo', 'PRODUCTO')->findAll(),
            'servicios'     => $productoModel->where('tipo', 'SERVICIO')->findAll(),
            'combos'        => $productoModel->where('tipo', 'COMBO')->findAll(),
            'comboComponentes' => $db->query("SELECT cd.idcombo, cd.idproducto, cd.cantidad, p.nombre, p.tipo, p.controlastock, p.controlaserie FROM combo_detalle cd JOIN productos p ON p.idproducto = cd.idproducto")->getResultArray(),
            'tipospago'     => $tipoPagoModel->findAll(),
            'lineas'        => $db->table('lineas')->get()->getResultArray(),
            'canales'       => $db->table('canalesventa')->get()->getResultArray(),
            'estadosVenta'  => $db->table('estadoventa')->get()->getResultArray(),
            'stockPorLocal' => json_encode($stockPorLocal),
            'seriesDisponibles' => json_encode($seriesDisponibles),
            'tecnicosEntrega' => model(\App\Models\TecnicoModel::class)->activos()
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
            'idlinea'       => $this->request->getPost('idlinea'),
            'idcanal'       => $this->request->getPost('idcanal'),
            'idvendedor'    => session()->get('idusuario'),
            'idestado'      => $this->request->getPost('idestado'),
            'formapago'     => 'CONTADO',
            'idcajero'      => session()->get('idusuario'),
            'tipodocumento' => $this->request->getPost('tipodocumento'),
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

        if (empty($dataVenta['idcajero']) || empty($dataVenta['idlinea']) || empty($dataVenta['idcanal']) || empty($dataVenta['idestado'])) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios: usuario, línea, canal o estado de venta.')->withInput();
        }

        $idTecnicoEntrega = $this->request->getPost('idtecnico_entrega');
        if ($idTecnicoEntrega !== null && $idTecnicoEntrega !== '') {
            $tecnico = model(\App\Models\TecnicoModel::class)->find((int) $idTecnicoEntrega);
            if (!$tecnico || $tecnico['estado'] !== 'ACTIVO') {
                return redirect()->back()->with('error', 'Selecciona un técnico activo válido.')->withInput();
            }
        }

        $entrega = [
            'tipoentrega' => $this->request->getPost('tipoentrega') ?: 'RECOJO_TIENDA',
            'direccion' => trim((string) $this->request->getPost('direccion_entrega')),
            'referencia' => trim((string) $this->request->getPost('referencia_entrega')),
            'telefono' => trim((string) $this->request->getPost('telefono_entrega')),
            'idtecnico' => $idTecnicoEntrega ?: null,
        ];
        $resultado = $ventaModel->registrarVenta($dataVenta, $detalles, $pagos, $entrega);

        if ($resultado) {
            return redirect()->to('/ventas')->with('success', 'Venta registrada exitosamente.');
        } else {
            $errorMsg = session()->getFlashdata('error') ?? 'No se pudo registrar la venta. Inténtelo nuevamente.';
            return redirect()->back()->with('error', $errorMsg)->withInput();
        }
    }

    public function detalles($idventa)
    {
        $ventaModel     = model(VentaModel::class);
        $detVentaModel  = model(DetVentaModel::class);
        $pagoVentaModel = model(PagoVentaModel::class);

        $venta = $ventaModel->select('ventas.*,
                                      IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) as cliente,
                                      personas.numerodoc,
                                      locales.nombrelocal as local')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
            ->where('ventas.idventa', $idventa)
            ->first();

        if (!$venta) {
            return redirect()->to(base_url('ventas'))->with('error', 'La venta solicitada no existe.');
        }

        $detalles = $detVentaModel->select('detventa.*, productos.nombre as producto')
            ->join('productos', 'productos.idproducto = detventa.idproducto', 'left')
            ->where('detventa.idventa', $idventa)
            ->findAll();

        $pagos = $pagoVentaModel->select('pagoventa.*, tipospago.tipopago')
            ->join('tipospago', 'tipospago.idtipopago = pagoventa.idtipopago', 'left')
            ->where('pagoventa.idventa', $idventa)
            ->findAll();

        $db = \Config\Database::connect();
        $entregaComercial = $db->table('entregas')->select('identrega, estado, tipoentrega, fechaencamino')->where('idventa', $idventa)->get()->getRowArray();
        $solicitudes = $db->table('solicitudes_anulacion s')
            ->select('s.*, CONCAT(COALESCE(p.nombres, ""), " ", COALESCE(p.apellidos, "")) AS solicitante')
            ->join('usuarios u', 'u.idusuario = s.idsolicitante')
            ->join('personas p', 'p.idpersona = u.idpersona')
            ->where('s.idventa', $idventa)
            ->orderBy('s.idsolicitud', 'DESC')
            ->get()->getResultArray();
        $solicitudActiva = null;
        foreach ($solicitudes as $solicitud) {
            if (in_array($solicitud['estado'], ['PENDIENTE', 'AUTORIZADA'], true)) {
                $solicitudActiva = $solicitud;
                break;
            }
        }

        $data = [
            'titulo' => 'Detalle de Venta N° ' . $idventa,
            'venta' => $venta,
            'detalles' => $detalles,
            'pagos' => $pagos,
            'entregaComercial' => $entregaComercial,
            'solicitudesAnulacion' => $solicitudes,
            'solicitudActiva' => $solicitudActiva,
            'puedeSolicitar' => in_array(session()->get('rol'), ['Administrador', 'Vendedor'], true),
            'puedeAutorizar' => session()->get('rol') === 'Administrador',
        ];

        return view('ventas/detalles', $data);
    }


    public function guardarClienteExpress()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Solicitud no permitida.'
            ]);
        }

        $clienteModel = model(\App\Models\PersonaModel::class);

        $documento = trim((string) $this->request->getPost('documento'));
        $nombre = trim((string) $this->request->getPost('nombre'));
        $telefono = trim((string) $this->request->getPost('telefono'));

        if (!in_array(strlen($documento), [8, 11], true) || !ctype_digit($documento)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.'
            ]);
        }

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'El nombre debe tener entre 2 y 100 caracteres.'
            ]);
        }

        if ($telefono !== '' && (!ctype_digit($telefono) || strlen($telefono) > 20)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ingrese un teléfono válido.'
            ]);
        }

        $tipoDoc = strlen($documento) === 11 ? 'RUC' : 'DNI';

        $buscarCliente = static function () use ($clienteModel, $tipoDoc, $documento) {
            return $clienteModel
                ->where('tipodoc', $tipoDoc)
                ->where('numerodoc', $documento)
                ->first();
        };

        try {
            $existente = $buscarCliente();

            if ($existente) {
                return $this->response->setJSON([
                    'status' => 'exists',
                    'message' => 'Este documento ya está registrado.',
                    'idcliente' => $existente['idpersona'],
                    'nombre' => trim(
                        ($existente['nombres'] ?? '') . ' ' .
                            ($existente['apellidos'] ?? '')
                    ),
                    'documento' => $documento
                ]);
            }

            $data = [
                'apellidos' => null,
                'nombres' => $nombre,
                'tipodoc' => $tipoDoc,
                'numerodoc' => $documento,
                'direccion' => null,
                'telefono' => $telefono ?: null,
                'email' => null,
                'estado' => 1
            ];

            $idCliente = $clienteModel->insert($data, true);

            if ($idCliente === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No se pudo registrar el cliente. Verifique los datos ingresados.'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Cliente registrado correctamente.',
                'idcliente' => $idCliente,
                'nombre' => $nombre,
                'documento' => $documento
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registro rápido de cliente: {error}', [
                'error' => $e->getMessage()
            ]);

            try {
                $existente = $buscarCliente();

                if ($existente) {
                    return $this->response->setJSON([
                        'status' => 'exists',
                        'message' => 'Este documento ya está registrado.',
                        'idcliente' => $existente['idpersona'],
                        'nombre' => trim(
                            ($existente['nombres'] ?? '') . ' ' .
                                ($existente['apellidos'] ?? '')
                        ),
                        'documento' => $documento
                    ]);
                }
            } catch (\Throwable $ignored) {
                log_message('error', 'Error al consultar cliente tras fallo de registro: ' . $ignored->getMessage());
            }

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'No se pudo completar el registro. Inténtelo nuevamente.'
            ]);
        }
    }
}
