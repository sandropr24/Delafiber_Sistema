<?php

use App\Services\CajaService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CajaServiceTest extends CIUnitTestCase
{
    /** Doble en memoria de SQL/builders: ninguna conexión ni migración real. */
    private function entorno(bool $abierta = false): array
    {
        $estado = (object) [
            'rol' => 'Administrador', 'activo' => true, 'ultimoId' => 0,
            'rollback' => 0, 'commit' => 0, 'fallar' => '', 'sql' => [], 'snapshot' => null,
            'tablas' => [
                'locales' => [['idlocal' => 2]],
                'usuario_local' => [['idusuario' => 7, 'idlocal' => 2]],
                'cajas' => [['idcaja' => 8, 'idlocal' => 2, 'estado' => 1, 'nombre' => 'Caja principal']],
                'aperturacaja' => $abierta ? [['idapertura' => 9, 'idcaja' => 8, 'idusuario' => 7, 'estado' => 'ABIERTA', 'montoinicial' => '100.00']] : [],
                'tipospago' => [
                    ['idtipopago' => 1, 'tipopago' => 'Efectivo', 'estado' => 1],
                    ['idtipopago' => 2, 'tipopago' => 'Yape', 'estado' => 1],
                ],
                'pagoventa' => [
                    ['idpagoventa' => 1, 'idapertura' => 9, 'idtipopago' => 1, 'monto' => '30.00'],
                    ['idpagoventa' => 2, 'idapertura' => 9, 'idtipopago' => 2, 'monto' => '20.00'],
                    ['idpagoventa' => 3, 'idapertura' => null, 'idtipopago' => 1, 'monto' => '999.00'],
                ],
                'cierrecaja_detalle' => [], 'cuentasbancarias' => [], 'movimientos_caja' => [],
                'cierres_caja_administrativos' => [], 'reembolsos_venta' => [], 'anulaciones_comerciales' => [],
            ],
        ];
        $resultado = function (array $filas): ResultInterface {
            $r = $this->createMock(ResultInterface::class);
            $r->method('getRowArray')->willReturn($filas[0] ?? null);
            $r->method('getResultArray')->willReturn(array_values($filas));
            return $r;
        };
        $filtrar = static function (array $filas, array $filtros): array {
            return array_values(array_filter($filas, static function ($fila) use ($filtros) {
                foreach ($filtros as $campo => $valor) {
                    if ((string) ($fila[$campo] ?? '') !== (string) $valor) return false;
                }
                return true;
            }));
        };
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'query', 'table', 'connect', 'tableExists', 'transBegin', 'transStatus', 'transCommit', 'transRollback', 'insertID',
        ])->getMock();
        $db->DBDriver = 'MySQLi';
        $db->expects($this->never())->method('connect');
        $db->method('tableExists')->willReturn(true);
        $db->method('insertID')->willReturnCallback(fn() => $estado->ultimoId);
        $db->method('transBegin')->willReturnCallback(function () use ($estado) { $estado->snapshot = $estado->tablas; return true; });
        $db->method('transStatus')->willReturn(true);
        $db->method('transCommit')->willReturnCallback(function () use ($estado) { $estado->commit++; return true; });
        $db->method('transRollback')->willReturnCallback(function () use ($estado) {
            $estado->rollback++;
            $estado->tablas = $estado->snapshot;
            return true;
        });
        $db->method('query')->willReturnCallback(function ($sql, $parametros = []) use ($estado, $resultado, $filtrar) {
            $estado->sql[] = $sql;
            if (str_starts_with($sql, 'SELECT u.idusuario')) {
                return $resultado($estado->activo ? [['idusuario' => $parametros[0], 'rol' => $estado->rol]] : []);
            }
            if (str_starts_with($sql, 'SELECT * FROM cajas')) return $resultado($filtrar($estado->tablas['cajas'], ['idcaja' => $parametros[0]]));
            if (str_starts_with($sql, 'SELECT * FROM aperturacaja')) return $resultado($filtrar($estado->tablas['aperturacaja'], ['idapertura' => $parametros[0]]));
            if (str_starts_with($sql, 'SELECT * FROM cuentasbancarias')) return $resultado($filtrar($estado->tablas['cuentasbancarias'], ['idcuenta' => $parametros[0]]));
            if (str_starts_with($sql, 'SELECT idapertura FROM aperturacaja')) {
                return $resultado(array_values(array_filter($estado->tablas['aperturacaja'], fn($a) => $a['estado'] === 'ABIERTA' && ($a['idusuario'] === $parametros[0] || $a['idcaja'] === $parametros[1]))));
            }
            if (str_starts_with($sql, 'SELECT * FROM tipospago')) return $resultado($estado->tablas['tipospago']);
            if (str_starts_with($sql, 'SELECT idtipopago, monto FROM pagoventa')) return $resultado($filtrar($estado->tablas['pagoventa'], ['idapertura' => $parametros[0]]));
            if (str_starts_with($sql, 'SELECT idtipopago, tipo, monto FROM movimientos_caja')) return $resultado($filtrar($estado->tablas['movimientos_caja'], ['idapertura' => $parametros[0]]));
            throw new LogicException('SQL inesperado: ' . $sql);
        });
        $db->method('table')->willReturnCallback(function ($nombre) use ($estado, $resultado, $filtrar) {
            $tabla = explode(' ', $nombre)[0];
            $this->assertArrayHasKey($tabla, $estado->tablas);
            $filtros = [];
            $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                'select', 'join', 'where', 'get', 'countAllResults', 'insert', 'update', 'orderBy', 'delete',
            ])->getMock();
            foreach (['select', 'join', 'orderBy'] as $metodo) $builder->method($metodo)->willReturnSelf();
            $builder->expects($this->never())->method('delete');
            $builder->method('where')->willReturnCallback(function ($campo, $valor) use (&$filtros, $builder) {
                $partes = explode('.', $campo);
                $filtros[end($partes)] = $valor;
                return $builder;
            });
            $builder->method('countAllResults')->willReturnCallback(function () use ($filtrar, $estado, $tabla, &$filtros) {
                return count($filtrar($estado->tablas[$tabla], $filtros));
            });
            $builder->method('get')->willReturnCallback(function () use ($tabla, $nombre, &$filtros, $estado, $resultado, $filtrar) {
                $filas = $filtrar($estado->tablas[$tabla], $filtros);
                if ($nombre === 'aperturacaja a') {
                    $filas = array_map(fn($fila) => $fila + ['idlocal' => 2, 'caja' => 'Caja principal', 'nombrelocal' => 'Tienda', 'nombreusuario' => 'Prueba'], $filas);
                }
                return $resultado($filas);
            });
            $builder->method('insert')->willReturnCallback(function ($fila) use ($tabla, $estado) {
                if ($estado->fallar === $tabla) return false;
                $id = ['aperturacaja' => 'idapertura', 'tipospago' => 'idtipopago', 'cuentasbancarias' => 'idcuenta', 'cajas' => 'idcaja', 'movimientos_caja' => 'idmovimientocaja'][$tabla] ?? null;
                if ($id) {
                    $estado->ultimoId = max([0, ...array_column($estado->tablas[$tabla], $id)]) + 1;
                    $fila[$id] = $estado->ultimoId;
                }
                $estado->tablas[$tabla][] = $fila;
                return true;
            });
            $builder->method('update')->willReturnCallback(function ($datos) use ($tabla, &$filtros, $estado, $filtrar) {
                if ($estado->fallar === $tabla) return false;
                foreach ($estado->tablas[$tabla] as &$fila) {
                    if ($filtrar([$fila], $filtros)) $fila = array_replace($fila, $datos);
                }
                unset($fila);
                return true;
            });
            return $builder;
        });
        return [new CajaService($db), $estado];
    }

    public function testAperturaGuardaUsuarioEInicialYRepetirNoDuplica(): void
    {
        [$servicio, $estado] = $this->entorno();
        $id = $servicio->abrir(8, '150.50', 7);
        $this->assertGreaterThan(0, $id);
        $this->assertSame(7, $estado->tablas['aperturacaja'][0]['idusuario']);
        $this->assertSame('150.50', $estado->tablas['aperturacaja'][0]['montoinicial']);
        try { $servicio->abrir(8, '10.00', 7); $this->fail('No debe abrir dos veces.'); }
        catch (InvalidArgumentException) { $this->assertCount(1, $estado->tablas['aperturacaja']); }
        $this->assertStringContainsString('FOR UPDATE', $estado->sql[0]);
        $this->assertStringContainsString('FROM cajas', $estado->sql[1]);
        $this->assertStringContainsString('FROM aperturacaja', $estado->sql[2]);
    }

    public function testNoComparteUnaCajaYaAbiertaPorOtroUsuario(): void
    {
        [$servicio, $estado] = $this->entorno(true);
        $estado->tablas['aperturacaja'][0]['idusuario'] = 99;
        $this->expectException(InvalidArgumentException::class);
        $servicio->abrir(8, '0.00', 7);
    }

    public function testVendedorNoPuedeAbrirEnUnLocalNoAsignado(): void
    {
        [$servicio, $estado] = $this->entorno();
        $estado->rol = 'Vendedor';
        $estado->tablas['usuario_local'] = [];
        $this->expectException(InvalidArgumentException::class);
        $servicio->abrir(8, '0.00', 7);
    }

    public function testVendedorConLocalAsignadoPuedeAbrir(): void
    {
        [$servicio, $estado] = $this->entorno();
        $estado->rol = 'Vendedor';
        $servicio->abrir(8, '0.00', 7);
        $this->assertCount(1, $estado->tablas['aperturacaja']);
    }

    public function testUsuarioDesactivadoNoPuedeOperar(): void
    {
        [$servicio, $estado] = $this->entorno();
        $estado->activo = false;
        $this->expectException(InvalidArgumentException::class);
        $servicio->abrir(8, '0.00', 7);
    }

    #[DataProvider('aperturasInvalidas')]
    public function testCobroRechazaAperturaAjenaCerradaOLocalDistinto(string $cambio): void
    {
        [$servicio, $estado] = $this->entorno(true);
        if ($cambio === 'ajena') $estado->tablas['aperturacaja'][0]['idusuario'] = 99;
        if ($cambio === 'cerrada') $estado->tablas['aperturacaja'][0]['estado'] = 'CERRADA';
        if ($cambio === 'local') $estado->tablas['cajas'][0]['idlocal'] = 5;
        $this->expectException(InvalidArgumentException::class);
        $servicio->bloquearCobro(9, 2, 7);
    }

    public static function aperturasInvalidas(): array
    {
        return [['ajena'], ['cerrada'], ['local']];
    }

    public function testCierreUsaCobrosActualesBajoBloqueoYNoPagosHistoricosSinApertura(): void
    {
        [$servicio, $estado] = $this->entorno(true);
        $servicio->cerrar(9, [1 => '130.00', 2 => '20.00'], '', 7);
        $this->assertSame('CERRADA', $estado->tablas['aperturacaja'][0]['estado']);
        $this->assertSame(['130.00', '20.00'], array_column($estado->tablas['cierrecaja_detalle'], 'montoesperado'));
        $this->assertSame(1, $estado->commit);
        $this->assertStringContainsString('FOR UPDATE', end($estado->sql));
        $this->assertStringContainsString('FROM movimientos_caja', end($estado->sql));
        $antes = $estado->tablas;
        try { $servicio->cerrar(9, [1 => '130.00', 2 => '20.00'], '', 7); $this->fail('No debe cerrar dos veces.'); }
        catch (InvalidArgumentException) { $this->assertSame($antes, $estado->tablas); }
    }

    public function testFalloDeEscrituraRevierteTodoElArqueo(): void
    {
        [$servicio, $estado] = $this->entorno(true);
        $estado->fallar = 'aperturacaja';
        try { $servicio->cerrar(9, [1 => '130.00', 2 => '20.00'], '', 7); $this->fail('Debe fallar la escritura.'); }
        catch (RuntimeException) {
            $this->assertSame([], $estado->tablas['cierrecaja_detalle']);
            $this->assertSame('ABIERTA', $estado->tablas['aperturacaja'][0]['estado']);
            $this->assertSame(1, $estado->rollback);
        }
    }

    public function testNoDesactivaUnaCajaConAperturaVigente(): void
    {
        [$servicio, $estado] = $this->entorno(true);
        $this->expectException(InvalidArgumentException::class);
        $servicio->cambiarCaja(8, 0, 7);
    }

    public function testSoloAdministradorPuedeConfigurar(): void
    {
        [$servicio, $estado] = $this->entorno();
        $estado->rol = 'Vendedor';
        $this->expectException(InvalidArgumentException::class);
        $servicio->crearCaja(2, 'Otra caja', 7);
    }

    public function testCompletarMediosEsIdempotenteYConservaUnMedioDesactivado(): void
    {
        [$servicio, $estado] = $this->entorno();
        $estado->tablas['tipospago'][1]['estado'] = 0;
        $servicio->completarMedios(7);
        $primera = $estado->tablas['tipospago'];
        $servicio->completarMedios(7);
        $this->assertSame($primera, $estado->tablas['tipospago']);
        $this->assertSame(0, $primera[1]['estado']);
        $this->assertContains('QR', array_column($primera, 'tipopago'));
        $this->assertContains('Depósito', array_column($primera, 'tipopago'));
    }

    public function testCuentaDestinoNoSeSobrescribeYSePuedeDesactivar(): void
    {
        [$servicio, $estado] = $this->entorno();
        $datos = ['banco' => 'BCP', 'numerocuenta' => '123-456789', 'cci' => '', 'moneda' => 'PEN'];
        $servicio->crearCuenta($datos, 7);
        $id = $estado->tablas['cuentasbancarias'][0]['idcuenta'];
        $servicio->cambiarCuenta($id, 0, 7);
        $this->assertSame('123-456789', $estado->tablas['cuentasbancarias'][0]['numerocuenta']);
        $this->assertSame(0, $estado->tablas['cuentasbancarias'][0]['estado']);
        try { $servicio->crearCuenta($datos, 7); $this->fail('No debe duplicar la cuenta.'); }
        catch (InvalidArgumentException) { $this->assertCount(1, $estado->tablas['cuentasbancarias']); }
    }
}
