<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class InventoryMovementService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function consume(int $idKardex, string $cantidad, int $idUsuario, string $descripcion, string $motivo): array
    {
        $unidades = $this->toHundredths($cantidad);
        $kardex = $this->db->query($this->lock("SELECT k.idkardex, k.idproducto, k.idlocal, k.stockactual, p.nombre,
            p.tipo, p.controlastock, p.controlaserie, p.estado
            FROM kardex k JOIN productos p ON p.idproducto = k.idproducto WHERE k.idkardex = ?"), [$idKardex])->getRowArray();
        if (!$kardex || $kardex['tipo'] !== 'PRODUCTO' || (int) $kardex['controlastock'] !== 1 || (int) $kardex['controlaserie'] !== 0 || (int) $kardex['estado'] !== 1) {
            throw new RuntimeException('El material no está habilitado para consumo directo (los artículos con serie requieren otro procedimiento).');
        }

        $reservado = (string) ($this->db->table('reservas_stock')->selectSum('cantidad')->where('idkardex', $idKardex)
            ->where('estado', 'RESERVADO')->get()->getRow('cantidad') ?? '0');
        $stock = $this->toHundredths((string) $kardex['stockactual'], false);
        $disponible = $stock - $this->toHundredths($reservado, false);
        if ($unidades > $disponible) {
            throw new RuntimeException('Stock insuficiente. Disponible sin reservas: ' . number_format(max(0, $disponible) / 100, 2) . '.');
        }

        $nuevoStock = $this->fromHundredths($stock - $unidades);
        if (!$this->db->table('kardex')->where('idkardex', $idKardex)->update(['stockactual' => $nuevoStock])) {
            throw new RuntimeException('No se pudo actualizar el stock físico.');
        }
        if (!$this->db->table('movimientos')->insert([
            'idkardex' => $idKardex,
            'idusuario' => $idUsuario,
            'tipo' => 'SALIDA',
            'fecha' => date('Y-m-d H:i:s'),
            'cantidad' => $this->fromHundredths($unidades),
            'saldo' => $nuevoStock,
            'descripcion' => $descripcion,
            'motivo' => $motivo,
        ])) {
            throw new RuntimeException('No se pudo crear el movimiento de Kardex.');
        }

        return ['idmovimiento' => (int) $this->db->insertID(), 'kardex' => $kardex, 'cantidad' => $this->fromHundredths($unidades), 'saldo' => $nuevoStock];
    }

    public function reverse(int $idMovimiento, int $idUsuario, string $motivo): array
    {
        $original = $this->db->query($this->lock('SELECT * FROM movimientos WHERE idmovimiento = ?'), [$idMovimiento])->getRowArray();
        if (!$original || $original['tipo'] !== 'SALIDA') {
            throw new RuntimeException('El movimiento original no es una salida válida.');
        }
        if ($this->db->table('movimientos')->where('idmovimiento_origen', $idMovimiento)->countAllResults()) {
            throw new RuntimeException('Este movimiento ya fue anulado.');
        }
        $kardex = $this->db->query($this->lock('SELECT idkardex, stockactual FROM kardex WHERE idkardex = ?'), [$original['idkardex']])->getRowArray();
        if (!$kardex) {
            throw new RuntimeException('No existe el Kardex asociado al consumo.');
        }
        $cantidad = $this->toHundredths((string) $original['cantidad'], false);
        $nuevoStock = $this->fromHundredths($this->toHundredths((string) $kardex['stockactual'], false) + $cantidad);
        if (!$this->db->table('kardex')->where('idkardex', $kardex['idkardex'])->update(['stockactual' => $nuevoStock])) {
            throw new RuntimeException('No se pudo devolver el material al stock.');
        }
        if (!$this->db->table('movimientos')->insert([
            'idmovimiento_origen' => $idMovimiento,
            'idkardex' => $original['idkardex'],
            'idusuario' => $idUsuario,
            'tipo' => 'ANULACION',
            'fecha' => date('Y-m-d H:i:s'),
            'cantidad' => $this->fromHundredths($cantidad),
            'saldo' => $nuevoStock,
            'descripcion' => 'Anulación: ' . mb_substr((string) $original['descripcion'], 0, 138),
            'motivo' => mb_substr($motivo, 0, 100),
        ])) {
            throw new RuntimeException('No se pudo crear el movimiento compensatorio.');
        }
        return ['idmovimiento' => (int) $this->db->insertID(), 'cantidad' => $this->fromHundredths($cantidad), 'saldo' => $nuevoStock];
    }

    private function toHundredths(string $value, bool $positive = true): int
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw new RuntimeException('La cantidad debe tener hasta dos decimales.');
        }
        $result = ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
        if ($positive && $result < 1) {
            throw new RuntimeException('La cantidad debe ser mayor que cero.');
        }
        return $result;
    }

    private function fromHundredths(int $value): string
    {
        return intdiv($value, 100) . '.' . str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }

    private function lock(string $sql): string
    {
        return $this->db->DBDriver === 'MySQLi' ? $sql . ' FOR UPDATE' : $sql;
    }
}
