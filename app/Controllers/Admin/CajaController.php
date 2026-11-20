<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ArqueoCaja;
use App\Services\CajaService;
use App\Services\VoucherStorage;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class CajaController extends BaseController
{
    protected function almacenVouchers(): VoucherStorage
    {
        return new VoucherStorage();
    }

    private function usuario(): int
    {
        return (int) session('idusuario');
    }

    private function texto(string $campo): string
    {
        $valor = $this->request->getPost($campo) ?? '';
        if (!is_scalar($valor)) throw new InvalidArgumentException('Revisa los datos del formulario.');
        return trim((string) $valor);
    }

    private function accion(callable $accion, string $mensaje, string $destino = 'admin/caja')
    {
        try {
            $accion();
            return redirect()->to(base_url($destino))->with('mensaje', $mensaje);
        } catch (Throwable $e) {
            log_message('error', 'Operación de caja: ' . $e->getMessage());
            return redirect()->to(base_url($destino))->withInput()->with(
                'error',
                $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo completar la operación de caja. Inténtalo nuevamente.'
            );
        }
    }

    private function estado(): int
    {
        $estado = $this->texto('estado');
        if (!in_array($estado, ['0', '1'], true)) throw new InvalidArgumentException('Estado inválido.');
        return (int) $estado;
    }

    public function index()
    {
        $db = db_connect();
        $servicio = new CajaService($db);
        $actual = $servicio->usuario($this->usuario());
        $locales = $servicio->locales($this->usuario());
        $ids = array_column($locales, 'idlocal');
        $cajas = $db->table('cajas c')->select('c.*, l.nombrelocal')->join('locales l', 'l.idlocal = c.idlocal')->where('c.estado', 1);
        $ids ? $cajas->whereIn('c.idlocal', $ids) : $cajas->where('c.idcaja', 0);
        $consulta = $db->table('aperturacaja a')->select('a.*, c.nombre AS caja, l.nombrelocal, u.nombreusuario, ca.idcierreadministrativo')
            ->join('cajas c', 'c.idcaja = a.idcaja')->join('locales l', 'l.idlocal = c.idlocal')
            ->join('usuarios u', 'u.idusuario = a.idusuario')
            ->join('cierres_caja_administrativos ca', 'ca.idapertura = a.idapertura', 'left');
        if ($actual['rol'] !== 'Administrador') $consulta->where('a.idusuario', $this->usuario());
        return view('caja/index', [
            'titulo' => 'Caja y cobros',
            'esAdmin' => $actual['rol'] === 'Administrador',
            'cajas' => $cajas->orderBy('l.nombrelocal, c.nombre')->get()->getResultArray(),
            'propias' => $servicio->aperturasPropias($this->usuario()),
            'aperturas' => $consulta->orderBy('a.idapertura', 'DESC')->get(50)->getResultArray(),
        ]);
    }

    public function abrir()
    {
        return $this->accion(
            fn() => (new CajaService())->abrir((int) $this->texto('idcaja'), $this->texto('montoinicial'), $this->usuario()),
            'Caja abierta. Ya puedes vincular tus ventas a esta apertura.'
        );
    }

    public function detalles(int $id)
    {
        $db = db_connect();
        $servicio = new CajaService($db);
        try {
            $apertura = $servicio->obtener($id, $this->usuario());
        } catch (InvalidArgumentException $e) {
            return redirect()->to(base_url('admin/caja'))->with('error', $e->getMessage());
        }
        $pagos = $servicio->pagos($id);
        $movimientos = $servicio->movimientos($id);
        $actual = $servicio->usuario($this->usuario());
        $arqueo = $apertura['estado'] === 'ABIERTA'
            ? ArqueoCaja::calcular($db->table('tipospago')->orderBy('idtipopago')->get()->getResultArray(), $pagos, $apertura['montoinicial'], $movimientos)
            : $db->table('cierrecaja_detalle cd')->select('cd.*, tp.tipopago')->join('tipospago tp', 'tp.idtipopago = cd.idtipopago')->where('cd.idapertura', $id)->orderBy('cd.idtipopago')->get()->getResultArray();
        return view('caja/detalles', [
            'titulo' => 'Apertura #' . $id,
            'apertura' => $apertura,
            'pagos' => $pagos,
            'movimientos' => $movimientos,
            'medios' => $db->table('tipospago')->where('estado', 1)->orderBy('idtipopago')->get()->getResultArray(),
            'arqueo' => $arqueo,
            'puedeCerrar' => $apertura['estado'] === 'ABIERTA' && (int) $apertura['idusuario'] === $this->usuario(),
            'puedeOperar' => $apertura['estado'] === 'ABIERTA' && (int) $apertura['idusuario'] === $this->usuario(),
            'puedeCerrarAdmin' => $apertura['estado'] === 'ABIERTA' && $actual['rol'] === 'Administrador' && (int) $apertura['idusuario'] !== $this->usuario(),
        ]);
    }

    public function cerrar(int $id)
    {
        return $this->accion(function () use ($id) {
            $contados = $this->request->getPost('contados');
            if (!is_array($contados)) throw new InvalidArgumentException('Completa el arqueo por cada medio de pago.');
            (new CajaService())->cerrar($id, $contados, $this->texto('observacion'), $this->usuario());
        }, 'Caja cerrada y arqueo guardado.', 'admin/caja/detalles/' . $id);
    }

    public function movimiento(int $id)
    {
        return $this->accion(fn() => (new CajaService())->registrarMovimiento(
            $id,
            (int) $this->texto('idtipopago'),
            $this->texto('tipo'),
            $this->texto('concepto'),
            $this->texto('monto'),
            $this->texto('descripcion'),
            $this->usuario(),
            $this->texto('token_operacion')
        ), 'Movimiento de caja registrado.', 'admin/caja/detalles/' . $id);
    }

    public function cierreAdministrativo(int $id)
    {
        return $this->accion(function () use ($id) {
            $contados = $this->request->getPost('contados');
            if (!is_array($contados)) throw new InvalidArgumentException('Completa el arqueo por cada medio de pago.');
            (new CajaService())->cerrarAdministrativamente($id, $contados, $this->texto('observacion'), $this->usuario());
        }, 'Caja cerrada administrativamente con trazabilidad.', 'admin/caja/detalles/' . $id);
    }

    public function configuracion()
    {
        $servicio = new CajaService();
        if ($servicio->usuario($this->usuario())['rol'] !== 'Administrador') {
            return redirect()->to(base_url('admin/caja'))->with('error', 'Solo un administrador puede configurar caja.');
        }
        $db = db_connect();
        return view('caja/configuracion', [
            'titulo' => 'Configuración de caja',
            'locales' => $servicio->locales($this->usuario()),
            'cajas' => $db->table('cajas c')->select('c.*, l.nombrelocal')->join('locales l', 'l.idlocal = c.idlocal')->orderBy('l.nombrelocal, c.nombre')->get()->getResultArray(),
            'cuentas' => $db->table('cuentasbancarias')->orderBy('banco, numerocuenta')->get()->getResultArray(),
            'medios' => $db->table('tipospago')->orderBy('idtipopago')->get()->getResultArray(),
        ]);
    }

    public function crearCaja()
    {
        return $this->accion(
            fn() => (new CajaService())->crearCaja((int) $this->texto('idlocal'), $this->texto('nombre'), $this->usuario()),
            'Punto de caja creado.',
            'admin/caja/configuracion'
        );
    }

    public function estadoCaja(int $id)
    {
        return $this->accion(
            fn() => (new CajaService())->cambiarCaja($id, $this->estado(), $this->usuario()),
            'Estado de caja actualizado.',
            'admin/caja/configuracion'
        );
    }

    public function crearCuenta()
    {
        return $this->accion(fn() => (new CajaService())->crearCuenta([
            'banco' => $this->texto('banco'),
            'numerocuenta' => $this->texto('numerocuenta'),
            'cci' => $this->texto('cci'),
            'moneda' => $this->texto('moneda'),
        ], $this->usuario()), 'Cuenta destino registrada.', 'admin/caja/configuracion');
    }

    public function estadoCuenta(int $id)
    {
        return $this->accion(
            fn() => (new CajaService())->cambiarCuenta($id, $this->estado(), $this->usuario()),
            'Estado de cuenta actualizado.',
            'admin/caja/configuracion'
        );
    }

    public function completarMedios()
    {
        return $this->accion(
            fn() => (new CajaService())->completarMedios($this->usuario()),
            'Medios completados sin modificar los existentes.',
            'admin/caja/configuracion'
        );
    }

    public function voucher(int $id)
    {
        $actual = (new CajaService())->usuario($this->usuario());
        $pago = db_connect()->table('pagoventa')->select('voucher, idusuariocobro')->where('idpagoventa', $id)->get()->getRowArray();
        if (!$pago || !$pago['voucher'] || ($actual['rol'] !== 'Administrador' && (int) $pago['idusuariocobro'] !== $this->usuario())) {
            throw PageNotFoundException::forPageNotFound();
        }
        try {
            $ruta = $this->almacenVouchers()->ruta($pago['voucher']);
        } catch (InvalidArgumentException) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (!is_file($ruta)) throw PageNotFoundException::forPageNotFound();
        return $this->response->download($ruta, null, true)->setFileName('voucher-' . $id . '.' . pathinfo($ruta, PATHINFO_EXTENSION))
            ->inline()->setHeader('Cache-Control', 'private, no-store')->setHeader('X-Content-Type-Options', 'nosniff');
    }
}
