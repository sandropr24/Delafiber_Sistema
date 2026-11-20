<?php

use App\Services\ProductoConfiguracionService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class ProductoConfiguracionDatabaseTest extends CIUnitTestCase
{
    private ?BaseConnection $conexion = null;
    private ProductoConfiguracionService $configuracion;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('Se requiere SQLite3 para verificar las relaciones de productos y reservas.');
        }

        $this->conexion = Database::connect([
            'database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '',
        ], false);
        $consultas = [
            'CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, nombre TEXT, tipo TEXT, controlastock INTEGER, controlaserie INTEGER, idunidad INTEGER, estado INTEGER)',
            'CREATE TABLE unidadesmedida (idunidad INTEGER PRIMARY KEY, abreviatura TEXT)',
            'CREATE TABLE kardex (idkardex INTEGER PRIMARY KEY, idproducto INTEGER, idlocal INTEGER)',
            'CREATE TABLE reservas_stock (idreserva INTEGER PRIMARY KEY, idventa INTEGER, idkardex INTEGER, cantidad NUMERIC, estado TEXT)',
            'CREATE TABLE series (idserie INTEGER PRIMARY KEY, idproducto INTEGER, estado TEXT)',
            'CREATE TABLE ventas (idventa INTEGER PRIMARY KEY, fechaanulacion TEXT)',
            'CREATE TABLE detventa (iddetventa INTEGER PRIMARY KEY, idventa INTEGER, idproducto INTEGER)',
            'CREATE TABLE entregas (identrega INTEGER PRIMARY KEY, idventa INTEGER, estado TEXT)',
            'CREATE TABLE venta_combo_componentes (idcomponenteventa INTEGER PRIMARY KEY, iddetventa INTEGER, idproducto INTEGER)',
            'CREATE TABLE combo_detalle (idcombo INTEGER, idproducto INTEGER, cantidad NUMERIC)',
        ];
        foreach ($consultas as $sql) {
            $this->conexion->query($sql);
        }
        $this->conexion->table('productos')->insertBatch([
            ['idproducto' => 7, 'nombre' => 'Router', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'controlaserie' => 0, 'idunidad' => 1, 'estado' => 1],
            ['idproducto' => 8, 'nombre' => 'Artículo sin stock', 'tipo' => 'PRODUCTO', 'controlastock' => 0, 'controlaserie' => 0, 'idunidad' => 1, 'estado' => 1],
            ['idproducto' => 20, 'nombre' => 'Kit', 'tipo' => 'COMBO', 'controlastock' => 0, 'controlaserie' => 0, 'idunidad' => 1, 'estado' => 1],
        ]);
        $this->conexion->table('unidadesmedida')->insert(['idunidad' => 1, 'abreviatura' => 'UND']);
        $this->conexion->table('kardex')->insertBatch([
            ['idkardex' => 1, 'idproducto' => 7, 'idlocal' => 1],
            ['idkardex' => 2, 'idproducto' => 7, 'idlocal' => 2],
        ]);
        $this->configuracion = new ProductoConfiguracionService($this->conexion);
    }

    protected function tearDown(): void
    {
        $this->conexion?->close();
        parent::tearDown();
    }

    private function validar(int $id, array $datos): array
    {
        $this->conexion->transBegin();
        try {
            return $this->configuracion->validarCambio($id, $datos);
        } finally {
            $this->conexion->transRollback();
        }
    }

    private function venta(int $producto = 7, ?string $estadoEntrega = 'PENDIENTE', ?string $anulacion = null): void
    {
        $this->conexion->table('ventas')->insert(['idventa' => 10, 'fechaanulacion' => $anulacion]);
        $this->conexion->table('detventa')->insert(['iddetventa' => 100, 'idventa' => 10, 'idproducto' => $producto]);
        if ($estadoEntrega !== null) {
            $this->conexion->table('entregas')->insert(['identrega' => 1, 'idventa' => 10, 'estado' => $estadoEntrega]);
        }
    }

    public function testReservaEnOtroLocalTambienImpideCambiarConfiguracion(): void
    {
        $this->conexion->table('reservas_stock')->insert([
            'idreserva' => 1, 'idventa' => 10, 'idkardex' => 2, 'cantidad' => 1, 'estado' => 'RESERVADO',
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reservas de inventario pendientes');
        $this->validar(7, ['controlastock' => 0]);
    }

    public function testSerieReservadaSinReservaDeStockTambienProtegeElProducto(): void
    {
        $this->conexion->table('series')->insert(['idserie' => 1, 'idproducto' => 7, 'estado' => 'RESERVADO']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('números de serie reservados');
        $this->validar(7, ['controlaserie' => 1]);
    }

    public function testVentaSinStockPendienteImpideActivarStock(): void
    {
        $this->venta(8);
        $this->expectException(InvalidArgumentException::class);
        $this->validar(8, ['controlastock' => 1]);
    }

    public function testVentaSinRegistroDeEntregaSeConsideraPendiente(): void
    {
        $this->venta(7, null);
        $this->expectException(InvalidArgumentException::class);
        $this->validar(7, ['tipo' => 'SERVICIO']);
    }

    public function testEntregaCanceladaNoEquivaleAUnaAnulacionComercial(): void
    {
        $this->venta(7, 'CANCELADO');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('resolución comercial pendiente');
        $this->validar(7, ['controlaserie' => 1]);
    }

    public function testComponenteVendidoSeProtegeAunqueYaNoEsteEnElComboActual(): void
    {
        $this->venta(20);
        $this->conexion->table('venta_combo_componentes')->insert([
            'idcomponenteventa' => 1, 'iddetventa' => 100, 'idproducto' => 8,
        ]);
        // combo_detalle queda vacío: se debe usar la composición de la venta.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ventas de combos');
        $this->validar(8, ['controlastock' => 1]);
    }

    public function testPermiteEditarDescripcionEImagenConReservas(): void
    {
        $this->conexion->table('reservas_stock')->insert([
            'idreserva' => 1, 'idventa' => 10, 'idkardex' => 1, 'cantidad' => 1, 'estado' => 'RESERVADO',
        ]);
        $actual = $this->validar(7, ['nombre' => 'Nuevo nombre', 'descripcion' => 'Descripción', 'imagen' => 'nueva.jpg']);
        $this->assertSame(7, (int) $actual['idproducto']);
        $this->assertSame('RESERVADO', $this->conexion->table('reservas_stock')->get()->getRow('estado'));
    }

    public function testPermiteCambiosDespuesDeEntregarYConsumirLasReservas(): void
    {
        $this->venta(7, 'ENTREGADO');
        $this->conexion->table('reservas_stock')->insert([
            'idreserva' => 1, 'idventa' => 10, 'idkardex' => 1, 'cantidad' => 1, 'estado' => 'CONSUMIDO',
        ]);
        $this->conexion->table('series')->insert(['idserie' => 1, 'idproducto' => 7, 'estado' => 'VENDIDO']);
        $this->assertSame(7, (int) $this->validar(7, ['controlastock' => 0])['idproducto']);
    }

    public function testPermiteCambiosDespuesDeAnularYDevolverLasReservas(): void
    {
        $this->venta(7, 'CANCELADO', '2026-11-16 12:00:00');
        $this->conexion->table('reservas_stock')->insert([
            'idreserva' => 1, 'idventa' => 10, 'idkardex' => 1, 'cantidad' => 1, 'estado' => 'LIBERADO',
        ]);
        $this->assertSame(7, (int) $this->validar(7, ['controlaserie' => 1])['idproducto']);
    }

    public function testUnaVentaDeOtroArticuloNoBloqueaElProducto(): void
    {
        $this->venta(8);
        $this->assertSame(7, (int) $this->validar(7, ['controlastock' => 0])['idproducto']);
    }

    public function testCambiarConfiguracionNoModificaReservasNiSeries(): void
    {
        $this->conexion->table('series')->insert(['idserie' => 1, 'idproducto' => 7, 'estado' => 'RESERVADO']);
        try {
            $this->validar(7, ['controlaserie' => 1]);
            $this->fail('Debió impedirse el cambio de configuración.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('series', $e->getMessage());
        }
        $this->assertSame('RESERVADO', $this->conexion->table('series')->get()->getRow('estado'));
        $this->assertSame(0, (int) $this->conexion->table('productos')->where('idproducto', 7)->get()->getRow('controlaserie'));
    }

    public function testPrepararVentaDevuelveComboYComponentesConSuUnidad(): void
    {
        $this->conexion->table('combo_detalle')->insert(['idcombo' => 20, 'idproducto' => 7, 'cantidad' => 2]);
        $this->conexion->transBegin();
        try {
            $datos = $this->configuracion->bloquearParaVenta([['idproducto' => 20]]);
            $this->assertSame([7, 20], array_keys($datos['productos']));
            $this->assertSame('UND', $datos['productos'][7]['unidad']);
            $this->assertSame(7, (int) $datos['componentes'][20][0]['idproducto']);
        } finally {
            $this->conexion->transRollback();
        }
    }
}
