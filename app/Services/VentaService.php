<?php

namespace App\Services;

use App\Libraries\CobroVenta;
use App\Libraries\CostoVenta;
use App\Libraries\DescuentoVenta;
use App\Libraries\EntregaPedidoWeb;
use App\Libraries\ImportesCotizacion;
use App\Models\CotizacionModel;
use App\Models\DetVentaModel;
use App\Models\EntregaModel;
use App\Models\PagoVentaModel;
use App\Models\PedidoModel;
use App\Models\PersonaModel;
use App\Models\TecnicoModel;
use App\Models\TipoPagoModel;
use App\Models\VentaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Concentra la lógica de negocio de las ventas: preparación del formulario,
 * consultas de historial/cartera/detalle y el registro transaccional con
 * validación de precios, descuentos, stock y series.
 */
class VentaService
{
    private BaseConnection $db;
    private VoucherStorage $vouchers;

    public function __construct(?BaseConnection $db = null, ?VoucherStorage $vouchers = null)
    {
        $this->db = $db ?? db_connect();
        $this->vouchers = $vouchers ?? new VoucherStorage();
    }

    /**
     * Datos para el historial de ventas.
     *
     * @param array{q:string,desde:string,hasta:string,estado:string,vendedor:string} $filtros
     */
    public function historial(array $filtros, bool $puedeAutorizar): array
    {
        $ventaModel = model(VentaModel::class);
        $db = $this->db;

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

        return [
            'titulo' => 'Historial de Ventas',
            'filtros' => $filtros,
            'vendedores' => $db->table('usuarios')->select('idusuario, nombreusuario')->orderBy('nombreusuario')->get()->getResultArray(),
            'ventas' => $ventaModel->select('ventas.*, IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) as cliente, locales.nombrelocal as local, vendedor.nombreusuario AS vendedor, (SELECT e.estado FROM entregas e WHERE e.idventa = ventas.idventa ORDER BY e.identrega DESC LIMIT 1) AS estadoentrega')
                ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
                ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
                ->join('usuarios vendedor', 'vendedor.idusuario = ventas.idvendedor', 'left')
                ->orderBy('ventas.fecha', 'DESC')
                ->orderBy('ventas.idventa', 'DESC')
                ->paginate(25, 'ventas'),
            'pager' => $ventaModel->pager,
            'solicitudesPendientes' => $solicitudesPendientes,
            'puedeAutorizar' => $puedeAutorizar,
            'cuotasVencidas' => (int) $db->table('cuotas c')->join('ventas v', 'v.idventa = c.idventa')->where('v.fechaanulacion', null)->where('c.estado !=', 'PAGADA')->where('c.fechavencimiento <', date('Y-m-d'))->countAllResults(),
        ];
    }

    /**
     * Datos de la cartera de cuotas.
     */
    public function carteraCuotas(string $estado, string $q): array
    {
        $db = $this->db;
        $consulta = $db->table('cuotas c')->select("c.*, v.serie, v.numero, v.fecha AS fechaventa, v.total, COALESCE(NULLIF(p.razonsocial, ''), CONCAT_WS(' ', p.nombres, p.apellidos)) AS cliente, p.numerodoc, DATEDIFF(CURDATE(), c.fechavencimiento) AS diasvencida", false)
            ->join('ventas v', 'v.idventa = c.idventa')->join('personas p', 'p.idpersona = v.idcliente')
            ->where('v.formapago', 'CUOTAS')->where('v.fechaanulacion', null);
        if ($estado === 'VENCIDA') $consulta->where('c.estado !=', 'PAGADA')->where('c.fechavencimiento <', date('Y-m-d'));
        elseif ($estado === 'PENDIENTE') $consulta->where('c.estado !=', 'PAGADA')->where('c.fechavencimiento >=', date('Y-m-d'));
        elseif ($estado === 'PAGADA') $consulta->where('c.estado', 'PAGADA');
        if ($q !== '') $consulta->groupStart()->like('v.numero', $q)->orLike('v.serie', $q)->orLike('p.numerodoc', $q)->orLike('p.nombres', $q)->orLike('p.apellidos', $q)->orLike('p.razonsocial', $q)->groupEnd();
        $cuotas = $consulta->orderBy('c.fechavencimiento', 'ASC')->orderBy('c.idventa')->orderBy('c.nrocuota')->limit(500)->get()->getResultArray();
        $pendientes = $db->table('cuotas c')->select('COUNT(*) AS cantidad, COALESCE(SUM(c.monto - c.montopagado), 0) AS monto', false)->join('ventas v', 'v.idventa = c.idventa')->where('v.formapago', 'CUOTAS')->where('v.fechaanulacion', null)->where('c.estado !=', 'PAGADA')->get()->getRowArray();
        $vencidas = $db->table('cuotas c')->select('COUNT(*) AS cantidad, COALESCE(SUM(c.monto - c.montopagado), 0) AS monto', false)->join('ventas v', 'v.idventa = c.idventa')->where('v.formapago', 'CUOTAS')->where('v.fechaanulacion', null)->where('c.estado !=', 'PAGADA')->where('c.fechavencimiento <', date('Y-m-d'))->get()->getRowArray();

        return compact('cuotas', 'estado', 'q', 'pendientes', 'vencidas') + ['titulo' => 'Cartera de cuotas'];
    }

    /**
     * Prepara todos los datos de la vista de creación de venta.
     *
     * Devuelve ['data' => [...]] o ['redirect' => 'ruta', 'error' => 'mensaje'].
     */
    public function prepararFormulario(int $idUsuario, int $idPedido, int $idCotizacion, bool $desdePedido, bool $desdeCotizacion): array
    {
        $db = $this->db;
        $personaModel  = model(PersonaModel::class);
        $tipoPagoModel = model(TipoPagoModel::class);

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

        $rolActual = $db->table('usuarios u')
            ->select('roles.rol')
            ->join('roles', 'roles.idrol = u.idrol')
            ->where('u.idusuario', $idUsuario)
            ->where('u.estado', 1)
            ->get()->getRow('rol');

        $cajaService = new CajaService($db);
        $locales = $cajaService->locales($idUsuario);
        $localUsuario = (int) session()->get('idlocal');
        if ($db->fieldExists('idlocal', 'usuarios')) {
            $localUsuario = (int) ($db->table('usuarios')->where('idusuario', $idUsuario)->get()->getRow('idlocal') ?: $localUsuario);
        }
        // Si no hay asignación, recordar el último local utilizado por este vendedor.
        if (!$localUsuario) $localUsuario = (int) $db->table('ventas')->where('idvendedor', $idUsuario)->orderBy('idventa', 'DESC')->get(1)->getRow('idlocal');
        $idsLocales = array_map('intval', array_column($locales, 'idlocal'));
        if (!in_array($localUsuario, $idsLocales, true)) $localUsuario = count($locales) === 1 ? (int) $locales[0]['idlocal'] : 0;
        $estados = array_values(array_filter(
            $db->table('estadoventa')->get()->getResultArray(),
            static fn (array $fila): bool => VentaEstadoInicial::permitido($fila['estado'] ?? null)
        ));
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
        $avisosPedido = [];
        if ($idPedido > 0) {
            $pedidos = model(PedidoModel::class);
            $pedidoSeleccionado = $pedidos->obtener($idPedido);
            if (!$pedidoSeleccionado || $pedidoSeleccionado['estado_codigo'] !== 'CONFIRMADO' || $pedidoSeleccionado['idventa']) {
                return ['redirect' => 'admin/pedidos', 'error' => 'El pedido no está confirmado o ya fue convertido.'];
            }
            $clienteInicial = (int) $pedidoSeleccionado['idpersona'];
            $localUsuario = (int) $pedidoSeleccionado['idlocal'];
            $datosPedido = [
                'idcliente' => $clienteInicial, 'idlocal' => $localUsuario,
                'tipo_descuento' => $pedidoSeleccionado['tipodescuento'], 'valor_descuento' => $pedidoSeleccionado['valordescuento'], 'motivo_descuento' => $pedidoSeleccionado['motivodescuento'],
                'detalles' => array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $pedidos->detalles($idPedido)),
            ];
            $entregaWeb = EntregaPedidoWeb::preparar($pedidoSeleccionado['datos_web']);
            $datosPedido += $entregaWeb['campos'];
            $avisosPedido = $entregaWeb['avisos'];
            if ($pedidoSeleccionado['tipopedido'] === 'TIENDA_WEB' && !$pedidoSeleccionado['datos_web']) {
                $datosPedido['tipoentrega'] = '';
                $avisosPedido[] = 'No se encontraron los datos de entrega del pedido web. Confirme la modalidad y el contacto con el cliente.';
            }
            foreach ($datosPedido['detalles'] as $lineaPedido) {
                $articuloPedido = $db->table('productos')->where('idproducto', $lineaPedido['idproducto'])->get()->getRowArray();
                if (!$articuloPedido || !(int) $articuloPedido['estado']) $avisosPedido[] = 'Hay artículos del pedido que ya no están activos. Puedes sustituirlos antes de cobrar.';
                elseif ($rolActual !== 'Administrador' && CobroVenta::centimos($lineaPedido['precioventa']) < CobroVenta::centimos($articuloPedido['precioventa'])) $avisosPedido[] = 'Revisa los precios: solo un administrador puede vender por debajo del catálogo vigente.';
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
        if ($pedidoSeleccionado) {
            if ($idCotizacion && $idCotizacion !== (int) ($pedidoSeleccionado['idcotizacion'] ?? 0)) {
                return ['redirect' => 'admin/pedidos', 'error' => 'La cotización no corresponde al pedido.'];
            }
            $idCotizacion = (int) ($pedidoSeleccionado['idcotizacion'] ?? 0);
        }
        if ($idCotizacion > 0) {
            $cotizaciones = model(CotizacionModel::class);
            $cotizacionSeleccionada = $cotizaciones->obtener($idCotizacion);
            if (!$cotizacionSeleccionada || (!$pedidoSeleccionado && ($cotizacionSeleccionada['vencida'] || !in_array($cotizacionSeleccionada['estado_codigo'], ['EMITIDA', 'ACEPTADA'], true) || $cotizacionSeleccionada['idpedido'] || $cotizacionSeleccionada['idventa']))) {
                return ['redirect' => 'admin/cotizaciones', 'error' => 'La cotización venció, no está emitida o ya fue convertida.'];
            }
            if (!$pedidoSeleccionado) {
                $clienteInicial = (int) $cotizacionSeleccionada['idpersona'];
                $localUsuario = (int) $cotizacionSeleccionada['idlocal'];
                $datosPedido = ['idcliente' => $clienteInicial, 'idlocal' => $localUsuario, 'tipo_descuento' => 'NINGUNO', 'valor_descuento' => '0', 'motivo_descuento' => '', 'detalles' => array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $cotizaciones->detalles($idCotizacion))];
            }
            $datosPedido['idlinea'] = (int) $cotizacionSeleccionada['idlinea'];
        }

        return ['data' => [
            'pedidoSeleccionado' => $pedidoSeleccionado,
            'cotizacionSeleccionada' => $cotizacionSeleccionada,
            'datosPedido' => $datosPedido,
            'iniciarPedido' => ($idPedido > 0 && $desdePedido) || ($idCotizacion > 0 && $desdeCotizacion),
            'avisosPedido' => array_values(array_unique($avisosPedido)),
            'localInicial' => $localUsuario,
            'estadoInicial' => $estadoInicial,
            'clienteInicial' => $clienteInicial,
            'requestToken' => bin2hex(random_bytes(32)),
            'puedeAplicarDescuento' => $rolActual === 'Administrador',
            'titulo'        => 'Registrar Nueva Venta',
            'clientes'      => $clientes,
            'locales'       => $locales,
            'productos' => [],
            'servicios' => [],
            'combos' => [],
            'comboComponentes' => [],
            'tipospago'     => $tipoPagoModel->where('estado', 1)->findAll(),
            'aperturasCaja' => $cajaService->aperturasPropias($idUsuario),
            'cuentasDestino' => $db->table('cuentasbancarias')->select('idcuenta, banco, numerocuenta')
                ->where('estado', 1)->where('moneda', 'PEN')->orderBy('banco, numerocuenta')->get()->getResultArray(),
            'lineas'        => $db->table('lineas')->get()->getResultArray(),
            'canales'       => $db->table('canalesventa')->get()->getResultArray(),
            'estadosVenta'  => $estados,
            'stockPorLocal' => json_encode($stockPorLocal),
            'seriesDisponibles' => json_encode($seriesDisponibles),
            'tecnicosEntrega' => model(TecnicoModel::class)->activos(),
        ]];
    }

    /**
     * Datos completos para la vista de detalle de una venta.
     */
    public function obtenerDetalle(int $idventa): ?array
    {
        $ventaModel     = model(VentaModel::class);
        $detVentaModel  = model(DetVentaModel::class);
        $pagoVentaModel = model(PagoVentaModel::class);
        $db = $this->db;

        $venta = $ventaModel->select('ventas.*,
                                      IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) as cliente,
                                      personas.numerodoc,
                                      locales.nombrelocal as local')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
            ->where('ventas.idventa', $idventa)
            ->first();

        if (!$venta) {
            return null;
        }

        $detalles = $detVentaModel->select('detventa.*, productos.nombre as producto')
            ->join('productos', 'productos.idproducto = detventa.idproducto', 'left')
            ->where('detventa.idventa', $idventa)
            ->findAll();

        $pagos = $pagoVentaModel->select('pagoventa.*, tipospago.tipopago, cb.banco, cb.numerocuenta')
            ->join('tipospago', 'tipospago.idtipopago = pagoventa.idtipopago', 'left')
            ->join('cuentasbancarias cb', 'cb.idcuenta = pagoventa.idcuenta', 'left')
            ->where('pagoventa.idventa', $idventa)
            ->findAll();

        $entregaComercial = $db->table('entregas')->select('identrega, estado, tipoentrega, fechaencamino, idtecnico, cobrocontraentrega')->where('idventa', $idventa)->get()->getRowArray();
        $solicitudes = $db->table('solicitudes_anulacion s')
            ->select('s.*, CONCAT(COALESCE(p.nombres, ""), " ", COALESCE(p.apellidos, "")) AS solicitante')
            ->join('usuarios u', 'u.idusuario = s.idsolicitante')
            ->join('personas p', 'p.idpersona = u.idpersona')
            ->where('s.idventa', $idventa)
            ->orderBy('s.idsolicitud', 'DESC')
            ->get()->getResultArray();
        $anulacionComercial = $db->table('anulaciones_comerciales')
            ->where('idventa', $idventa)->get()->getRowArray();
        $reembolsos = $anulacionComercial ? $db->table('reembolsos_venta r')
            ->select('r.*, tp.tipopago, u.nombreusuario')
            ->join('pagoventa pv', 'pv.idpagoventa = r.idpagoventa')
            ->join('tipospago tp', 'tp.idtipopago = pv.idtipopago')
            ->join('usuarios u', 'u.idusuario = r.idusuario')
            ->where('r.idanulacion', (int) $anulacionComercial['idanulacion'])
            ->orderBy('r.idreembolso')->get()->getResultArray() : [];
        $solicitudActiva = null;
        foreach ($solicitudes as $solicitud) {
            if (in_array($solicitud['estado'], ['PENDIENTE', 'AUTORIZADA'], true)) {
                $solicitudActiva = $solicitud;
                break;
            }
        }

        return [
            'titulo' => 'Detalle de Venta N° ' . $idventa,
            'venta' => $venta,
            'detalles' => $detalles,
            'pagos' => $pagos,
            'entregaComercial' => $entregaComercial,
            'anulacionComercial' => $anulacionComercial,
            'reembolsos' => $reembolsos,
            'aperturasReembolso' => session()->get('rol') === 'Administrador' ? (new CajaService($db))->aperturasPropias((int) session()->get('idusuario')) : [],
            'solicitudesAnulacion' => $solicitudes,
            'solicitudActiva' => $solicitudActiva,
            'puedeSolicitar' => in_array(session()->get('rol'), ['Administrador', 'Vendedor'], true),
            'puedeAutorizar' => session()->get('rol') === 'Administrador',
            'saldoCobro' => (new CobroPosteriorService($db))->saldo((int) $idventa)['saldo'],
            'aperturasCobro' => (new CajaService($db))->aperturasPropias((int) session()->get('idusuario')),
            'mediosCobro' => $db->table('tipospago')->where('estado', 1)->orderBy('idtipopago')->get()->getResultArray(),
            'cuentasCobro' => $db->table('cuentasbancarias')->where('estado', 1)->where('moneda', 'PEN')->orderBy('banco, numerocuenta')->get()->getResultArray(),
            'tecnicosCobro' => $db->table('tecnicos')->where('estado', 'ACTIVO')->orderBy('apellidos, nombres')->get()->getResultArray(),
            'cuotas' => $db->table('cuotas')->where('idventa', $idventa)->orderBy('nrocuota')->get()->getResultArray(),
            'cuotasCobro' => $db->table('cuotas')->where('idventa', $idventa)->whereIn('estado', ['PENDIENTE', 'PARCIAL'])->orderBy('nrocuota')->get()->getResultArray(),
        ];
    }

    /**
     * Registra una venta completa dentro de una transacción.
     *
     * @return bool true si se registró (o si el request_token ya existía).
     */
    public function registrar($dataVenta, $detalles, $pagos, $entrega = null, array $archivosVoucher = []): bool
    {
        $db = $this->db;
        $vouchers = $this->vouchers;
        $vouchersNuevos = [];

        $idTecnico = $entrega['idtecnico'] ?? null;
        if ($idTecnico !== null && $idTecnico !== '') {
            $tecnico = model(TecnicoModel::class)->find((int) $idTecnico);
            if (!$tecnico || $tecnico['estado'] !== 'ACTIVO') {
                session()->setFlashdata('error', 'Selecciona un técnico activo válido.');
                return false;
            }
        }

        if (!$db->transBegin()) {
            session()->setFlashdata('error', 'No se pudo iniciar el registro de la venta.');
            return false;
        }

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
            (new CajaService($db))->validarLocalAsignado((int) $dataVenta['idlocal'], (int) $dataVenta['idvendedor']);
            $estado = $db->table('estadoventa')->select('estado')->where('idestado', (int) $dataVenta['idestado'])->get()->getRow('estado');
            if (!VentaEstadoInicial::permitido($estado)) {
                throw new \InvalidArgumentException('El estado inicial de la venta no está permitido.');
            }
            if (($dataVenta['tipodocumento'] ?? '') !== 'NOTA_VENTA') throw new \InvalidArgumentException('Seleccione nota de venta.');
            $formaPago = (string) ($dataVenta['formapago'] ?? 'CONTADO');
            if (!in_array($formaPago, ['CONTADO', 'ADELANTO_SALDO', 'CUOTAS'], true)) throw new \InvalidArgumentException('Selecciona una forma de pago válida.');
            $idApertura = $dataVenta['idapertura'] ?? null;
            if ((int) $dataVenta['idcajero'] !== (int) $dataVenta['idvendedor']) throw new \InvalidArgumentException('El cobro debe registrarse con tu propio usuario.');
            $apertura = null;
            if ($pagos) {
                if (!is_scalar($idApertura) || !ctype_digit((string) $idApertura) || (int) $idApertura < 1) {
                    throw new \InvalidArgumentException('Abre tu caja y selecciona la apertura antes de cobrar.');
                }
                $apertura = (new CajaService($db))->bloquearCobro((int) $idApertura, (int) $dataVenta['idlocal'], (int) $dataVenta['idcajero']);
            }
            // La apertura pertenece a los pagos, no a la cabecera de ventas.
            unset($dataVenta['idapertura']);
            $rolVendedor = $db->table('usuarios u')->select('r.rol')->join('roles r', 'r.idrol = u.idrol')->where('u.idusuario', (int) $dataVenta['idvendedor'])->get()->getRow('rol');
            $configuracion = (new ProductoConfiguracionService($db))->bloquearParaVenta($detalles);
            $articulos = $configuracion['productos'];
            foreach ($detalles as &$detalleSeguro) {
                if (!is_array($detalleSeguro)) throw new \InvalidArgumentException('Detalle de venta inválido.');
                $articulo = $articulos[(int) $detalleSeguro['idproducto']] ?? null;
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
                $precioCentimos = CobroVenta::centimos($precioSolicitado);
                if ($precioCentimos <= 0) throw new \InvalidArgumentException('El precio debe ser mayor que cero.');
                if ($precioCentimos < CobroVenta::centimos($articulo['precioventa']) && $rolVendedor !== 'Administrador') {
                    throw new \InvalidArgumentException('Solo un administrador puede reducir el precio de catálogo.');
                }
                $detalleSeguro['precioventa'] = number_format($precioCentimos / 100, 2, '.', '');
            }
            unset($detalleSeguro);
            $detVentaModel  = model(DetVentaModel::class);
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

            if (empty($pagos) && $formaPago === 'CONTADO') {
                throw new \RuntimeException('Debe registrar al menos una forma de pago.');
            }

            if (empty($detalles) || empty($dataVenta['idlocal'])) {
                throw new \RuntimeException('Debe indicar productos y almacén.');
            }

            if ($entrega === null) {
                throw new \RuntimeException('La venta requiere una entrega para gestionar sus reservas.');
            }
            $contraEntrega = !empty($entrega['cobrocontraentrega']);
            $entrega = EntregaPedidoWeb::normalizarVenta($entrega);
            if ($contraEntrega && $entrega['tipoentrega'] !== 'DOMICILIO') throw new \InvalidArgumentException('El cobro contraentrega requiere entrega a domicilio.');
            if (!$pagos && $formaPago === 'ADELANTO_SALDO' && !$contraEntrega) throw new \InvalidArgumentException('Una venta sin adelanto debe marcarse para cobrar al entregar.');
            $entrega['cobrocontraentrega'] = $contraEntrega ? 1 : 0;

            $importes = DescuentoVenta::calcular(
                $detalles,
                (string) ($dataVenta['tipodescuento'] ?? 'NINGUNO'),
                $dataVenta['valordescuento'] ?? '0',
                (string) ($dataVenta['motivodescuento'] ?? '')
            );

            $dataVenta = array_merge($dataVenta, $importes);
            if ($cotizacionOrigen) $dataVenta = array_merge($dataVenta, ImportesCotizacion::desglosar($importes['total'], $cotizacionOrigen['porcentajeigv']), ['porcentajeigv' => $cotizacionOrigen['porcentajeigv'], 'tipocambio' => $cotizacionOrigen['tipocambio']]);

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

                $producto = $articulos[$idproducto] ?? null;

                if (!$producto) {
                    throw new \RuntimeException('Producto inexistente: ' . $idproducto);
                }

                $componentes = [];

                if ($producto['tipo'] === 'COMBO') {
                    $filas = $configuracion['componentes'][$idproducto] ?? [];

                    if (!$filas) {
                        throw new \RuntimeException('El combo no tiene componentes configurados.');
                    }

                    foreach ($filas as $fila) {
                        $idComponente = (int) $fila['idproducto'];
                        $componente = $articulos[$idComponente] ?? null;

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
            $costosKardex = [];

            foreach ($cantidades as $idproducto => $cantidad) {
                $fila = $db->query(
                    'SELECT idkardex, stockactual, costopromedio
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

                $costosKardex[$idproducto] = $fila['costopromedio'];

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
                    $nombreArticulo = $articulos[$idproducto]['nombre'] ?? (string) $idproducto;
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

            // Congelar el costo del local al registrar la venta nueva. No se
            // recalcula al entregar ni se modifican detalles históricos.
            $costosPorDetalle = CostoVenta::calcular($detalles, $articulos, $componentesPorDetalle, $costosKardex);
            $pagosPreparados = $pagos
                ? (new SoporteCobroService($db, $vouchers))->preparar($pagos, $archivosVoucher, $dataVenta['total'], $formaPago === 'CONTADO')
                : [];
            $inicialCentimos = 0;
            foreach ($pagosPreparados as $pagoPreparado) $inicialCentimos += CobroVenta::centimos($pagoPreparado['monto']);
            if ($formaPago === 'ADELANTO_SALDO' && $inicialCentimos >= CobroVenta::centimos($dataVenta['total'])) {
                throw new \InvalidArgumentException('Usa contado cuando el pago inicial cubre el total de la venta.');
            }
            $dataVenta['montoinicial'] = number_format($inicialCentimos / 100, 2, '.', '');
            $cronograma = [];
            $primeraFecha = (string) ($dataVenta['fechaprimervencimiento'] ?? '');
            unset($dataVenta['fechaprimervencimiento']);
            if ($formaPago === 'CUOTAS') {
                $cronograma = (new CronogramaCuotasService())->generar(
                    $dataVenta['total'],
                    $dataVenta['montoinicial'],
                    $dataVenta['nrocuotas'] ?? null,
                    (string) ($dataVenta['frecuenciacuotas'] ?? ''),
                    $primeraFecha,
                    substr((string) $dataVenta['fecha'], 0, 10)
                );
            } else {
                $dataVenta['nrocuotas'] = null;
                $dataVenta['frecuenciacuotas'] = null;
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

            foreach ($cronograma as $cuota) {
                if (!$db->table('cuotas')->insert(['idventa' => $idventa] + $cuota)) {
                    throw new \RuntimeException('No se pudo guardar el cronograma de cuotas.');
                }
            }

            foreach ($pagosPreparados as $pago) {
                $montoPagado = (float) $pago['monto'];
                $voucher = null;
                if ($pago['archivo']) $vouchersNuevos[] = $voucher = $vouchers->guardar($pago['archivo']);

                $idPago = $pagoVentaModel->insert([
                    'idventa' => $idventa,
                    'idtipopago' => $pago['idtipopago'],
                    'idusuariocobro' => $dataVenta['idcajero'],
                    'idapertura' => $apertura['idapertura'],
                    'idcuenta' => $pago['idcuenta'],
                    'voucher' => $voucher,
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

            foreach ($detalles as $indice => $item) {
                $idproducto = $item['idproducto'];
                $cantidad = (float) $item['cantidad'];
                $precioventa = (float) $item['precioventa'];

                $producto = $articulos[(int) $idproducto];
                $costoUnitario = $costosPorDetalle[$indice];

                $idDetalle = $detVentaModel->insert([
                    'idventa' => $idventa,
                    'idproducto' => $idproducto,
                    'cantidad' => $cantidad,
                    'precioventa' => $precioventa,
                    'costounitario' => $costoUnitario,
                    'garantiameses' => $producto['garantiameses'] ?? 0,
                    'nombre_snapshot' => $producto['nombre'],
                    'tipo_snapshot' => $producto['tipo'],
                    'controlaserie_snapshot' => (int) $producto['controlaserie'],
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
                        'cantidad' => $componente['cantidad'],
                        'nombre_snapshot' => $articulos[$componente['idproducto']]['nombre'],
                        'tipo_snapshot' => $articulos[$componente['idproducto']]['tipo'],
                        'controlaserie_snapshot' => (int) $articulos[$componente['idproducto']]['controlaserie'],
                        'garantiameses' => (int) ($articulos[$componente['idproducto']]['garantiameses'] ?? 0),
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
                $datosEntrega = ['idventa' => $idventa] + $entrega + ['estado' => 'PENDIENTE', 'cobrocontraentrega' => 0];

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
            $vouchers->descartar($vouchersNuevos);

            log_message('warning', 'Validación de venta: ' . $e->getMessage());
            session()->setFlashdata('error', $e->getMessage());

            return false;

        } catch (\Throwable $e) {
            $db->transRollback();
            $vouchers->descartar($vouchersNuevos);

            log_message('error', 'Error en VentaService::registrar: ' . $e->getMessage());
            session()->setFlashdata(
                'error',
                'No se pudo registrar la venta. Inténtelo nuevamente.'
            );

            return false;
        }
    }
}
