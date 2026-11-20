<?php

use App\Services\ServicioConsultaService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class ServicioConsultaServiceTest extends CIUnitTestCase
{
    private ServicioConsultaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect([
            'database' => ':memory:',
            'DBDriver' => 'SQLite3',
            'DBPrefix' => '',
            'foreignKeys' => true,
        ], false);
        $this->db->query('CREATE TABLE personas (idpersona INTEGER PRIMARY KEY, nombres TEXT, apellidos TEXT, razonsocial TEXT, numerodoc TEXT)');
        $this->db->query('CREATE TABLE tecnicos (idtecnico INTEGER PRIMARY KEY, nombres TEXT, apellidos TEXT)');
        $this->db->query('CREATE TABLE unidadesmedida (idunidad INTEGER PRIMARY KEY, abreviatura TEXT)');
        $this->db->query('CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, nombre TEXT, idunidad INTEGER, tipo TEXT)');
        $this->db->query('CREATE TABLE ventas (idventa INTEGER PRIMARY KEY, idcliente INTEGER, serie TEXT, numero TEXT, fecha TEXT, fechaanulacion TEXT)');
        $this->db->query('CREATE TABLE detventa (iddetventa INTEGER PRIMARY KEY, idventa INTEGER, idproducto INTEGER, cantidad NUMERIC)');
        $this->db->query('CREATE TABLE venta_combo_componentes (idcomponenteventa INTEGER PRIMARY KEY, iddetventa INTEGER, idproducto INTEGER, cantidad NUMERIC)');
        $this->db->query('CREATE TABLE ordenes_servicio (idorden INTEGER PRIMARY KEY, idcliente INTEGER, idventa INTEGER, idservicio INTEGER, idtecnico INTEGER, titulo TEXT, direccion TEXT, fecha_inicio TEXT, fecha_fin TEXT, estado TEXT, cantidad_servicio NUMERIC)');

        $this->db->table('personas')->insert(['idpersona' => 1, 'nombres' => 'Ana', 'apellidos' => 'Cliente', 'razonsocial' => '', 'numerodoc' => '12345678']);
        $this->db->table('tecnicos')->insertBatch([
            ['idtecnico' => 1, 'nombres' => 'Técnico', 'apellidos' => 'Uno'],
            ['idtecnico' => 2, 'nombres' => 'Técnico', 'apellidos' => 'Dos'],
        ]);
        $this->db->table('unidadesmedida')->insert(['idunidad' => 1, 'abreviatura' => 'UND']);
        $this->db->table('productos')->insertBatch([
            ['idproducto' => 10, 'nombre' => 'Instalación', 'idunidad' => 1, 'tipo' => 'SERVICIO'],
            ['idproducto' => 11, 'nombre' => 'Combo instalación', 'idunidad' => 1, 'tipo' => 'COMBO'],
        ]);
        $this->db->table('ventas')->insertBatch([
            ['idventa' => 20, 'idcliente' => 1, 'serie' => 'F001', 'numero' => '42', 'fecha' => '2026-11-10 10:00:00', 'fechaanulacion' => null],
            ['idventa' => 21, 'idcliente' => 1, 'serie' => 'F001', 'numero' => '43', 'fecha' => '2026-11-11 10:00:00', 'fechaanulacion' => null],
        ]);
        $this->db->table('detventa')->insertBatch([
            ['iddetventa' => 1, 'idventa' => 20, 'idproducto' => 10, 'cantidad' => '3.00'],
            ['iddetventa' => 2, 'idventa' => 21, 'idproducto' => 11, 'cantidad' => '2.00'],
        ]);
        $this->db->table('venta_combo_componentes')->insert(['idcomponenteventa' => 1, 'iddetventa' => 2, 'idproducto' => 10, 'cantidad' => '2.00']);
        $this->db->table('ordenes_servicio')->insertBatch([
            ['idorden' => 1, 'idcliente' => 1, 'idventa' => 20, 'idservicio' => 10, 'idtecnico' => 1, 'titulo' => 'Instalación oficina', 'direccion' => 'Centro', 'fecha_inicio' => '2026-11-16 09:00:00', 'fecha_fin' => '2026-11-16 10:00:00', 'estado' => 'PROGRAMADO', 'cantidad_servicio' => '1.00'],
            ['idorden' => 2, 'idcliente' => 1, 'idventa' => null, 'idservicio' => 10, 'idtecnico' => 2, 'titulo' => 'Diagnóstico', 'direccion' => 'Norte', 'fecha_inicio' => '2026-11-17 09:00:00', 'fecha_fin' => '2026-11-17 10:00:00', 'estado' => 'EN_PROCESO', 'cantidad_servicio' => '1.00'],
            ['idorden' => 3, 'idcliente' => 1, 'idventa' => null, 'idservicio' => 10, 'idtecnico' => 1, 'titulo' => 'Trabajo antiguo', 'direccion' => 'Sur', 'fecha_inicio' => '2025-01-01 09:00:00', 'fecha_fin' => '2025-01-01 10:00:00', 'estado' => 'EN_PROCESO', 'cantidad_servicio' => '1.00'],
            ['idorden' => 4, 'idcliente' => 1, 'idventa' => null, 'idservicio' => 10, 'idtecnico' => 1, 'titulo' => 'Finalizado', 'direccion' => 'Este', 'fecha_inicio' => '2026-11-18 09:00:00', 'fecha_fin' => '2026-11-18 10:00:00', 'estado' => 'FINALIZADO', 'cantidad_servicio' => '1.00'],
        ]);
        $this->service = new ServicioConsultaService($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testAgendaAplicaRangoEstadoTecnicoYLimite(): void
    {
        $resultado = $this->service->agenda(['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'estado' => 'PROGRAMADO', 'tecnico' => 1]);
        $this->assertSame([1], array_column($resultado['items'], 'idorden'));
        $this->assertFalse($resultado['truncada']);

        $limitado = $this->service->agenda(['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'estado' => '', 'tecnico' => 0], 1);
        $this->assertCount(1, $limitado['items']);
        $this->assertTrue($limitado['truncada']);
    }

    public function testListadoBuscaCodigoYResumeEstados(): void
    {
        $resultado = $this->service->listar(['buscar' => 'OS-00002', 'estado' => ''], 1, 25);
        $this->assertSame([2], array_column($resultado['items'], 'idorden'));
        $this->assertSame(1, $resultado['total']);

        $resumen = $this->service->resumenPorEstado();
        $this->assertSame(1, $resumen['PROGRAMADO']);
        $this->assertSame(2, $resumen['EN_PROCESO']);
        $this->assertSame(1, $resumen['FINALIZADO']);
    }

    public function testBuscaVentasConSaldoPendiente(): void
    {
        $porCliente = $this->service->buscarVentas('Ana');
        $this->assertContains(20, array_column($porCliente, 'id'));
        $ventaDirecta = $porCliente[array_search(20, array_column($porCliente, 'id'), true)];
        $this->assertStringContainsString('F001-42', $ventaDirecta['texto']);

        $servicios = $this->service->serviciosDeVenta(20);
        $this->assertSame('2.00', $servicios[0]['pendiente']);
    }

    public function testDetectaServicioIncluidoEnComboYLoMuestraEntreVentasRecientes(): void
    {
        $resultados = $this->service->buscarVentas('');
        $this->assertContains(21, array_column($resultados, 'id'));

        $servicios = $this->service->serviciosDeVenta(21);
        $this->assertSame(10, (int) $servicios[0]['idproducto']);
        $this->assertSame('2.00', $servicios[0]['pendiente']);
    }
}
