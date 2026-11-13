<?php

namespace App\Controllers\Admin;

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
        $filtros = array_map(static fn($valor) => is_scalar($valor) ? trim((string) $valor) : '', $this->request->getGet(['q', 'desde', 'hasta', 'estado', 'vendedor']));
        foreach (['desde', 'hasta'] as $fecha) {
            if ($filtros[$fecha] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $filtros[$fecha])) $filtros[$fecha] = '';
        }
        if ($filtros['q'] !== '') {
            $ventaModel->groupStart()->like('personas.nombres', $filtros['q'])->orLike('personas.apellidos', $filtros['q'])->orLike('personas.razonsocial', $filtros['q'])->orLike('personas.numerodoc', $filtros['q'])->orLike('ventas.numero', $filtros['q'])->groupEnd();
        }
        if ($filtros['desde'] !== '') $ventaModel->where('ventas.fecha >=', $filtros['desde'] . ' 00:00:00');
        if ($filtros['hasta'] !== '') $ventaModel->where('ventas.fecha <=', $filtros['hasta'] . ' 23:59:59');
        if ($filtros['estado'] === 'ACTIVA') $ventaModel->where('ventas.fechaanulacion', null);
        if ($filtros['estado'] === 'ANULADA') $ventaModel->where('ventas.fechaanulacion IS NOT NULL', null, false);
        if (ctype_digit($filtros['vendedor'])) $ventaModel->where('ventas.idvendedor', (int) $filtros['vendedor']);
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
            'filtros' => $filtros,
            'vendedores' => $db->table('usuarios')->select('idusuario, nombreusuario')->orderBy('nombreusuario')->get()->getResultArray(),
            'ventas' => $ventaModel->select('ventas.*, IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) as cliente, locales.nombrelocal as local, vendedor.nombreusuario AS vendedor, (SELECT e.estado FROM entregas e WHERE e.idventa = ventas.idventa ORDER BY e.identrega DESC LIMIT 1) AS estadoentrega')
                ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
                ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
                ->join('usuarios vendedor', 'vendedor.idusuario = ventas.idvendedor', 'left')
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
            ->where('estado', 1)
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

        $idUsuario = (int) session()->get('idusuario');
        $rolActual = $db->table('usuarios u')
            ->select('roles.rol')
            ->join('roles', 'roles.idrol = u.idrol')
            ->where('u.idusuario', $idUsuario)
            ->where('u.estado', 1)
            ->get()->getRow('rol');

        $locales = $localModel->findAll();
        $localUsuario = (int) session()->get('idlocal');
        if ($db->fieldExists('idlocal', 'usuarios')) {
            $localUsuario = (int) ($db->table('usuarios')->where('idusuario', $idUsuario)->get()->getRow('idlocal') ?: $localUsuario);
        }
        // Si no hay asignación, recordar el último local utilizado por este vendedor.
        if (!$localUsuario) $localUsuario = (int) $db->table('ventas')->where('idvendedor', $idUsuario)->orderBy('idventa', 'DESC')->get(1)->getRow('idlocal');
        $idsLocales = array_map('intval', array_column($locales, 'idlocal'));
        if (!in_array($localUsuario, $idsLocales, true)) $localUsuario = count($locales) === 1 ? (int) $locales[0]['idlocal'] : 0;
        $estados = $db->table('estadoventa')->get()->getResultArray();
        $estadoInicial = 0;
        foreach ($estados as $estado) {
            if (mb_strtolower(trim($estado['estado'])) === 'pendiente') $estadoInicial = (int) $estado['idestado'];
        }
        if (!$estadoInicial && count($estados) === 1) $estadoInicial = (int) $estados[0]['idestado'];
        $clienteInicial = 0;
        foreach ($clientes as $cliente) {
            $nombre = mb_strtolower(trim(($cliente['razonsocial'] ?? '') . ' ' . ($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')));
            if (in_array($nombre, ['cliente general', 'consumidor final', 'público general', 'publico general'], true)) {
                $clienteInicial = (int) $cliente['idpersona'];
                break;
            }
        }

        $pedidoSeleccionado = null;
        $datosPedido = [];
        $idPedido = (int) $this->request->getGet('pedido');
        if ($idPedido > 0) {
            $pedidos = model(\App\Models\PedidoModel::class);
            $pedidoSeleccionado = $pedidos->obtener($idPedido);
            if (!$pedidoSeleccionado || $pedidoSeleccionado['estado_codigo'] !== 'CONFIRMADO' || $pedidoSeleccionado['idventa']) {
                return redirect()->to(base_url('admin/pedidos'))->with('error', 'El pedido no está confirmado o ya fue convertido.');
            }
            $clienteInicial = (int) $pedidoSeleccionado['idpersona'];
            $localUsuario = (int) $pedidoSeleccionado['idlocal'];
            $datosPedido = [
                'idcliente' => $clienteInicial, 'idlocal' => $localUsuario,
                'tipo_descuento' => $pedidoSeleccionado['tipodescuento'], 'valor_descuento' => $pedidoSeleccionado['valordescuento'], 'motivo_descuento' => $pedidoSeleccionado['motivodescuento'],
                'detalles' => array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $pedidos->detalles($idPedido)),
            ];
            $avisosPedido = [];
            foreach ($datosPedido['detalles'] as $lineaPedido) {
                $articuloPedido = $db->table('productos')->where('idproducto', $lineaPedido['idproducto'])->get()->getRowArray();
                if (!$articuloPedido || !(int) $articuloPedido['estado']) $avisosPedido[] = 'Hay artículos del pedido que ya no están activos. Puedes sustituirlos antes de cobrar.';
                elseif ($rolActual !== 'Administrador' && \App\Libraries\CobroVenta::centimos($lineaPedido['precioventa']) < \App\Libraries\CobroVenta::centimos($articuloPedido['precioventa'])) $avisosPedido[] = 'Revisa los precios: solo un administrador puede vender por debajo del catálogo vigente.';
            }
            if (!in_array($clienteInicial, array_map('intval', array_column($clientes, 'idpersona')), true)) $avisosPedido[] = 'Selecciona un cliente activo para la venta.';
            if ($pedidoSeleccionado['tipodescuento'] !== 'NINGUNO' && $rolActual !== 'Administrador') {
                $datosPedido['tipo_descuento'] = 'NINGUNO';
                $datosPedido['valor_descuento'] = '0';
                $datosPedido['motivo_descuento'] = '';
                $avisosPedido[] = 'El pedido incluía un descuento que solo un administrador puede aplicar. Revisa el total sin descuento antes de cobrar.';
            }

        }

        $cotizacionSeleccionada = null;
        $idCotizacion = (int) $this->request->getGet('cotizacion');
        if ($pedidoSeleccionado) {
            if ($idCotizacion && $idCotizacion !== (int) ($pedidoSeleccionado['idcotizacion'] ?? 0)) return redirect()->to(base_url('admin/pedidos'))->with('error', 'La cotización no corresponde al pedido.');
            $idCotizacion = (int) ($pedidoSeleccionado['idcotizacion'] ?? 0);
        }
        if ($idCotizacion > 0) {
            $cotizaciones = model(\App\Models\CotizacionModel::class);
            $cotizacionSeleccionada = $cotizaciones->obtener($idCotizacion);
            if (!$cotizacionSeleccionada || (!$pedidoSeleccionado && ($cotizacionSeleccionada['vencida'] || !in_array($cotizacionSeleccionada['estado_codigo'], ['EMITIDA', 'ACEPTADA'], true) || $cotizacionSeleccionada['idpedido'] || $cotizacionSeleccionada['idventa']))) return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La cotización venció, no está emitida o ya fue convertida.');
            if (!$pedidoSeleccionado) {
                $clienteInicial = (int) $cotizacionSeleccionada['idpersona']; $localUsuario = (int) $cotizacionSeleccionada['idlocal'];
                $datosPedido = ['idcliente' => $clienteInicial, 'idlocal' => $localUsuario, 'tipo_descuento' => 'NINGUNO', 'valor_descuento' => '0', 'motivo_descuento' => '', 'detalles' => array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $cotizaciones->detalles($idCotizacion))];
            }
            $datosPedido['idlinea'] = (int) $cotizacionSeleccionada['idlinea'];
        }

        $data = [
            'pedidoSeleccionado' => $pedidoSeleccionado,
            'cotizacionSeleccionada' => $cotizacionSeleccionada,
            'datosPedido' => $datosPedido,
            'iniciarPedido' => ($idPedido > 0 && $this->request->getGet('desde_pedido') === '1') || ($idCotizacion > 0 && $this->request->getGet('desde_cotizacion') === '1'),
            'avisosPedido' => array_values(array_unique($avisosPedido ?? [])),
            'localInicial' => $localUsuario,
            'estadoInicial' => $estadoInicial,
            'clienteInicial' => $clienteInicial,
            'requestToken' => bin2hex(random_bytes(32)),
            'puedeAplicarDescuento' => $rolActual === 'Administrador',
            'titulo'        => 'Registrar Nueva Venta',
            'clientes'      => $clientes,
            'locales'       => $locales,
            'productos'     => $productoModel->select('productos.*, um.abreviatura AS unidad')->join('unidadesmedida um', 'um.idunidad = productos.idunidad', 'left')->where('productos.estado', 1)->where('tipo', 'PRODUCTO')->findAll(),
            'servicios'     => $productoModel->select('productos.*, um.abreviatura AS unidad')->join('unidadesmedida um', 'um.idunidad = productos.idunidad', 'left')->where('productos.estado', 1)->where('tipo', 'SERVICIO')->findAll(),
            'combos'        => $productoModel->select('productos.*, um.abreviatura AS unidad')->join('unidadesmedida um', 'um.idunidad = productos.idunidad', 'left')->where('productos.estado', 1)->where('tipo', 'COMBO')->findAll(),
            'comboComponentes' => $db->query("SELECT cd.idcombo, cd.idproducto, cd.cantidad, p.nombre, p.tipo, p.controlastock, p.controlaserie FROM combo_detalle cd JOIN productos p ON p.idproducto = cd.idproducto")->getResultArray(),
            'tipospago'     => $tipoPagoModel->findAll(),
            'lineas'        => $db->table('lineas')->get()->getResultArray(),
            'canales'       => $db->table('canalesventa')->get()->getResultArray(),
            'estadosVenta'  => $estados,
            'stockPorLocal' => json_encode($stockPorLocal),
            'seriesDisponibles' => json_encode($seriesDisponibles),
            'tecnicosEntrega' => model(\App\Models\TecnicoModel::class)->activos()
        ];

        return view('ventas/crear', $data);
    }

    public function guardar()
    {
        $ventaModel    = model(VentaModel::class);

        $idCliente = $this->request->getPost('idcliente');
        if (empty($idCliente) || $idCliente === 'false' || $idCliente === false) {
            return redirect()->back()->with('error', 'Por favor, seleccione un cliente válido antes de procesar la venta.')->withInput();
        }

        $detallesPost = $this->request->getPost('detalles');
        $pagos        = $this->request->getPost('pagos');

        if (empty($detallesPost) || !is_array($detallesPost)) {
            return redirect()->back()->with('error', 'Debes agregar al menos un producto a la venta.')->withInput();
        }

        if (empty($pagos) || !is_array($pagos)) {
            return redirect()->back()->with('error', 'Debes registrar al menos una forma de pago.')->withInput();
        }

        // El modelo valida precios, cantidades, descuentos y cobros.
        $detallesSeguros = $detallesPost;
        $tipoDescuento = $this->request->getPost('tipo_descuento') ?: 'NINGUNO';
        $valorDescuento = $this->request->getPost('valor_descuento') ?: '0';

        $dataVenta = [
            'idcliente'       => $idCliente,
            'idlocal'         => $this->request->getPost('idlocal'),
            'idpedido'        => $this->request->getPost('idpedido') ?: null,
            'idcotizacion'    => $this->request->getPost('idcotizacion') ?: null,
            'idlinea'         => $this->request->getPost('idlinea'),
            'idcanal'         => $this->request->getPost('idcanal'),
            'idvendedor'      => session()->get('idusuario'),
            'idestado'        => $this->request->getPost('idestado'),
            'formapago'       => 'CONTADO',
            'idcajero'        => session()->get('idusuario'),
            'tipodocumento'   => $this->request->getPost('tipodocumento'),
            'cdr'             => null,
            'estafacturado'   => 0,
            'fecha'           => date('Y-m-d H:i:s'),
            'tipodescuento'   => $tipoDescuento,
            'valordescuento'  => $valorDescuento,
            'motivodescuento' => trim((string) $this->request->getPost('motivo_descuento')),
            'request_token' => $this->request->getPost('request_token')
        ];

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
            'direccion'   => trim((string) $this->request->getPost('direccion_entrega')),
            'referencia'  => trim((string) $this->request->getPost('referencia_entrega')),
            'telefono'    => trim((string) $this->request->getPost('telefono_entrega')),
            'idtecnico'   => $idTecnicoEntrega ?: null,
        ];

        $resultado = $ventaModel->registrarVenta($dataVenta, $detallesSeguros, $pagos, $entrega);

        if ($resultado) {
            session()->set('idlocal', (int) $dataVenta['idlocal']);
            session()->setFlashdata('ventaRegistrada', true);
            if (!empty($dataVenta['idpedido'])) session()->setFlashdata('pedidoVendido', (int) $dataVenta['idpedido']);
            if (!empty($dataVenta['idcotizacion']) || !empty($dataVenta['idpedido'])) {
                $cotizacionVendida = db_connect()->table('ventas')->where('request_token', $dataVenta['request_token'])->get()->getRow('idcotizacion');
                if ($cotizacionVendida) session()->setFlashdata('cotizacionVendida', (int) $cotizacionVendida);
            }
            return redirect()->to('admin/ventas')->with('success', 'Venta registrada exitosamente.');
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
            return redirect()->to(base_url('admin/ventas'))->with('error', 'La venta solicitada no existe.');
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
        $anulacionComercial = $db->table('anulaciones_comerciales')
            ->where('idventa', $idventa)->get()->getRowArray();
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
            'anulacionComercial' => $anulacionComercial,
            'solicitudesAnulacion' => $solicitudes,
            'solicitudActiva' => $solicitudActiva,
            'puedeSolicitar' => in_array(session()->get('rol'), ['Administrador', 'Vendedor'], true),
            'puedeAutorizar' => session()->get('rol') === 'Administrador',
        ];

        return view('ventas/detalles', $data);
    }


    public function guardarClienteExpress()
    {
        // El filtro CSRF ya regeneró el token, incluso si la validación del cliente falla.
        $this->response->setHeader('X-CSRF-TOKEN', csrf_hash());
        $this->response->setHeader('X-CSRF-NAME', csrf_token());
        $this->response->setHeader('Cache-Control', 'no-store');

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Solicitud no permitida.'
            ]);
        }

        $clienteModel = model(\App\Models\PersonaModel::class);

        $documento = trim((string) $this->request->getPost('documento'));
        $tipoDoc = (string) $this->request->getPost('tipodoc');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $telefono = trim((string) $this->request->getPost('telefono'));

        $longitudDocumento = ['DNI' => 8, 'RUC' => 11][$tipoDoc] ?? 0;
        if (!$longitudDocumento || strlen($documento) !== $longitudDocumento || !ctype_digit($documento)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.'
            ]);
        }

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100 || preg_match('/\p{N}/u', $nombre)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'El nombre debe tener entre 2 y 100 caracteres y no contener números.'
            ]);
        }

        if ($telefono !== '' && (!ctype_digit($telefono) || strlen($telefono) > 9)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ingrese un teléfono válido.'
            ]);
        }

        $buscarCliente = static function () use ($clienteModel, $documento) {
            return $clienteModel
                ->where('numerodoc', $documento)
                ->first();
        };

        try {
            $existente = $buscarCliente() ?: model(\App\Models\TecnicoModel::class)->where('dni', $documento)->first();

            if ($existente) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Este documento ya está registrado. No se puede registrar nuevamente.'
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
                $existente = $buscarCliente() ?: model(\App\Models\TecnicoModel::class)->where('dni', $documento)->first();

                if ($existente) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Este documento ya está registrado. No se puede registrar nuevamente.'
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
