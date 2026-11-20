<?php

namespace App\Services;

use App\Libraries\CobroVenta;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CobroPosteriorService
{
    public function __construct(private ?BaseConnection $db = null, private ?VoucherStorage $vouchers = null)
    {
        $this->db ??= db_connect();
        $this->vouchers ??= new VoucherStorage();
    }

    private function lock(string $sql): string
    {
        return $this->db->DBDriver === 'MySQLi' ? $sql . ' FOR UPDATE' : $sql;
    }

    public function saldo(int $venta): array
    {
        $fila = $this->db->table('ventas v')->select('v.total, v.fechaanulacion, COALESCE(SUM(p.monto), 0) AS pagado', false)
            ->join('pagoventa p', 'p.idventa = v.idventa', 'left')->where('v.idventa', $venta)
            ->groupBy('v.idventa, v.total, v.fechaanulacion')->get()->getRowArray();
        if (!$fila) throw new InvalidArgumentException('La venta no existe.');
        $total = CobroVenta::centimos($fila['total']);
        $pagado = CobroVenta::centimos((string) $fila['pagado']);
        return $fila + ['saldo' => number_format(max(0, $total - $pagado) / 100, 2, '.', '')];
    }

    public function registrar(int $venta, int $apertura, array $pago, ?UploadedFile $archivo, ?int $tecnico, ?int $cuota, int $usuario, string $token): int
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new InvalidArgumentException('Actualiza la página e intenta nuevamente.');
        $guardados = [];
        if (!$this->db->transBegin()) throw new RuntimeException('No se pudo iniciar el cobro.');
        try {
            $existente = $this->db->query($this->lock('SELECT idpagoventa, idventa, idusuariocobro FROM pagoventa WHERE token_operacion = ?'), [$token])->getRowArray();
            if ($existente) {
                if ((int) $existente['idventa'] !== $venta || (int) $existente['idusuariocobro'] !== $usuario) {
                    throw new InvalidArgumentException('El formulario ya fue utilizado para otro cobro.');
                }
                $this->db->transCommit();
                return (int) $existente['idpagoventa'];
            }
            $fila = $this->db->query($this->lock('SELECT * FROM ventas WHERE idventa = ?'), [$venta])->getRowArray();
            if (!$fila || $fila['fechaanulacion'] !== null) throw new InvalidArgumentException('La venta no existe o fue anulada.');
            $caja = new CajaService($this->db);
            $caja->bloquearCobro($apertura, (int) $fila['idlocal'], $usuario);
            $pagos = $this->db->query($this->lock('SELECT monto FROM pagoventa WHERE idventa = ? ORDER BY idpagoventa'), [$venta])->getResultArray();
            $pagado = 0;
            foreach ($pagos as $anterior) $pagado += CobroVenta::centimos($anterior['monto']);
            $saldo = CobroVenta::centimos($fila['total']) - $pagado;
            if ($saldo <= 0) throw new InvalidArgumentException('La venta ya está pagada completamente.');
            $preparado = (new SoporteCobroService($this->db, $this->vouchers))->preparar([0 => $pago], [0 => $archivo], $pago['monto'] ?? '');
            $actual = $preparado[0];
            if (CobroVenta::centimos($actual['monto']) > $saldo) throw new InvalidArgumentException('El cobro supera el saldo pendiente de la venta.');

            $entrega = $this->db->query($this->lock('SELECT * FROM entregas WHERE idventa = ?'), [$venta])->getRowArray();
            if ($tecnico) {
                $tecnicoFila = $this->db->query($this->lock('SELECT idtecnico FROM tecnicos WHERE idtecnico = ? AND estado = ?'), [$tecnico, 'ACTIVO'])->getRowArray();
                if (!$tecnicoFila) throw new InvalidArgumentException('Selecciona un técnico activo.');
            }
            $cuotaFila = null;
            if ($fila['formapago'] === 'CUOTAS' && !$cuota) throw new InvalidArgumentException('Selecciona la cuota a la que corresponde el cobro.');
            if ($cuota) {
                $cuotaFila = $this->db->query($this->lock('SELECT * FROM cuotas WHERE idcuota = ?'), [$cuota])->getRowArray();
                if (!$cuotaFila || (int) $cuotaFila['idventa'] !== $venta || $cuotaFila['estado'] === 'PAGADA') throw new InvalidArgumentException('Selecciona una cuota pendiente de esta venta.');
                $pendienteCuota = CobroVenta::centimos($cuotaFila['monto']) - CobroVenta::centimos($cuotaFila['montopagado']);
                if (CobroVenta::centimos($actual['monto']) > $pendienteCuota) throw new InvalidArgumentException('El cobro supera el saldo de la cuota.');
            }

            $voucher = null;
            if ($actual['archivo']) $guardados[] = $voucher = $this->vouchers->guardar($actual['archivo']);
            if (!$this->db->table('pagoventa')->insert([
                'idventa' => $venta, 'idcuota' => $cuota ?: null, 'idtipopago' => $actual['idtipopago'],
                'idapertura' => $apertura, 'idusuariocobro' => $usuario, 'idtecnicocobro' => $tecnico ?: null,
                'identrega' => $entrega['identrega'] ?? null, 'monto' => $actual['monto'], 'idcuenta' => $actual['idcuenta'],
                'referencia' => $actual['referencia'], 'voucher' => $voucher, 'montorecibido' => $actual['montorecibido'],
                'vuelto' => $actual['vuelto'], 'fecha' => date('Y-m-d H:i:s'), 'token_operacion' => $token,
            ])) throw new RuntimeException('No se pudo guardar el cobro.');
            $idPago = (int) $this->db->insertID();
            $saldoPosterior = $saldo - CobroVenta::centimos($actual['monto']);
            if ($entrega && (int) ($entrega['cobrocontraentrega'] ?? 0) === 1 && $saldoPosterior === 0) {
                if (!$this->db->table('entregas')->where('identrega', (int) $entrega['identrega'])->update([
                    'cobrocontraentrega' => 0, 'idpagoventa' => $idPago,
                ])) throw new RuntimeException('No se pudo conciliar el cobro de la entrega.');
            }
            if ($cuotaFila) {
                $nuevo = CobroVenta::centimos($cuotaFila['montopagado']) + CobroVenta::centimos($actual['monto']);
                $totalCuota = CobroVenta::centimos($cuotaFila['monto']);
                if (!$this->db->table('cuotas')->where('idcuota', $cuota)->update([
                    'montopagado' => number_format($nuevo / 100, 2, '.', ''),
                    'estado' => $nuevo === $totalCuota ? 'PAGADA' : 'PARCIAL',
                ])) throw new RuntimeException('No se pudo actualizar la cuota.');
            }
            if ($entrega && $tecnico && (int) ($entrega['idtecnico'] ?? 0) !== $tecnico) {
                throw new InvalidArgumentException('El técnico cobrador debe coincidir con el técnico asignado a la entrega.');
            }
            if (!$this->db->transStatus() || !$this->db->transCommit()) throw new RuntimeException('No se pudo confirmar el cobro.');
            return $idPago;
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->vouchers->descartar($guardados);
            throw $e;
        }
    }
}
