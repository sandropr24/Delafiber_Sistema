<?php

use App\Database\Seeds\MainSeeder;
use App\Database\Seeds\RolSeeder;
use App\Database\Seeds\SeguridadSeeder;
use App\Database\Seeds\UsuarioSeeder;
use App\Services\SeguridadInicialService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SeguridadInicialTest extends CIUnitTestCase
{
    private array $claveAnterior;
    private array $identidadAnterior;

    protected function setUp(): void
    {
        parent::setUp();
        $this->claveAnterior = [array_key_exists('seed.adminPassword', $_ENV), $_ENV['seed.adminPassword'] ?? null];
        $_ENV['seed.adminPassword'] = 'ClaveTemporalSoloPruebas-2026';
        $this->identidadAnterior = [$_ENV['seed.adminUser'] ?? null, $_ENV['seed.adminDoc'] ?? null];
        $_ENV['seed.adminUser'] = 'admin_prueba';
        $_ENV['seed.adminDoc'] = '00000001';
    }

    protected function tearDown(): void
    {
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

    /**
     * Doble de conexión: ejecuta la lógica real del servicio y los seeders,
     * pero simula los builders y las transacciones sin ejecutar SQL.
     */
    private function conexion(array $inicial = []): array
    {
        $estado = (object) [
            'tablas' => array_replace([
                'roles' => [], 'permisos' => [], 'rol_permiso' => [], 'usuario_local' => [], 'usuarios' => [],
                'personas' => [['idpersona' => 11, 'numerodoc' => '00000001']],
                'locales' => [['idlocal' => 2], ['idlocal' => 5]],
            ], $inicial),
            'id' => 0, 'instantanea' => null, 'inicios' => 0, 'confirmaciones' => 0, 'reversiones' => 0,
            'rechazar' => null, 'fallarInicio' => false, 'fallarCommit' => false, 'error' => false,
        ];
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'table', 'tableExists', 'query', 'connect', 'insertID',
            'transBegin', 'transStatus', 'transCommit', 'transRollback',
        ])->getMock();
        $db->expects($this->never())->method('connect');
        $db->expects($this->never())->method('query');
        $db->method('tableExists')->willReturnCallback(fn($tabla) => array_key_exists($tabla, $estado->tablas));
        $db->method('insertID')->willReturnCallback(fn() => $estado->id);
        $db->method('transBegin')->willReturnCallback(function () use ($estado) {
            $estado->inicios++;
            if ($estado->fallarInicio) return false;
            $estado->instantanea = $estado->tablas;
            return true;
        });
        $db->method('transStatus')->willReturnCallback(fn() => !$estado->error);
        $db->method('transCommit')->willReturnCallback(function () use ($estado) {
            if ($estado->fallarCommit) return false;
            $estado->confirmaciones++;
            $estado->instantanea = null;
            return true;
        });
        $db->method('transRollback')->willReturnCallback(function () use ($estado) {
            $estado->reversiones++;
            if ($estado->instantanea !== null) $estado->tablas = $estado->instantanea;
            $estado->instantanea = null;
            return true;
        });
        $db->method('table')->willReturnCallback(function (string $tabla) use ($estado) {
            $this->assertArrayHasKey($tabla, $estado->tablas);
            $filtro = (object) ['condiciones' => []];
            $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                'select', 'where', 'orderBy', 'get', 'countAllResults', 'insert', 'update', 'delete',
            ])->getMock();
            $builder->expects($this->never())->method('update');
            $builder->expects($this->never())->method('delete');
            $builder->method('select')->willReturnSelf();
            $builder->method('orderBy')->willReturnSelf();
            $builder->method('where')->willReturnCallback(function ($campo, $valor) use ($filtro, $builder) {
                $filtro->condiciones[$campo] = $valor;
                return $builder;
            });
            $filtrar = static function () use ($estado, $tabla, $filtro) {
                return array_values(array_filter($estado->tablas[$tabla], static function ($fila) use ($filtro) {
                    foreach ($filtro->condiciones as $campo => $valor) {
                        if ((string) ($fila[$campo] ?? '') !== (string) $valor) return false;
                    }
                    return true;
                }));
            };
            $builder->method('countAllResults')->willReturnCallback(fn() => count($filtrar()));
            $builder->method('get')->willReturnCallback(function () use ($filtrar) {
                $filas = $filtrar();
                $resultado = $this->createMock(ResultInterface::class);
                $resultado->method('getRowArray')->willReturn($filas[0] ?? null);
                $resultado->method('getResultArray')->willReturn($filas);
                return $resultado;
            });
            $builder->method('insert')->willReturnCallback(function (array $fila) use ($estado, $tabla) {
                if ($estado->rechazar === $tabla) return false;
                $clave = ['roles' => 'idrol', 'permisos' => 'idpermiso', 'usuarios' => 'idusuario'][$tabla] ?? null;
                if ($clave !== null) {
                    $fila[$clave] = max([0, ...array_column($estado->tablas[$tabla], $clave)]) + 1;
                    $estado->id = $fila[$clave];
                }
                $estado->tablas[$tabla][] = $fila;
                return true;
            });
            return $builder;
        });
        return [$db, $estado];
    }

    private function permisosDe(object $estado, string $nombre): array
    {
        $roles = array_column($estado->tablas['roles'], 'idrol', 'rol');
        $permisos = array_column($estado->tablas['permisos'], 'clave', 'idpermiso');
        $claves = [];
        foreach ($estado->tablas['rol_permiso'] as $fila) {
            if ($fila['idrol'] === $roles[$nombre]) $claves[] = $permisos[$fila['idpermiso']];
        }
        return $claves;
    }

    private function seeder(string $clase, Connection $db): Seeder
    {
        // El constructor de CI prepara Forge, innecesario para este doble sin
        // SQL. Se conserva el run() real y solo se inyecta la conexión simulada.
        $seeder = (new ReflectionClass($clase))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Seeder::class, 'db'))->setValue($seeder, $db);
        return $seeder;
    }

    public function testInicializaRolesYPermisosDespuesDeLasMigraciones(): void
    {
        [$db, $estado] = $this->conexion();
        $this->seeder(SeguridadSeeder::class, $db)->run();
        $this->assertSame(['Administrador', 'Operador', 'Vendedor'], array_column($estado->tablas['roles'], 'rol'));
        $this->assertSame(array_keys(SeguridadInicialService::PERMISOS), $this->permisosDe($estado, 'Administrador'));
        $this->assertSame(['servicios.ver', 'servicios.gestionar', 'inventario.consumir'], $this->permisosDe($estado, 'Operador'));
        $this->assertSame([], $this->permisosDe($estado, 'Vendedor'));
        $this->assertSame(1, $estado->confirmaciones);
    }

    public function testRepetirInicializacionNoDuplicaNingunaFila(): void
    {
        [$db, $estado] = $this->conexion();
        $servicio = new SeguridadInicialService($db);
        $servicio->inicializar();
        $primera = $estado->tablas;
        $servicio->inicializar();
        $this->assertSame($primera, $estado->tablas);
    }

    public function testCatalogoCreadoPorLaMigracionSeReutilizaAlCrearLosRoles(): void
    {
        $catalogo = [];
        foreach (SeguridadInicialService::PERMISOS as $clave => $descripcion) {
            $catalogo[] = ['idpermiso' => 20 + count($catalogo), 'clave' => $clave, 'descripcion' => $descripcion];
        }
        [$db, $estado] = $this->conexion(['permisos' => $catalogo]);
        (new SeguridadInicialService($db))->inicializar();
        $this->assertSame($catalogo, $estado->tablas['permisos']);
        $this->assertCount(7, $estado->tablas['rol_permiso']);
        $this->assertSame(array_keys(SeguridadInicialService::PERMISOS), $this->permisosDe($estado, 'Administrador'));
    }

    public function testUsaIdentificadoresExistentesYConservaDescripcionesPersonalizadas(): void
    {
        [$db, $estado] = $this->conexion([
            'permisos' => [['idpermiso' => 52, 'clave' => 'servicios.ver', 'descripcion' => 'Texto personalizado']],
        ]);
        (new SeguridadInicialService($db))->inicializar();
        $this->assertSame('Texto personalizado', $estado->tablas['permisos'][0]['descripcion']);
        $this->assertSame(52, $estado->tablas['rol_permiso'][0]['idpermiso']);
        $this->assertCount(4, $estado->tablas['permisos']);
    }

    public function testNoReasignaPermisosRetiradosIntencionalmente(): void
    {
        [$db, $estado] = $this->conexion();
        $servicio = new SeguridadInicialService($db);
        $servicio->inicializar();
        array_shift($estado->tablas['rol_permiso']);
        $antes = $estado->tablas['rol_permiso'];
        $servicio->inicializar();
        $this->assertSame($antes, $estado->tablas['rol_permiso']);
        $this->assertNotContains('servicios.ver', $this->permisosDe($estado, 'Administrador'));
    }

    public function testRespetaInclusoUnRolExistenteSinPermisos(): void
    {
        [$db, $estado] = $this->conexion(['roles' => [['idrol' => 70, 'rol' => 'Administrador']]]);
        (new SeguridadInicialService($db))->inicializar();
        $this->assertSame([], $this->permisosDe($estado, 'Administrador'));
        $this->assertCount(3, $estado->tablas['rol_permiso']); // Solo el nuevo Operador.
    }

    public function testNoModificaRolesPermisosNiLocalesPersonalizados(): void
    {
        $personalizadas = [
            'roles' => [['idrol' => 90, 'rol' => 'Supervisor']],
            'permisos' => [['idpermiso' => 80, 'clave' => 'custom.ver', 'descripcion' => 'Personalizado']],
            'rol_permiso' => [['idrol' => 90, 'idpermiso' => 80]],
            'usuario_local' => [['idusuario' => 30, 'idlocal' => 5]],
        ];
        [$db, $estado] = $this->conexion($personalizadas);
        (new SeguridadInicialService($db))->inicializar();
        foreach (['roles', 'permisos', 'rol_permiso'] as $tabla) {
            $this->assertSame($personalizadas[$tabla][0], $estado->tablas[$tabla][0]);
        }
        $this->assertSame($personalizadas['usuario_local'], $estado->tablas['usuario_local']);
        $this->assertNotContains('custom.ver', $this->permisosDe($estado, 'Administrador'));
    }

    public function testNoRecortaUnaAutorizacionPersonalizadaDeUnRolExistente(): void
    {
        [$db, $estado] = $this->conexion([
            'roles' => [['idrol' => 12, 'rol' => 'Operador']],
            'permisos' => [['idpermiso' => 30, 'clave' => 'inventario.anular_consumo', 'descripcion' => 'Personalizado']],
            'rol_permiso' => [['idrol' => 12, 'idpermiso' => 30]],
        ]);
        (new SeguridadInicialService($db))->inicializar();
        $this->assertSame(['inventario.anular_consumo'], $this->permisosDe($estado, 'Operador'));
    }

    #[DataProvider('tablasNecesarias')]
    public function testFallaSinMigracionesSinCrearDatosParciales(string $tabla): void
    {
        [$db, $estado] = $this->conexion();
        unset($estado->tablas[$tabla]);
        $antes = $estado->tablas;
        try {
            (new SeguridadInicialService($db))->inicializar();
            $this->fail('Debe exigir que se ejecuten las migraciones.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('migraciones', $e->getMessage());
        }
        $this->assertSame($antes, $estado->tablas);
        $this->assertSame(0, $estado->inicios);
    }

    public static function tablasNecesarias(): array
    {
        return [['roles'], ['permisos'], ['rol_permiso']];
    }

    #[DataProvider('escriturasFallidas')]
    public function testRevierteElCatalogoLosRolesYLasAutorizacionesSiFallaUnaEscritura(string $tabla): void
    {
        [$db, $estado] = $this->conexion();
        $estado->rechazar = $tabla;
        $antes = $estado->tablas;
        try {
            (new SeguridadInicialService($db))->inicializar();
            $this->fail('Debe fallar la inicialización.');
        } catch (RuntimeException $e) {
            $this->assertSame($antes, $estado->tablas);
            $this->assertSame(1, $estado->reversiones);
        }
    }

    public static function escriturasFallidas(): array
    {
        return [['permisos'], ['roles'], ['rol_permiso']];
    }

    public function testRevierteSiNoPuedeConfirmarLaTransaccion(): void
    {
        [$db, $estado] = $this->conexion();
        $estado->fallarCommit = true;
        $antes = $estado->tablas;
        try {
            (new SeguridadInicialService($db))->inicializar();
            $this->fail('Debe fallar el commit.');
        } catch (RuntimeException $e) {
            $this->assertSame($antes, $estado->tablas);
            $this->assertSame(1, $estado->reversiones);
        }
    }

    public function testAdministradorNuevoTieneLocalesYContrasenaHasheada(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        $this->seeder(UsuarioSeeder::class, $db)->run();
        $usuario = $estado->tablas['usuarios'][0];
        $this->assertSame('admin_prueba', $usuario['nombreusuario']);
        $this->assertTrue(password_verify($_ENV['seed.adminPassword'], $usuario['claveacceso']));
        $this->assertNotSame($_ENV['seed.adminPassword'], $usuario['claveacceso']);
        $this->assertSame([2, 5], array_column($estado->tablas['usuario_local'], 'idlocal'));
        $this->assertSame([$usuario['idusuario']], array_values(array_unique(array_column($estado->tablas['usuario_local'], 'idusuario'))));
    }

    public function testAdministradorExigeIdentidadConfiguradaSinEscribir(): void
    {
        foreach (['seed.adminUser', 'seed.adminDoc'] as $clave) {
            $anterior = $_ENV[$clave];
            $_ENV[$clave] = '';
            [$db, $estado] = $this->conexion();
            try {
                $this->seeder(UsuarioSeeder::class, $db)->run();
                $this->fail('Debe exigir la identidad del administrador.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('seed.adminUser y seed.adminDoc', $e->getMessage());
                $this->assertSame(0, $estado->inicios);
                $this->assertSame([], $estado->tablas['usuarios']);
            } finally {
                $_ENV[$clave] = $anterior;
            }
        }
    }

    public function testUsuarioExistenteNoSeReactivaNiRecibeOtrosLocalesAlRepetirElSeeder(): void
    {
        $existentes = [
            'usuarios' => [['idusuario' => 8, 'nombreusuario' => 'admin_prueba', 'idrol' => 99, 'estado' => 0, 'claveacceso' => 'HashExistente']],
            'usuario_local' => [['idusuario' => 8, 'idlocal' => 5]],
        ];
        [$db, $estado] = $this->conexion($existentes);
        $this->seeder(UsuarioSeeder::class, $db)->run();
        $this->assertSame($existentes['usuarios'], $estado->tablas['usuarios']);
        $this->assertSame($existentes['usuario_local'], $estado->tablas['usuario_local']);
        $this->assertSame(0, $estado->inicios);
    }

    public function testRepetirUsuarioSeederNoAsignaLocalesNuevosNiCambiaLaClave(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        $seeder = $this->seeder(UsuarioSeeder::class, $db);
        $seeder->run();
        $usuarios = $estado->tablas['usuarios'];
        $asignaciones = $estado->tablas['usuario_local'];
        $estado->tablas['locales'][] = ['idlocal' => 9];
        $_ENV['seed.adminPassword'] = 'OtraClaveQueNoDebeAplicarse';
        $seeder->run();
        $this->assertSame($usuarios, $estado->tablas['usuarios']);
        $this->assertSame($asignaciones, $estado->tablas['usuario_local']);
    }

    public function testNoCreaAdministradorSinLocalesConfigurados(): void
    {
        [$db, $estado] = $this->conexion(['locales' => []]);
        (new SeguridadInicialService($db))->inicializar();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('al menos un local');
        $this->seeder(UsuarioSeeder::class, $db)->run();
    }

    public function testNoCreaAdministradorConClaveDemasiadoCorta(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        $_ENV['seed.adminPassword'] = 'corta';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('seed.adminPassword');
        $this->seeder(UsuarioSeeder::class, $db)->run();
    }

    public function testNoDejaUnAdministradorSinLaTablaDeAsignaciones(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        unset($estado->tablas['usuario_local']);
        try {
            $this->seeder(UsuarioSeeder::class, $db)->run();
            $this->fail('Debe exigir las migraciones de asignación de locales.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('migraciones', $e->getMessage());
            $this->assertSame([], $estado->tablas['usuarios']);
        }
    }

    public function testSiFallaLaAsignacionDeLocalesTampocoQuedaCreadoElUsuario(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        $estado->rechazar = 'usuario_local';
        try {
            $this->seeder(UsuarioSeeder::class, $db)->run();
            $this->fail('Debe revertir usuario y locales.');
        } catch (RuntimeException $e) {
            $this->assertSame([], $estado->tablas['usuarios']);
            $this->assertSame([], $estado->tablas['usuario_local']);
            $this->assertSame(1, $estado->reversiones);
        }
    }

    public function testNoEscribeSiNoPuedeIniciarLaTransaccion(): void
    {
        [$db, $estado] = $this->conexion();
        $estado->fallarInicio = true;
        $antes = $estado->tablas;
        try {
            (new SeguridadInicialService($db))->inicializar();
            $this->fail('Debe fallar el inicio de la transacción.');
        } catch (RuntimeException $e) {
            $this->assertSame($antes, $estado->tablas);
            $this->assertSame(0, $estado->confirmaciones);
        }
    }

    public function testAdministradorSeRevierteSiNoPuedeConfirmarLaTransaccion(): void
    {
        [$db, $estado] = $this->conexion();
        (new SeguridadInicialService($db))->inicializar();
        $estado->fallarCommit = true;
        try {
            $this->seeder(UsuarioSeeder::class, $db)->run();
            $this->fail('Debe fallar el commit del administrador.');
        } catch (RuntimeException $e) {
            $this->assertSame([], $estado->tablas['usuarios']);
            $this->assertSame([], $estado->tablas['usuario_local']);
            $this->assertSame(1, $estado->reversiones);
        }
    }

    public function testMainSeederInicializaSeguridadYLocalesAntesDelAdministrador(): void
    {
        $llamadas = [];
        $seeder = $this->getMockBuilder(MainSeeder::class)->disableOriginalConstructor()->onlyMethods(['call'])->getMock();
        $seeder->method('call')->willReturnCallback(function ($clase) use (&$llamadas) { $llamadas[] = $clase; });
        $seeder->run();
        $this->assertSame('SeguridadSeeder', $llamadas[0]);
        $this->assertLessThan(array_search('UsuarioSeeder', $llamadas, true), array_search('LocalSeeder', $llamadas, true));
        $this->assertLessThan(array_search('UsuarioSeeder', $llamadas, true), array_search('PersonaSeeder', $llamadas, true));
    }

    public function testRolSeederAnteriorDelegaEnLaInicializacionSegura(): void
    {
        $seeder = $this->getMockBuilder(RolSeeder::class)->disableOriginalConstructor()->onlyMethods(['call'])->getMock();
        $seeder->expects($this->once())->method('call')->with('SeguridadSeeder');
        $seeder->run();
    }
}
