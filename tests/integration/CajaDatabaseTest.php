<?php

use App\Services\CajaService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class CajaDatabaseTest extends CIUnitTestCase
{
    private ?BaseConnection $conexion = null;
    private CajaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sqlite3')) $this->markTestSkipped('Se requiere SQLite3 para probar caja con SQL en memoria.');
        $this->conexion = Database::connect([
            'database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '', 'foreignKeys' => true,
        ], false);
        foreach ([
            'CREATE TABLE roles (idrol INTEGER PRIMARY KEY, rol TEXT)',
            "CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY, idrol INTEGER REFERENCES roles(idrol), estado INTEGER, nombreusuario TEXT DEFAULT 'Prueba')",
            'CREATE TABLE locales (idlocal INTEGER PRIMARY KEY, nombrelocal TEXT)',
            'CREATE TABLE usuario_local (idusuario INTEGER, idlocal INTEGER, PRIMARY KEY (idusuario, idlocal))',
            'CREATE TABLE cajas (idcaja INTEGER PRIMARY KEY AUTOINCREMENT, idlocal INTEGER REFERENCES locales(idlocal), nombre TEXT, estado INTEGER DEFAULT 1, UNIQUE (idlocal, nombre))',
            "CREATE TABLE aperturacaja (idapertura INTEGER PRIMARY KEY AUTOINCREMENT, idcaja INTEGER REFERENCES cajas(idcaja), idusuario INTEGER REFERENCES usuarios(idusuario), fechaapertura TEXT, montoinicial DECIMAL(10,2), fechacierre TEXT, estado TEXT DEFAULT 'ABIERTA', observacion TEXT)",
            'CREATE TABLE tipospago (idtipopago INTEGER PRIMARY KEY, tipopago TEXT UNIQUE, estado INTEGER DEFAULT 1, requierecuenta INTEGER DEFAULT 0)',
            'CREATE TABLE pagoventa (idpagoventa INTEGER PRIMARY KEY, idapertura INTEGER REFERENCES aperturacaja(idapertura), idtipopago INTEGER REFERENCES tipospago(idtipopago), monto DECIMAL(10,2))',
            "CREATE TABLE movimientos_caja (idmovimientocaja INTEGER PRIMARY KEY AUTOINCREMENT, idapertura INTEGER REFERENCES aperturacaja(idapertura), idtipopago INTEGER REFERENCES tipospago(idtipopago), idusuario INTEGER REFERENCES usuarios(idusuario), tipo TEXT, concepto TEXT, monto DECIMAL(10,2), descripcion TEXT, fecha TEXT, token_operacion TEXT UNIQUE)",
            'CREATE TABLE cierres_caja_administrativos (idcierreadministrativo INTEGER PRIMARY KEY AUTOINCREMENT, idapertura INTEGER UNIQUE REFERENCES aperturacaja(idapertura), idadministrador INTEGER REFERENCES usuarios(idusuario), fecha TEXT, observacion TEXT)',
            'CREATE TABLE cierrecaja_detalle (idapertura INTEGER REFERENCES aperturacaja(idapertura), idtipopago INTEGER REFERENCES tipospago(idtipopago), montoesperado DECIMAL(10,2), montocontado DECIMAL(10,2), observacion TEXT, diferencia DECIMAL(10,2) GENERATED ALWAYS AS (montocontado - montoesperado) STORED, PRIMARY KEY (idapertura, idtipopago))',
        ] as $sql) $this->conexion->query($sql);
        $this->conexion->table('roles')->insert(['idrol' => 1, 'rol' => 'Administrador']);
        $this->conexion->table('usuarios')->insert(['idusuario' => 7, 'idrol' => 1, 'estado' => 1]);
        $this->conexion->table('locales')->insert(['idlocal' => 2, 'nombrelocal' => 'Tienda']);
        $this->conexion->table('usuario_local')->insert(['idusuario' => 7, 'idlocal' => 2]);
        $this->conexion->table('cajas')->insert(['idcaja' => 8, 'idlocal' => 2, 'nombre' => 'Principal']);
        $this->conexion->table('tipospago')->insertBatch([
            ['idtipopago' => 1, 'tipopago' => 'Efectivo'], ['idtipopago' => 2, 'tipopago' => 'Yape'],
        ]);
        $this->servicio = new CajaService($this->conexion);
    }

    protected function tearDown(): void
    {
        $this->conexion?->close();
        parent::tearDown();
    }

    public function testAperturaYSegundoIntentoNoDuplican(): void
    {
        $id = $this->servicio->abrir(8, '100.00', 7);
        $this->assertGreaterThan(0, $id);
        try { $this->servicio->abrir(8, '0.00', 7); $this->fail('No puede abrir dos veces.'); }
        catch (InvalidArgumentException) { $this->assertSame(1, $this->conexion->table('aperturacaja')->countAllResults()); }
    }

    private function aperturaConCobros(): int
    {
        $id = $this->servicio->abrir(8, '100.00', 7);
        $this->conexion->table('pagoventa')->insertBatch([
            ['idpagoventa' => 1, 'idapertura' => $id, 'idtipopago' => 1, 'monto' => '30.00'],
            ['idpagoventa' => 2, 'idapertura' => $id, 'idtipopago' => 2, 'monto' => '20.00'],
            ['idpagoventa' => 3, 'idapertura' => null, 'idtipopago' => 1, 'monto' => '999.00'],
        ]);
        return $id;
    }

    public function testCierreGuardaDiferenciasYSoloSumaCobrosDeLaApertura(): void
    {
        $id = $this->aperturaConCobros();
        $this->servicio->registrarMovimiento($id, 1, 'INGRESO', 'APORTE', '10.00', 'Aporte extraordinario', 7, str_repeat('a', 64));
        $this->servicio->registrarMovimiento($id, 1, 'EGRESO', 'GASTO', '5.00', 'Gasto documentado', 7, str_repeat('b', 64));
        $this->servicio->cerrar($id, [1 => '134.50', 2 => '20.00'], 'Faltante verificado', 7);
        $this->assertSame('CERRADA', $this->conexion->table('aperturacaja')->where('idapertura', $id)->get()->getRow('estado'));
        $efectivo = $this->conexion->table('cierrecaja_detalle')->where('idapertura', $id)->where('idtipopago', 1)->get()->getRowArray();
        $this->assertEquals(135, $efectivo['montoesperado']);
        $this->assertEquals(-0.5, $efectivo['diferencia']);
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->bloquearCobro($id, 2, 7);
    }

    public function testFalloSqlRevierteArqueoYCierre(): void
    {
        $id = $this->aperturaConCobros();
        $this->conexion->query("CREATE TRIGGER fallar_cierre BEFORE UPDATE ON aperturacaja BEGIN SELECT RAISE(ABORT, 'Fallo simulado'); END");
        try { $this->servicio->cerrar($id, [1 => '130.00', 2 => '20.00'], '', 7); $this->fail('Debe revertir el cierre.'); }
        catch (RuntimeException) {
            $this->assertSame(0, $this->conexion->table('cierrecaja_detalle')->countAllResults());
            $this->assertSame('ABIERTA', $this->conexion->table('aperturacaja')->where('idapertura', $id)->get()->getRow('estado'));
        }
    }

    public function testAdministradorPuedeCerrarUnaAperturaDeContingenciaConTrazabilidad(): void
    {
        $id = $this->servicio->abrir(8, '25.00', 7);
        $this->servicio->cerrarAdministrativamente($id, [1 => '25.00', 2 => '0.00'], 'Usuario ausente; arqueo verificado', 7);
        $this->assertSame('CERRADA', $this->conexion->table('aperturacaja')->where('idapertura', $id)->get()->getRow('estado'));
        $cierre = $this->conexion->table('cierres_caja_administrativos')->where('idapertura', $id)->get()->getRowArray();
        $this->assertSame(7, (int) $cierre['idadministrador']);
        $this->assertStringContainsString('Usuario ausente', $cierre['observacion']);
    }
}
