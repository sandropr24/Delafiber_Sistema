<?php

use App\Database\Seeds\SeguridadSeeder;
use App\Database\Seeds\UsuarioSeeder;
use App\Services\PermissionService;
use App\Services\SeguridadInicialService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class SeguridadInicialDatabaseTest extends CIUnitTestCase
{
    private ?BaseConnection $conexion = null;
    private array $claveAnterior;
    private array $identidadAnterior;

    protected function setUp(): void
    {
        parent::setUp();
        $this->claveAnterior = [array_key_exists('seed.adminPassword', $_ENV), $_ENV['seed.adminPassword'] ?? null];
        $this->identidadAnterior = [$_ENV['seed.adminUser'] ?? null, $_ENV['seed.adminDoc'] ?? null];
        $_ENV['seed.adminUser'] = 'admin_prueba';
        $_ENV['seed.adminDoc'] = '00000001';
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('Se requiere SQLite3 para probar la inicialización de seguridad con una base de datos en memoria.');
        }
        $_ENV['seed.adminPassword'] = 'ClaveTemporalSoloPruebas-2026';
        $this->conexion = Database::connect([
            'database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '', 'foreignKeys' => true,
        ], false);
        $consultas = [
            'CREATE TABLE roles (idrol INTEGER PRIMARY KEY AUTOINCREMENT, rol TEXT UNIQUE NOT NULL)',
            'CREATE TABLE permisos (idpermiso INTEGER PRIMARY KEY AUTOINCREMENT, clave TEXT UNIQUE NOT NULL, descripcion TEXT)',
            'CREATE TABLE rol_permiso (idrol INTEGER REFERENCES roles(idrol), idpermiso INTEGER REFERENCES permisos(idpermiso), PRIMARY KEY (idrol, idpermiso))',
            'CREATE TABLE personas (idpersona INTEGER PRIMARY KEY, numerodoc TEXT)',
            'CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY AUTOINCREMENT, idpersona INTEGER REFERENCES personas(idpersona), idrol INTEGER REFERENCES roles(idrol), nombreusuario TEXT UNIQUE, claveacceso TEXT, estado INTEGER)',
            'CREATE TABLE locales (idlocal INTEGER PRIMARY KEY)',
            'CREATE TABLE usuario_local (idusuario INTEGER REFERENCES usuarios(idusuario), idlocal INTEGER REFERENCES locales(idlocal), PRIMARY KEY (idusuario, idlocal))',
        ];
        foreach ($consultas as $consulta) {
            $this->conexion->query($consulta);
        }
        // Simula migrate sobre una base vacía: existe el catálogo de permisos,
        // pero todavía no existen los roles ni el administrador del seeder.
        foreach (SeguridadInicialService::PERMISOS as $clave => $descripcion) {
            $this->conexion->table('permisos')->insert(compact('clave', 'descripcion'));
        }
        $this->conexion->table('personas')->insert(['idpersona' => 11, 'numerodoc' => '00000001']);
        $this->conexion->table('locales')->insertBatch([['idlocal' => 2], ['idlocal' => 5]]);
    }

    protected function tearDown(): void
    {
        $this->conexion?->close();
        foreach (['seed.adminUser', 'seed.adminDoc'] as $i => $clave) {
            if ($this->identidadAnterior[$i] === null) unset($_ENV[$clave]);
            else $_ENV[$clave] = $this->identidadAnterior[$i];
        }
        if ($this->claveAnterior[0]) {
            $_ENV['seed.adminPassword'] = $this->claveAnterior[1];
        } else {
            unset($_ENV['seed.adminPassword']);
        }
        parent::tearDown();
    }

    private function seguridad(): void
    {
        (new SeguridadSeeder(config(Database::class), $this->conexion))->setSilent(true)->run();
    }

    private function admin(): array
    {
        (new UsuarioSeeder(config(Database::class), $this->conexion))->setSilent(true)->run();
        return $this->conexion->table('usuarios')->where('nombreusuario', 'admin_prueba')->get()->getRowArray();
    }

    public function testInstalacionNuevaDaAccesoAServiciosYLocalesAlAdministrador(): void
    {
        $this->seguridad();
        $admin = $this->admin();
        $permisos = new PermissionService($this->conexion);
        foreach (array_keys(SeguridadInicialService::PERMISOS) as $clave) {
            $this->assertTrue($permisos->can((int) $admin['idusuario'], $clave));
        }
        $this->assertTrue($permisos->canUseLocation((int) $admin['idusuario'], 2));
        $this->assertTrue($permisos->canUseLocation((int) $admin['idusuario'], 5));
        $this->assertFalse($permisos->canUseLocation((int) $admin['idusuario'], 99));
        $this->assertTrue(password_verify($_ENV['seed.adminPassword'], $admin['claveacceso']));
    }

    public function testOperadorNoRecibePermisoParaAnularYVendedorNoRecibePermisosDeServicios(): void
    {
        $this->seguridad();
        $permisos = new PermissionService($this->conexion);
        foreach (['Operador', 'Vendedor'] as $nombre) {
            $rol = $this->conexion->table('roles')->where('rol', $nombre)->get()->getRow('idrol');
            $this->conexion->table('usuarios')->insert(['idpersona' => 11, 'idrol' => $rol, 'nombreusuario' => $nombre, 'estado' => 1]);
            $id = (int) $this->conexion->insertID();
            $this->assertSame($nombre === 'Operador', $permisos->can($id, 'servicios.gestionar'));
            $this->assertFalse($permisos->can($id, 'inventario.anular_consumo'));
            $this->assertFalse($permisos->canUseLocation($id, 2));
        }
    }

    public function testRepetirLosSeedersEsIdempotente(): void
    {
        $this->seguridad();
        $this->admin();
        $tablas = ['roles', 'permisos', 'rol_permiso', 'usuarios', 'usuario_local'];
        $antes = [];
        foreach ($tablas as $tabla) $antes[$tabla] = $this->conexion->table($tabla)->get()->getResultArray();
        $this->seguridad();
        $this->admin();
        foreach ($tablas as $tabla) {
            $this->assertSame($antes[$tabla], $this->conexion->table($tabla)->get()->getResultArray());
        }
    }

    public function testNoReintroducePermisosNiLocalesRetirados(): void
    {
        $this->seguridad();
        $admin = $this->admin();
        $idPermiso = $this->conexion->table('permisos')->where('clave', 'inventario.anular_consumo')->get()->getRow('idpermiso');
        $this->conexion->table('rol_permiso')->where('idrol', $admin['idrol'])->where('idpermiso', $idPermiso)->delete();
        $this->conexion->table('usuario_local')->where('idusuario', $admin['idusuario'])->where('idlocal', 5)->delete();
        $this->seguridad();
        $this->admin();
        $permisos = new PermissionService($this->conexion);
        $this->assertFalse($permisos->can((int) $admin['idusuario'], 'inventario.anular_consumo'));
        $this->assertFalse($permisos->canUseLocation((int) $admin['idusuario'], 5));
        $this->assertTrue($permisos->canUseLocation((int) $admin['idusuario'], 2));
    }

    public function testNoReactivaUnaCuentaDesactivada(): void
    {
        $this->seguridad();
        $admin = $this->admin();
        $this->conexion->table('usuarios')->where('idusuario', $admin['idusuario'])->update(['estado' => 0]);
        $repetido = $this->admin();
        $this->assertSame(0, (int) $repetido['estado']);
        $this->assertFalse((new PermissionService($this->conexion))->can((int) $admin['idusuario'], 'servicios.ver'));
    }

    public function testConservaUnAdministradorHistoricoSinPermisos(): void
    {
        $this->conexion->table('roles')->insert(['idrol' => 70, 'rol' => 'Administrador']);
        $this->seguridad();
        $this->assertSame(0, $this->conexion->table('rol_permiso')->where('idrol', 70)->countAllResults());
        $this->assertSame(3, $this->conexion->table('rol_permiso')->countAllResults());
    }

    public function testUnErrorSqlRevierteLaCreacionDelRol(): void
    {
        $this->conexion->query("CREATE TRIGGER impedir_asignacion BEFORE INSERT ON rol_permiso BEGIN SELECT RAISE(ABORT, 'Fallo simulado de permisos'); END");
        try {
            $this->seguridad();
            $this->fail('La inicialización no puede quedar confirmada parcialmente.');
        } catch (RuntimeException $e) {
            $this->assertSame(0, $this->conexion->table('roles')->countAllResults());
            $this->assertSame(0, $this->conexion->table('rol_permiso')->countAllResults());
        }
    }
}
