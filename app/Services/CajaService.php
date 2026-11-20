<?php

namespace App\Services;

use App\Libraries\ArqueoCaja;
use App\Libraries\CobroVenta;
use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CajaService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    private function lock(string $sql): string
    {
        return $this->db->DBDriver === 'MySQLi' ? $sql . ' FOR UPDATE' : $sql;
    }

    public function usuario(int $usuario, bool $bloquear = false): array
    {
        $sql = 'SELECT u.idusuario, r.rol FROM usuarios u JOIN roles r ON r.idrol = u.idrol WHERE u.idusuario = ? AND u.estado = 1';
        $fila = $this->db->query($bloquear ? $this->lock($sql) : $sql, [$usuario])->getRowArray();
        if (!$fila || !in_array($fila['rol'], ['Administrador', 'Vendedor'], true)) {
            throw new InvalidArgumentException('No tienes permiso para operar caja.');
        }
        return $fila;
    }

    private function administrador(int $usuario): void
    {
        if ($this->usuario($usuario, true)['rol'] !== 'Administrador') {
            throw new InvalidArgumentException('Solo un administrador puede configurar cajas y cuentas.');
        }
    }

    public function validarLocalAsignado(int $local, int $usuario): void
    {
        $actual = $this->usuario($usuario, true);
        $this->validarLocal($local, $actual);
    }

    private function validarLocal(int $local, array $usuario): void
    {
        if (!$this->db->table('locales')->where('idlocal', $local)->countAllResults()
            || !(new PermissionService($this->db))->canUseLocation((int) $usuario['idusuario'], $local)) {
            throw new InvalidArgumentException('El local no existe o no está asignado a tu usuario.');
        }
    }

    private function transaccion(callable $accion)
    {
        if (!$this->db->transBegin()) throw new RuntimeException('No se pudo iniciar la operación de caja.');
        try {
            $resultado = $accion();
            if (!$this->db->transStatus() || !$this->db->transCommit()) throw new RuntimeException('No se pudo confirmar la operación de caja.');
            return $resultado;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function locales(int $usuario): array
    {
        $actual = $this->usuario($usuario);
        $consulta = $this->db->table('locales l')->select('l.*');
        $consulta->join('usuario_local ul', 'ul.idlocal = l.idlocal')->where('ul.idusuario', $usuario);
        return $consulta->orderBy('l.nombrelocal')->get()->getResultArray();
    }

    public function abrir(int $caja, $montoInicial, int $usuario): int
    {
        $centimos = CobroVenta::centimos($montoInicial);
        return $this->transaccion(function () use ($caja, $centimos, $usuario) {
            // Orden común: usuario -> caja -> apertura. Serializa aperturas
            // concurrentes del mismo usuario o del mismo punto de venta.
            $actual = $this->usuario($usuario, true);
            $punto = $this->db->query($this->lock('SELECT * FROM cajas WHERE idcaja = ?'), [$caja])->getRowArray();
            if (!$punto || (int) $punto['estado'] !== 1) throw new InvalidArgumentException('Selecciona una caja activa.');
            $this->validarLocal((int) $punto['idlocal'], $actual);
            $abiertas = $this->db->query($this->lock("SELECT idapertura FROM aperturacaja WHERE estado = 'ABIERTA' AND (idusuario = ? OR idcaja = ?)"), [$usuario, $caja])->getResultArray();
            if ($abiertas) throw new InvalidArgumentException('Tu usuario o esta caja ya tiene una apertura vigente. Ciérrala antes de abrir otra.');
            if (!$this->db->table('tipospago')->where('tipopago', 'Efectivo')->where('estado', 1)->countAllResults()) {
                throw new InvalidArgumentException('Primero completa los medios de pago desde Configuración de caja.');
            }
            if (!$this->db->table('aperturacaja')->insert([
                'idcaja' => $caja, 'idusuario' => $usuario, 'fechaapertura' => date('Y-m-d H:i:s'),
                'montoinicial' => number_format($centimos / 100, 2, '.', ''), 'estado' => 'ABIERTA',
            ])) throw new RuntimeException('No se pudo registrar la apertura.');
            return (int) $this->db->insertID();
        });
    }

    /**
     * Debe llamarse DENTRO de la transacción de venta/cierre. El bloqueo de
     * apertura permanece hasta commit, impidiendo cobrar mientras se cierra.
     */
    public function bloquearCobro(int $apertura, int $local, int $usuario): array
    {
        $actual = $this->usuario($usuario, true);
        $referencia = $this->db->table('aperturacaja')->select('idcaja')->where('idapertura', $apertura)->get()->getRowArray();
        if (!$referencia) throw new InvalidArgumentException('Abre tu caja y selecciona la apertura antes de cobrar.');
        $caja = $this->db->query($this->lock('SELECT * FROM cajas WHERE idcaja = ?'), [(int) $referencia['idcaja']])->getRowArray();
        $fila = $this->db->query($this->lock('SELECT * FROM aperturacaja WHERE idapertura = ?'), [$apertura])->getRowArray();
        if (!$fila || $fila['estado'] !== 'ABIERTA' || (int) $fila['idusuario'] !== $usuario
            || !$caja || (int) $caja['estado'] !== 1 || (int) $caja['idlocal'] !== $local) {
            throw new InvalidArgumentException('La caja debe estar abierta por tu usuario y pertenecer al local de la venta.');
        }
        $this->validarLocal($local, $actual);
        return $fila;
    }

    public function aperturasPropias(int $usuario): array
    {
        $this->usuario($usuario);
        return $this->db->table('aperturacaja a')->select('a.*, c.nombre AS caja, c.idlocal')
            ->join('cajas c', 'c.idcaja = a.idcaja')->where('a.idusuario', $usuario)
            ->where('a.estado', 'ABIERTA')->where('c.estado', 1)->get()->getResultArray();
    }

    public function obtener(int $apertura, int $usuario): array
    {
        $actual = $this->usuario($usuario);
        $fila = $this->db->table('aperturacaja a')
            ->select('a.*, c.nombre AS caja, c.idlocal, l.nombrelocal, u.nombreusuario, ca.idcierreadministrativo, ca.fecha AS fecha_cierre_administrativo, ca.observacion AS observacion_administrativa')
            ->join('cajas c', 'c.idcaja = a.idcaja')->join('locales l', 'l.idlocal = c.idlocal')
            ->join('usuarios u', 'u.idusuario = a.idusuario')
            ->join('cierres_caja_administrativos ca', 'ca.idapertura = a.idapertura', 'left')
            ->where('a.idapertura', $apertura)->get()->getRowArray();
        if (!$fila || ($actual['rol'] !== 'Administrador' && (int) $fila['idusuario'] !== $usuario)) {
            throw new InvalidArgumentException('No puedes consultar esta apertura.');
        }
        return $fila;
    }

    public function pagos(int $apertura): array
    {
        return $this->db->table('pagoventa p')
            ->select('p.*, tp.tipopago, v.serie, v.numero, v.fechaanulacion, cb.banco, cb.numerocuenta')
            ->join('tipospago tp', 'tp.idtipopago = p.idtipopago')->join('ventas v', 'v.idventa = p.idventa')
            ->join('cuentasbancarias cb', 'cb.idcuenta = p.idcuenta', 'left')
            ->where('p.idapertura', $apertura)->orderBy('p.idpagoventa')->get()->getResultArray();
    }

    public function movimientos(int $apertura): array
    {
        return $this->db->table('movimientos_caja m')
            ->select('m.*, tp.tipopago, u.nombreusuario, r.idreembolso, ac.idventa')
            ->join('tipospago tp', 'tp.idtipopago = m.idtipopago')
            ->join('usuarios u', 'u.idusuario = m.idusuario')
            ->join('reembolsos_venta r', 'r.idmovimientocaja = m.idmovimientocaja', 'left')
            ->join('anulaciones_comerciales ac', 'ac.idanulacion = r.idanulacion', 'left')
            ->where('m.idapertura', $apertura)->orderBy('m.idmovimientocaja')->get()->getResultArray();
    }

    private function validarToken(string $token): string
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new InvalidArgumentException('Actualiza la página e intenta nuevamente.');
        return $token;
    }

    public function registrarMovimiento(int $apertura, int $medio, string $tipo, string $concepto, $monto, string $descripcion, int $usuario, string $token): int
    {
        $token = $this->validarToken($token);
        $descripcion = trim($descripcion);
        $tipo = strtoupper(trim($tipo));
        $concepto = strtoupper(trim($concepto));
        $centimos = CobroVenta::centimos($monto);
        $conceptoValido = ($tipo === 'INGRESO' && in_array($concepto, ['APORTE', 'AJUSTE'], true))
            || ($tipo === 'EGRESO' && in_array($concepto, ['RETIRO', 'GASTO', 'AJUSTE'], true));
        if ($centimos <= 0 || !in_array($tipo, ['INGRESO', 'EGRESO'], true)
            || !in_array($concepto, ['APORTE', 'RETIRO', 'GASTO', 'AJUSTE'], true)
            || !$conceptoValido
            || mb_strlen($descripcion) < 5 || mb_strlen($descripcion) > 255) {
            throw new InvalidArgumentException('Revisa el tipo, concepto, importe y descripción del movimiento.');
        }

        return $this->transaccion(function () use ($apertura, $medio, $tipo, $concepto, $centimos, $descripcion, $usuario, $token) {
            $existente = $this->db->query($this->lock('SELECT * FROM movimientos_caja WHERE token_operacion = ?'), [$token])->getRowArray();
            if ($existente) {
                if ((int) $existente['idapertura'] !== $apertura || (int) $existente['idusuario'] !== $usuario) {
                    throw new InvalidArgumentException('El formulario ya fue utilizado para otra operación.');
                }
                return (int) $existente['idmovimientocaja'];
            }
            $referencia = $this->db->table('aperturacaja a')->select('c.idlocal')
                ->join('cajas c', 'c.idcaja = a.idcaja')->where('a.idapertura', $apertura)->get()->getRowArray();
            if (!$referencia) throw new InvalidArgumentException('La apertura no existe.');
            $fila = $this->bloquearCobro($apertura, (int) $referencia['idlocal'], $usuario);
            $metodos = $this->db->query($this->lock('SELECT * FROM tipospago ORDER BY idtipopago'))->getResultArray();
            $metodo = null;
            foreach ($metodos as $actual) if ((int) $actual['idtipopago'] === $medio && (int) $actual['estado'] === 1) $metodo = $actual;
            if (!$metodo) throw new InvalidArgumentException('Selecciona un medio de pago activo.');
            $pagos = $this->db->query($this->lock('SELECT idtipopago, monto FROM pagoventa WHERE idapertura = ? ORDER BY idpagoventa'), [$apertura])->getResultArray();
            $movimientos = $this->db->query($this->lock('SELECT idtipopago, tipo, monto FROM movimientos_caja WHERE idapertura = ? ORDER BY idmovimientocaja'), [$apertura])->getResultArray();
            $movimientos[] = ['idtipopago' => $medio, 'tipo' => $tipo, 'monto' => number_format($centimos / 100, 2, '.', '')];
            ArqueoCaja::calcular($metodos, $pagos, $fila['montoinicial'], $movimientos);
            if (!$this->db->table('movimientos_caja')->insert([
                'idapertura' => $apertura, 'idtipopago' => $medio, 'idusuario' => $usuario,
                'tipo' => $tipo, 'concepto' => $concepto, 'monto' => number_format($centimos / 100, 2, '.', ''),
                'descripcion' => $descripcion, 'fecha' => date('Y-m-d H:i:s'), 'token_operacion' => $token,
            ])) throw new RuntimeException('No se pudo registrar el movimiento de caja.');
            return (int) $this->db->insertID();
        });
    }

    public function reembolsar(int $anulacion, int $pago, int $apertura, $monto, string $referencia, int $usuario, string $token): int
    {
        $token = $this->validarToken($token);
        $referencia = trim($referencia);
        if (mb_strlen($referencia) > 50) throw new InvalidArgumentException('La referencia admite hasta 50 caracteres.');
        $centimos = CobroVenta::centimos($monto);
        if ($centimos <= 0) throw new InvalidArgumentException('El reembolso debe ser positivo.');

        return $this->transaccion(function () use ($anulacion, $pago, $apertura, $centimos, $referencia, $usuario, $token) {
            $this->administrador($usuario);
            $existente = $this->db->query($this->lock('SELECT * FROM reembolsos_venta WHERE token_operacion = ?'), [$token])->getRowArray();
            if ($existente) {
                if ((int) $existente['idanulacion'] !== $anulacion || (int) $existente['idusuario'] !== $usuario) {
                    throw new InvalidArgumentException('El formulario ya fue utilizado para otra operación.');
                }
                return (int) $existente['idreembolso'];
            }
            $acta = $this->db->query($this->lock('SELECT ac.*, v.idlocal FROM anulaciones_comerciales ac JOIN ventas v ON v.idventa = ac.idventa WHERE ac.idanulacion = ?'), [$anulacion])->getRowArray();
            if (!$acta || $acta['estado_reembolso'] === 'NO_APLICA') throw new InvalidArgumentException('La anulación no tiene un reembolso pendiente.');
            $aperturaActual = $this->bloquearCobro($apertura, (int) $acta['idlocal'], $usuario);
            $cobro = $this->db->query($this->lock('SELECT p.*, tp.tipopago FROM pagoventa p JOIN tipospago tp ON tp.idtipopago = p.idtipopago WHERE p.idpagoventa = ?'), [$pago])->getRowArray();
            if (!$cobro || (int) $cobro['idventa'] !== (int) $acta['idventa']) throw new InvalidArgumentException('Selecciona un pago de la venta anulada.');
            if (!CobroVenta::esEfectivo($cobro['tipopago']) && $referencia === '') throw new InvalidArgumentException('Indica la referencia del reembolso electrónico.');
            $anteriores = $this->db->query($this->lock('SELECT monto FROM reembolsos_venta WHERE idpagoventa = ? ORDER BY idreembolso'), [$pago])->getResultArray();
            $devuelto = 0;
            foreach ($anteriores as $anterior) $devuelto += CobroVenta::centimos($anterior['monto']);
            if ($devuelto + $centimos > CobroVenta::centimos($cobro['monto'])) throw new InvalidArgumentException('El reembolso supera el saldo disponible de este pago.');

            $metodos = $this->db->query($this->lock('SELECT * FROM tipospago ORDER BY idtipopago'))->getResultArray();
            $pagosApertura = $this->db->query($this->lock('SELECT idtipopago, monto FROM pagoventa WHERE idapertura = ? ORDER BY idpagoventa'), [$apertura])->getResultArray();
            $movimientos = $this->db->query($this->lock('SELECT idtipopago, tipo, monto FROM movimientos_caja WHERE idapertura = ? ORDER BY idmovimientocaja'), [$apertura])->getResultArray();
            $movimientos[] = ['idtipopago' => (int) $cobro['idtipopago'], 'tipo' => 'EGRESO', 'monto' => number_format($centimos / 100, 2, '.', '')];
            ArqueoCaja::calcular($metodos, $pagosApertura, $aperturaActual['montoinicial'], $movimientos);

            $ahora = date('Y-m-d H:i:s');
            if (!$this->db->table('movimientos_caja')->insert([
                'idapertura' => $apertura, 'idtipopago' => (int) $cobro['idtipopago'], 'idusuario' => $usuario,
                'tipo' => 'EGRESO', 'concepto' => 'REEMBOLSO', 'monto' => number_format($centimos / 100, 2, '.', ''),
                'descripcion' => 'Reembolso de venta anulada #' . (int) $acta['idventa'], 'fecha' => $ahora, 'token_operacion' => $token,
            ])) throw new RuntimeException('No se pudo registrar la salida de caja.');
            $movimiento = (int) $this->db->insertID();
            if (!$this->db->table('reembolsos_venta')->insert([
                'idanulacion' => $anulacion, 'idpagoventa' => $pago, 'idmovimientocaja' => $movimiento,
                'idusuario' => $usuario, 'monto' => number_format($centimos / 100, 2, '.', ''),
                'referencia' => $referencia ?: null, 'fecha' => $ahora, 'token_operacion' => $token,
            ])) throw new RuntimeException('No se pudo registrar el reembolso.');
            $id = (int) $this->db->insertID();
            $total = $this->db->table('reembolsos_venta')->selectSum('monto', 'total')->where('idanulacion', $anulacion)->get()->getRow('total') ?? '0.00';
            $pendiente = CobroVenta::centimos($acta['monto_pagado']) - CobroVenta::centimos($total);
            if ($pendiente < 0) throw new RuntimeException('Los reembolsos superan el monto pagado de la anulación.');
            if (!$this->db->table('anulaciones_comerciales')->where('idanulacion', $anulacion)->update([
                'monto_pendiente_reembolso' => number_format($pendiente / 100, 2, '.', ''),
                'estado_reembolso' => $pendiente === 0 ? 'COMPLETADO' : 'PENDIENTE',
            ])) throw new RuntimeException('No se pudo actualizar el saldo del reembolso.');
            return $id;
        });
    }

    public function cerrar(int $apertura, array $contados, string $observacion, int $usuario): void
    {
        $this->transaccion(function () use ($apertura, $contados, $observacion, $usuario) {
            $referencia = $this->obtener($apertura, $usuario);
            // Incluso el administrador cierra solo su propia apertura.
            $fila = $this->bloquearCobro($apertura, (int) $referencia['idlocal'], $usuario);
            // Lecturas actuales, no la instantánea iniciada por obtener().
            // No bloquear ventas: una anulación no cambia el dinero recibido.
            $metodos = $this->db->query($this->lock('SELECT * FROM tipospago ORDER BY idtipopago'))->getResultArray();
            $pagos = $this->db->query($this->lock('SELECT idtipopago, monto FROM pagoventa WHERE idapertura = ? ORDER BY idpagoventa'), [$apertura])->getResultArray();
            $movimientos = $this->db->query($this->lock('SELECT idtipopago, tipo, monto FROM movimientos_caja WHERE idapertura = ? ORDER BY idmovimientocaja'), [$apertura])->getResultArray();
            $esperados = ArqueoCaja::calcular($metodos, $pagos, $fila['montoinicial'], $movimientos);
            foreach (ArqueoCaja::cierre($esperados, $contados, $observacion) as $detalle) {
                if (!$this->db->table('cierrecaja_detalle')->insert(['idapertura' => $apertura] + $detalle)) {
                    throw new RuntimeException('No se pudo guardar el arqueo.');
                }
            }
            if (!$this->db->table('aperturacaja')->where('idapertura', $apertura)->where('estado', 'ABIERTA')->update([
                'estado' => 'CERRADA', 'fechacierre' => date('Y-m-d H:i:s'), 'observacion' => trim($observacion) ?: null,
            ])) throw new RuntimeException('No se pudo cerrar la apertura.');
        });
    }

    public function cerrarAdministrativamente(int $apertura, array $contados, string $observacion, int $usuario): void
    {
        $observacion = trim($observacion);
        if (mb_strlen($observacion) < 10 || mb_strlen($observacion) > 500) throw new InvalidArgumentException('Explica el cierre administrativo entre 10 y 500 caracteres.');
        $this->transaccion(function () use ($apertura, $contados, $observacion, $usuario) {
            $this->administrador($usuario);
            $referencia = $this->db->table('aperturacaja')->select('idcaja')->where('idapertura', $apertura)->get()->getRowArray();
            if (!$referencia) throw new InvalidArgumentException('La apertura no existe.');
            $this->db->query($this->lock('SELECT * FROM cajas WHERE idcaja = ?'), [(int) $referencia['idcaja']])->getRowArray();
            $fila = $this->db->query($this->lock('SELECT * FROM aperturacaja WHERE idapertura = ?'), [$apertura])->getRowArray();
            if (!$fila || $fila['estado'] !== 'ABIERTA') throw new InvalidArgumentException('La apertura ya está cerrada.');
            $metodos = $this->db->query($this->lock('SELECT * FROM tipospago ORDER BY idtipopago'))->getResultArray();
            $pagos = $this->db->query($this->lock('SELECT idtipopago, monto FROM pagoventa WHERE idapertura = ? ORDER BY idpagoventa'), [$apertura])->getResultArray();
            $movimientos = $this->db->query($this->lock('SELECT idtipopago, tipo, monto FROM movimientos_caja WHERE idapertura = ? ORDER BY idmovimientocaja'), [$apertura])->getResultArray();
            $observacionArqueo = mb_substr($observacion, 0, 150);
            foreach (ArqueoCaja::cierre(ArqueoCaja::calcular($metodos, $pagos, $fila['montoinicial'], $movimientos), $contados, $observacionArqueo) as $detalle) {
                if (!$this->db->table('cierrecaja_detalle')->insert(['idapertura' => $apertura] + $detalle)) throw new RuntimeException('No se pudo guardar el arqueo administrativo.');
            }
            $ahora = date('Y-m-d H:i:s');
            if (!$this->db->table('aperturacaja')->where('idapertura', $apertura)->where('estado', 'ABIERTA')->update([
                'estado' => 'CERRADA', 'fechacierre' => $ahora, 'observacion' => mb_substr($observacion, 0, 255),
            ])) throw new RuntimeException('No se pudo cerrar la apertura.');
            if (!$this->db->table('cierres_caja_administrativos')->insert([
                'idapertura' => $apertura, 'idadministrador' => $usuario, 'fecha' => $ahora, 'observacion' => $observacion,
            ])) throw new RuntimeException('No se pudo guardar la trazabilidad del cierre administrativo.');
        });
    }

    public function crearCaja(int $local, string $nombre, int $usuario): void
    {
        $nombre = trim($nombre);
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 60) throw new InvalidArgumentException('El nombre de caja debe tener entre 2 y 60 caracteres.');
        $this->transaccion(function () use ($local, $nombre, $usuario) {
            $this->administrador($usuario);
            if (!$this->db->table('locales')->where('idlocal', $local)->countAllResults()) throw new InvalidArgumentException('Selecciona un local válido.');
            if ($this->db->table('cajas')->where('idlocal', $local)->where('nombre', $nombre)->countAllResults()) throw new InvalidArgumentException('Ya existe esa caja en el local.');
            if (!$this->db->table('cajas')->insert(['idlocal' => $local, 'nombre' => $nombre, 'estado' => 1])) throw new RuntimeException('No se pudo crear la caja.');
        });
    }

    public function cambiarCaja(int $caja, int $estado, int $usuario): void
    {
        $this->transaccion(function () use ($caja, $estado, $usuario) {
            $this->administrador($usuario);
            $actual = $this->db->query($this->lock('SELECT * FROM cajas WHERE idcaja = ?'), [$caja])->getRowArray();
            if (!$actual || !in_array($estado, [0, 1], true)) throw new InvalidArgumentException('Caja o estado inválido.');
            if ($estado === 0 && $this->db->table('aperturacaja')->where('idcaja', $caja)->where('estado', 'ABIERTA')->countAllResults()) {
                throw new InvalidArgumentException('No se puede desactivar una caja abierta.');
            }
            if (!$this->db->table('cajas')->where('idcaja', $caja)->update(['estado' => $estado])) throw new RuntimeException('No se pudo cambiar el estado.');
        });
    }

    public function crearCuenta(array $datos, int $usuario): void
    {
        $banco = trim((string) ($datos['banco'] ?? ''));
        $numero = trim((string) ($datos['numerocuenta'] ?? ''));
        $cci = trim((string) ($datos['cci'] ?? ''));
        $moneda = (string) ($datos['moneda'] ?? '');
        if (!in_array($banco, ['BCP', 'BBVA', 'Interbank'], true) || !preg_match('/^[0-9][0-9-]{4,28}[0-9]$/D', $numero)
            || ($cci !== '' && !preg_match('/^[0-9]{20}$/D', $cci)) || !in_array($moneda, ['PEN', 'USD'], true)) {
            throw new InvalidArgumentException('Revisa banco, número de cuenta (6 a 30 caracteres), CCI de 20 dígitos y moneda.');
        }
        $this->transaccion(function () use ($banco, $numero, $cci, $moneda, $usuario) {
            $this->administrador($usuario);
            if ($this->db->table('cuentasbancarias')->where('banco', $banco)->where('numerocuenta', $numero)->countAllResults()) throw new InvalidArgumentException('La cuenta ya existe.');
            if (!$this->db->table('cuentasbancarias')->insert([
                'banco' => $banco, 'numerocuenta' => $numero, 'cci' => $cci ?: null, 'moneda' => $moneda, 'estado' => 1,
            ])) throw new RuntimeException('No se pudo registrar la cuenta.');
        });
    }

    public function cambiarCuenta(int $cuenta, int $estado, int $usuario): void
    {
        $this->transaccion(function () use ($cuenta, $estado, $usuario) {
            $this->administrador($usuario);
            $fila = $this->db->query($this->lock('SELECT * FROM cuentasbancarias WHERE idcuenta = ?'), [$cuenta])->getRowArray();
            if (!$fila || !in_array($estado, [0, 1], true)) throw new InvalidArgumentException('Cuenta o estado inválido.');
            if (!$this->db->table('cuentasbancarias')->where('idcuenta', $cuenta)->update(['estado' => $estado])) throw new RuntimeException('No se pudo cambiar el estado.');
        });
    }

    public function completarMedios(int $usuario): void
    {
        $this->transaccion(function () use ($usuario) {
            $this->administrador($usuario);
            foreach (CobroVenta::MEDIOS as $nombre) {
                if (!$this->db->table('tipospago')->where('tipopago', $nombre)->countAllResults()
                    && !$this->db->table('tipospago')->insert(['tipopago' => $nombre, 'requierecuenta' => CobroVenta::esEfectivo($nombre) ? 0 : 1])) {
                    throw new RuntimeException('No se pudieron completar los medios de pago.');
                }
            }
        });
    }
}
