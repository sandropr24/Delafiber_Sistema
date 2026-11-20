<?php

use App\Services\InventoryMovementService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class InventoryMovementServiceTest extends CIUnitTestCase
{
    private InventoryMovementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect([
            'database' => ':memory:',
            'DBDriver' => 'SQLite3',
            'DBPrefix' => '',
            'foreignKeys' => true,
        ], false);
        $this->db->query('CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, nombre TEXT, tipo TEXT, controlastock INTEGER, controlaserie INTEGER, estado INTEGER)');
        $this->db->query('CREATE TABLE kardex (idkardex INTEGER PRIMARY KEY, idproducto INTEGER, idlocal INTEGER, stockactual NUMERIC)');
        $this->db->query('CREATE TABLE reservas_stock (idkardex INTEGER, cantidad NUMERIC, estado TEXT)');
        $this->db->query('CREATE TABLE movimientos (idmovimiento INTEGER PRIMARY KEY AUTOINCREMENT, idmovimiento_origen INTEGER UNIQUE, idkardex INTEGER, tipo TEXT, fecha TEXT, cantidad NUMERIC, saldo NUMERIC, descripcion TEXT, motivo TEXT, idusuario INTEGER, idcompra INTEGER, idventa INTEGER)');
        $this->db->table('productos')->insert(['idproducto' => 1, 'nombre' => 'Cable', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'controlaserie' => 0, 'estado' => 1]);
        $this->db->table('kardex')->insert(['idkardex' => 1, 'idproducto' => 1, 'idlocal' => 1, 'stockactual' => '10.00']);
        $this->db->table('reservas_stock')->insert(['idkardex' => 1, 'cantidad' => '2.00', 'estado' => 'RESERVADO']);
        $this->service = new InventoryMovementService($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testConsumoYAnulacionRestauranElStockConTrazabilidad(): void
    {
        $this->db->transBegin();
        $salida = $this->service->consume(1, '3.25', 1, 'Prueba de consumo', 'Servicio técnico');
        $this->assertSame('6.75', $salida['saldo']);
        $this->assertSame('6.75', number_format((float) $this->db->table('kardex')->get()->getRow('stockactual'), 2, '.', ''));

        $anulacion = $this->service->reverse($salida['idmovimiento'], 1, 'Error de digitación');
        $this->assertSame('10.00', $anulacion['saldo']);
        $this->assertSame('10.00', number_format((float) $this->db->table('kardex')->get()->getRow('stockactual'), 2, '.', ''));
        $this->assertSame(2, $this->db->table('movimientos')->countAllResults());
        $this->assertSame('ANULACION', $this->db->table('movimientos')->where('idmovimiento', $anulacion['idmovimiento'])->get()->getRow('tipo'));
        $this->db->transRollback();
    }

    public function testConsumoRespetaStockReservado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->consume(1, '8.01', 1, 'Prueba', 'Servicio técnico');
    }

    public function testNoPermiteAnularDosVecesLaMismaSalida(): void
    {
        $salida = $this->service->consume(1, '1.00', 1, 'Prueba', 'Servicio técnico');
        $this->service->reverse($salida['idmovimiento'], 1, 'Primera anulación');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ya fue anulado');
        $this->service->reverse($salida['idmovimiento'], 1, 'Segunda anulación');
    }
}
