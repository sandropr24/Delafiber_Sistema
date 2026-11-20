<?php

namespace App\Services;

use App\Libraries\CobroVenta;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;

class SoporteCobroService
{
    public function __construct(private ?BaseConnection $db = null, private ?VoucherStorage $vouchers = null)
    {
        $this->db ??= db_connect();
        $this->vouchers ??= new VoucherStorage();
    }

    /** Valida sin mover archivos ni aceptar rutas o cuentas del formulario sin contrastarlas. */
    public function preparar(array $pagos, array $archivos, $total, bool $exigirTotal = true): array
    {
        if (!$pagos || count($pagos) > 20) throw new InvalidArgumentException('Registra entre 1 y 20 pagos.');
        $preparados = [];
        $suma = 0;
        $ids = [];
        foreach ($pagos as $pago) {
            if (is_array($pago) && is_scalar($pago['idcuenta'] ?? null) && ctype_digit((string) $pago['idcuenta'])) {
                $ids[(int) $pago['idcuenta']] = true;
            }
        }
        // Las ventas de distintas cajas pueden utilizar las mismas cuentas.
        // Adquirir los bloqueos en orden evita invertirlos en pagos mixtos.
        ksort($ids, SORT_NUMERIC);
        $cuentas = [];
        foreach (array_keys($ids) as $id) {
            $sql = 'SELECT * FROM cuentasbancarias WHERE idcuenta = ?';
            if ($this->db->DBDriver === 'MySQLi') $sql .= ' FOR UPDATE';
            $cuentas[$id] = $this->db->query($sql, [$id])->getRowArray();
        }
        foreach ($pagos as $indice => $pago) {
            if (!is_array($pago) || !ctype_digit((string) $indice) || !is_scalar($pago['idtipopago'] ?? null)
                || !ctype_digit((string) $pago['idtipopago'])) throw new InvalidArgumentException('Pago inválido.');
            $metodo = $this->db->table('tipospago')->where('idtipopago', (int) $pago['idtipopago'])->where('estado', 1)->get()->getRow('tipopago');
            if (!$metodo) throw new InvalidArgumentException('Selecciona un método de pago válido.');
            $normalizado = CobroVenta::normalizar($pago, $metodo);
            $normalizado += ['idtipopago' => (int) $pago['idtipopago'], 'idcuenta' => null, 'archivo' => null];
            if (!CobroVenta::esEfectivo($metodo)) {
                if (!is_scalar($pago['idcuenta'] ?? null) || !ctype_digit((string) $pago['idcuenta'])) {
                    throw new InvalidArgumentException('Selecciona la cuenta destino de cada pago electrónico.');
                }
                $cuenta = $cuentas[(int) $pago['idcuenta']] ?? null;
                if (!$cuenta || (int) $cuenta['estado'] !== 1 || $cuenta['moneda'] !== 'PEN') {
                    throw new InvalidArgumentException('La cuenta destino debe estar activa y en soles (PEN).');
                }
                $archivo = $archivos[$indice] ?? null;
                if (!$archivo instanceof UploadedFile) throw new InvalidArgumentException('Adjunta el voucher de cada pago electrónico.');
                $this->vouchers->validar($archivo);
                $normalizado['idcuenta'] = (int) $cuenta['idcuenta'];
                $normalizado['archivo'] = $archivo;
            }
            $suma += CobroVenta::centimos($normalizado['monto']);
            $preparados[$indice] = $normalizado;
        }
        $totalCentimos = CobroVenta::centimos($total);
        if (($exigirTotal && $suma !== $totalCentimos) || (!$exigirTotal && $suma > $totalCentimos)) {
            throw new InvalidArgumentException('En una venta de contado, los pagos deben coincidir con el total de la venta.');
        }
        return $preparados;
    }
}
