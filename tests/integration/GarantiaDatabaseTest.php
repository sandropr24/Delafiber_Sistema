<?php

use App\Services\GarantiaConsultaService;
use App\Services\GarantiaService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class GarantiaDatabaseTest extends CIUnitTestCase
{
    protected $db = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sqlite3')) $this->markTestSkipped('Se requiere SQLite3 para las garantías en SQL aislado.');
        $this->db = Database::connect(['database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '', 'foreignKeys' => true], false);
        foreach ([
            'CREATE TABLE roles (idrol INTEGER PRIMARY KEY, rol TEXT)',
            'CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY, idrol INTEGER REFERENCES roles(idrol), estado INTEGER, nombreusuario TEXT)',
            'CREATE TABLE personas (idpersona INTEGER PRIMARY KEY, numerodoc TEXT, razonsocial TEXT, nombres TEXT, apellidos TEXT)',
            'CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, nombre TEXT, tipo TEXT, controlaserie INTEGER, garantiameses INTEGER)',
            'CREATE TABLE ventas (idventa INTEGER PRIMARY KEY, idcliente INTEGER REFERENCES personas(idpersona), fecha TEXT, fechaanulacion TEXT, serie TEXT, numero TEXT)',
            'CREATE TABLE detventa (iddetventa INTEGER PRIMARY KEY, idventa INTEGER REFERENCES ventas(idventa), idproducto INTEGER REFERENCES productos(idproducto), cantidad DECIMAL, garantiameses INTEGER, nombre_snapshot TEXT, tipo_snapshot TEXT, controlaserie_snapshot INTEGER)',
            'CREATE TABLE venta_combo_componentes (idcomponenteventa INTEGER PRIMARY KEY, iddetventa INTEGER REFERENCES detventa(iddetventa), idproducto INTEGER REFERENCES productos(idproducto), cantidad DECIMAL, garantiameses INTEGER, nombre_snapshot TEXT, tipo_snapshot TEXT, controlaserie_snapshot INTEGER)',
            'CREATE TABLE series (idserie INTEGER PRIMARY KEY, iddetventa INTEGER REFERENCES detventa(iddetventa), idproducto INTEGER REFERENCES productos(idproducto), numeroserie TEXT, estado TEXT)',
            'CREATE TABLE entregas (identrega INTEGER PRIMARY KEY, idventa INTEGER REFERENCES ventas(idventa), estado TEXT)',
            'CREATE TABLE proveedores (idproveedor INTEGER PRIMARY KEY, razonsocial TEXT)',
            "CREATE TABLE garantias (idgarantia INTEGER PRIMARY KEY AUTOINCREMENT, iddetventa INTEGER REFERENCES detventa(iddetventa), idproducto INTEGER REFERENCES productos(idproducto), idcomponenteventa INTEGER REFERENCES venta_combo_componentes(idcomponenteventa), idserie INTEGER REFERENCES series(idserie), producto_snapshot TEXT, serie_snapshot TEXT, fechaingreso TEXT NOT NULL, falla TEXT NOT NULL, estado TEXT, idproveedor INTEGER REFERENCES proveedores(idproveedor), idusuario INTEGER REFERENCES usuarios(idusuario), observacion TEXT, garantiameses INTEGER, fechavencimiento TEXT, plazo_verificado INTEGER, request_token TEXT UNIQUE)",
            'CREATE TABLE garantia_historial (idhistorial INTEGER PRIMARY KEY AUTOINCREMENT, idgarantia INTEGER REFERENCES garantias(idgarantia), estado TEXT, fecha TEXT NOT NULL, observacion TEXT, idusuario INTEGER REFERENCES usuarios(idusuario), idproveedor INTEGER REFERENCES proveedores(idproveedor))',
        ] as $sql) $this->db->query($sql);
        $this->db->table('roles')->insertBatch([['idrol' => 1, 'rol' => 'Administrador'], ['idrol' => 2, 'rol' => 'Vendedor']]);
        $this->db->table('usuarios')->insertBatch([['idusuario' => 7, 'idrol' => 1, 'estado' => 1, 'nombreusuario' => 'Admin'], ['idusuario' => 8, 'idrol' => 2, 'estado' => 1, 'nombreusuario' => 'Vendedor']]);
        $this->db->table('personas')->insert(['idpersona' => 1, 'numerodoc' => '12345678', 'nombres' => 'Cliente', 'apellidos' => 'Prueba']);
        $this->db->table('productos')->insertBatch([
            ['idproducto' => 30, 'nombre' => 'Cámara catálogo nuevo', 'tipo' => 'PRODUCTO', 'controlaserie' => 1, 'garantiameses' => 99],
            ['idproducto' => 31, 'nombre' => 'Combo', 'tipo' => 'COMBO', 'controlaserie' => 0, 'garantiameses' => 1],
            ['idproducto' => 32, 'nombre' => 'Instalación', 'tipo' => 'SERVICIO', 'controlaserie' => 0, 'garantiameses' => 0],
        ]);
        $this->db->table('ventas')->insert(['idventa' => 10, 'idcliente' => 1, 'fecha' => date('Y-m-d') . ' 10:00:00', 'serie' => 'NV01', 'numero' => '00000001']);
        $this->db->table('detventa')->insertBatch([
            ['iddetventa' => 20, 'idventa' => 10, 'idproducto' => 30, 'cantidad' => 1, 'garantiameses' => 12, 'nombre_snapshot' => 'Cámara original', 'tipo_snapshot' => 'PRODUCTO', 'controlaserie_snapshot' => 1],
            ['iddetventa' => 21, 'idventa' => 10, 'idproducto' => 31, 'cantidad' => 1, 'garantiameses' => 1, 'nombre_snapshot' => 'Kit original', 'tipo_snapshot' => 'COMBO', 'controlaserie_snapshot' => 0],
        ]);
        $this->db->table('venta_combo_componentes')->insertBatch([
            ['idcomponenteventa' => 55, 'iddetventa' => 21, 'idproducto' => 30, 'cantidad' => 1, 'garantiameses' => 6, 'nombre_snapshot' => 'Cámara del kit', 'tipo_snapshot' => 'PRODUCTO', 'controlaserie_snapshot' => 1],
            ['idcomponenteventa' => 56, 'iddetventa' => 21, 'idproducto' => 32, 'cantidad' => 1, 'garantiameses' => 0, 'nombre_snapshot' => 'Instalación', 'tipo_snapshot' => 'SERVICIO', 'controlaserie_snapshot' => 0],
        ]);
        $this->db->table('series')->insertBatch([
            ['idserie' => 40, 'iddetventa' => 20, 'idproducto' => 30, 'numeroserie' => 'DIRECTA-001', 'estado' => 'VENDIDO'],
            ['idserie' => 41, 'iddetventa' => 21, 'idproducto' => 30, 'numeroserie' => 'COMBO-001', 'estado' => 'VENDIDO'],
        ]);
        $this->db->table('entregas')->insert(['identrega' => 1, 'idventa' => 10, 'estado' => 'ENTREGADO']);
        $this->db->table('proveedores')->insert(['idproveedor' => 11, 'razonsocial' => 'Proveedor original']);
    }

    protected function tearDown(): void
    {
        $this->db?->close();
        parent::tearDown();
    }

    private function datos(): array
    {
        return ['iddetventa' => 21, 'idcomponenteventa' => 55, 'idserie' => 41, 'request_token' => str_repeat('a', 64), 'falla' => 'No enciende el equipo.'];
    }

    public function testBusquedaPorDniSerieYComprobanteNoMezclaSeriesNiServicios(): void
    {
        $c = new GarantiaConsultaService($this->db);
        $this->assertCount(2, $c->buscarVentas('12345678'));
        $this->assertCount(2, $c->buscarVentas('NV01-00000001'));
        $filas = $c->buscarVentas('COMBO-001');
        $this->assertCount(1, $filas);
        $this->assertSame('Cámara del kit', $filas[0]['producto']);
        $this->assertEquals(6, $filas[0]['garantiameses']);
        $this->assertEquals(55, $filas[0]['idcomponenteventa']);
        $this->assertCount(0, $c->buscarVentas("' OR 1=1 --"));
        $this->assertNull($c->articulo(20, 55));
    }

    public function testHistoricosDesconocidosNoUsanGarantiaDelCatalogo(): void
    {
        $this->db->table('venta_combo_componentes')->where('idcomponenteventa', 55)->update(['garantiameses' => null]);
        $fila = (new GarantiaConsultaService($this->db))->buscarVentas('COMBO-001')[0];
        $this->assertNull($fila['garantiameses']);
        $this->assertStringContainsString('verificación', $fila['aviso']);
    }

    public function testSeguimientoRealPreservaSnapshotsYPermiteConsultarElHistorial(): void
    {
        $s = new GarantiaService($this->db);
        $id = $s->registrar($this->datos(), 7);
        $this->assertSame($id, $s->registrar($this->datos(), 7));
        $s->cambiarEstado($id, 'RECIBIDO', 'ENVIADO_PROVEEDOR', 'Se envía al proveedor original.', 11, 7);
        $s->cambiarEstado($id, 'ENVIADO_PROVEEDOR', 'REEMPLAZADO', 'Reemplazo documentado serie NUEVA-01.', null, 7);
        $s->cambiarEstado($id, 'REEMPLAZADO', 'ENTREGADO_CLIENTE', 'Cliente recibe conforme.', null, 7);
        $c = new GarantiaConsultaService($this->db);
        $caso = $c->obtener($id);
        $this->assertSame('Cámara del kit', $caso['producto']);
        $this->assertEquals(6, $caso['garantiameses']);
        $this->assertSame('COMBO-001', $caso['numeroserie']);
        $this->assertSame('ENTREGADO_CLIENTE', $caso['estado']);
        $this->assertCount(4, $c->historial($id));
        $this->assertSame(1, $c->listar('COMBO-001', 'ENTREGADO_CLIENTE', 1)['total']);
        $this->assertSame('VENDIDO', $this->db->table('series')->where('idserie', 41)->get()->getRow('estado'));
    }

    public function testDosFormulariosDistintosNoAbrenDosCasosParaLaMismaSerie(): void
    {
        $s = new GarantiaService($this->db);
        $s->registrar($this->datos(), 7);
        $datos = $this->datos();
        $datos['request_token'] = str_repeat('b', 64);
        try { $s->registrar($datos, 7); $this->fail('Debía rechazarse.'); }
        catch (InvalidArgumentException) {
            $this->assertSame(1, $this->db->table('garantias')->countAllResults());
            $this->assertSame(1, $this->db->table('garantia_historial')->countAllResults());
        }
    }

    public function testSqlFallidoRevierteCasoYEvento(): void
    {
        $this->db->query("CREATE TRIGGER fallar_evento BEFORE INSERT ON garantia_historial BEGIN SELECT RAISE(ABORT, 'Fallo de prueba'); END");
        try { (new GarantiaService($this->db))->registrar($this->datos(), 7); $this->fail('Debía fallar.'); }
        catch (Throwable $e) {
            $this->assertNotInstanceOf(PHPUnit\Framework\AssertionFailedError::class, $e);
            $this->assertSame(0, $this->db->table('garantias')->countAllResults());
            $this->assertSame(0, $this->db->table('garantia_historial')->countAllResults());
        }
    }
}
