<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\VentaService;

class VentaController extends BaseController
{
    public function index()
    {
        $puedeAutorizar = session()->get('rol') === 'Administrador';
        $filtros = array_map(static fn($valor) => is_scalar($valor) ? trim((string) $valor) : '', $this->request->getGet(['q', 'desde', 'hasta', 'estado', 'vendedor']));
        foreach (['desde', 'hasta'] as $fecha) {
            if ($filtros[$fecha] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $filtros[$fecha])) $filtros[$fecha] = '';
        }

        return view('ventas/index', (new VentaService())->historial($filtros, $puedeAutorizar));
    }

    public function cuotas()
    {
        $estado = strtoupper(trim((string) $this->request->getGet('estado')));
        if (!in_array($estado, ['VENCIDA', 'PENDIENTE', 'PAGADA', 'TODAS'], true)) $estado = 'VENCIDA';
        $q = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);

        return view('ventas/cuotas', (new VentaService())->carteraCuotas($estado, $q));
    }

    public function crear()
    {
        $preparacion = (new VentaService())->prepararFormulario(
            (int) session()->get('idusuario'),
            (int) $this->request->getGet('pedido'),
            (int) $this->request->getGet('cotizacion'),
            $this->request->getGet('desde_pedido') === '1',
            $this->request->getGet('desde_cotizacion') === '1'
        );

        if (isset($preparacion['redirect'])) {
            return redirect()->to(base_url($preparacion['redirect']))->with('error', $preparacion['error']);
        }

        return view('ventas/crear', $preparacion['data']);
    }

    public function guardar()
    {
        $idCliente = $this->request->getPost('idcliente');
        if (empty($idCliente) || $idCliente === 'false' || $idCliente === false) {
            return redirect()->back()->with('error', 'Por favor, seleccione un cliente válido antes de procesar la venta.')->withInput();
        }

        $detallesPost = $this->request->getPost('detalles');
        $pagos        = $this->request->getPost('pagos');
        if ($pagos === null) $pagos = [];

        if (empty($detallesPost) || !is_array($detallesPost)) {
            return redirect()->back()->with('error', 'Debes agregar al menos un producto a la venta.')->withInput();
        }

        if (!is_array($pagos)) return redirect()->back()->with('error', 'Los pagos no tienen un formato válido.')->withInput();
        $formaPago = strtoupper(trim((string) $this->request->getPost('formapago')));
        if (!in_array($formaPago, ['CONTADO', 'ADELANTO_SALDO', 'CUOTAS'], true)) return redirect()->back()->with('error', 'Selecciona una forma de pago válida.')->withInput();
        if ($formaPago === 'CONTADO' && !$pagos) return redirect()->back()->with('error', 'Debes registrar al menos una forma de pago.')->withInput();

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
            'formapago'       => $formaPago,
            'nrocuotas'       => $this->request->getPost('nrocuotas'),
            'frecuenciacuotas' => $this->request->getPost('frecuenciacuotas'),
            'fechaprimervencimiento' => $this->request->getPost('fechaprimervencimiento'),
            'idcajero'        => session()->get('idusuario'),
            'idapertura'      => $this->request->getPost('idapertura'),
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

        $entrega = [
            'tipoentrega' => $this->request->getPost('tipoentrega') ?? 'RECOJO_TIENDA',
            'direccion'   => trim((string) $this->request->getPost('direccion_entrega')),
            'referencia'  => trim((string) $this->request->getPost('referencia_entrega')),
            'telefono'    => trim((string) $this->request->getPost('telefono_entrega')),
            'idtecnico'   => $idTecnicoEntrega ?: null,
            'cobrocontraentrega' => $this->request->getPost('cobrocontraentrega') === '1' ? 1 : 0,
        ];

        $archivosVoucher = [];
        if (count($pagos) > 20) {
            return redirect()->back()->with('error', 'La venta admite hasta 20 pagos.')->withInput();
        }
        foreach (array_keys($pagos) as $indicePago) {
            if (!ctype_digit((string) $indicePago)) return redirect()->back()->with('error', 'Los pagos no tienen un formato válido.')->withInput();
            $archivosVoucher[$indicePago] = $this->request->getFile('voucher_pagos.' . $indicePago);
        }

        $resultado = (new VentaService())->registrar($dataVenta, $detallesPost, $pagos, $entrega, $archivosVoucher);

        if ($resultado) {
            session()->set('idlocal', (int) $dataVenta['idlocal']);
            session()->setFlashdata('ventaRegistrada', true);
            if (!empty($dataVenta['idpedido'])) session()->setFlashdata('pedidoVendido', (int) $dataVenta['idpedido']);
            if (!empty($dataVenta['idcotizacion']) || !empty($dataVenta['idpedido'])) {
                $cotizacionVendida = db_connect()->table('ventas')->where('request_token', $dataVenta['request_token'])->get()->getRow('idcotizacion');
                if ($cotizacionVendida) session()->setFlashdata('cotizacionVendida', (int) $cotizacionVendida);
            }
            return redirect()->to('admin/ventas')->with('success', 'Venta registrada exitosamente.');
        }

        $errorMsg = session()->getFlashdata('error') ?? 'No se pudo registrar la venta. Inténtelo nuevamente.';
        return redirect()->back()->with('error', $errorMsg)->withInput();
    }

    public function detalles($idventa)
    {
        $data = (new VentaService())->obtenerDetalle((int) $idventa);

        if ($data === null) {
            return redirect()->to(base_url('admin/ventas'))->with('error', 'La venta solicitada no existe.');
        }

        return view('ventas/detalles', $data);
    }

    public function cobrar(int $idventa)
    {
        $destino = 'admin/ventas/detalles/' . $idventa;
        try {
            $pago = $this->request->getPost('pago');
            if (!is_array($pago)) throw new \InvalidArgumentException('Completa los datos del cobro.');
            (new \App\Services\CobroPosteriorService())->registrar(
                $idventa,
                (int) $this->request->getPost('idapertura'),
                $pago,
                $this->request->getFile('voucher'),
                ($tecnico = (int) $this->request->getPost('idtecnicocobro')) > 0 ? $tecnico : null,
                ($cuota = (int) $this->request->getPost('idcuota')) > 0 ? $cuota : null,
                (int) session('idusuario'),
                trim((string) $this->request->getPost('token_operacion'))
            );
            return redirect()->to(base_url($destino))->with('success', 'Cobro registrado y agregado al arqueo de caja.');
        } catch (\Throwable $e) {
            log_message('error', 'Cobro posterior: ' . $e->getMessage());
            return redirect()->to(base_url($destino))->withInput()->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo registrar el cobro.');
        }
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

        if ($tipoDoc !== 'RUC' && preg_match('/\p{N}/u', $nombre)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'El nombre no debe contener números.'
            ]);
        }

        if ($telefono !== '' && (!ctype_digit($telefono) || strlen($telefono) > 9)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ingrese un teléfono válido.'
            ]);
        }

        $data = $clienteModel->datosCliente([
            'nombres' => $nombre,
            'tipodoc' => $tipoDoc,
            'numerodoc' => $documento,
            'telefono' => $telefono,
        ]);
        if (!$this->validateData($data, $clienteModel->reglasCliente($tipoDoc, false))) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(' ', $this->validator->getErrors()),
            ]);
        }
        $data['estado'] = 1;

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
