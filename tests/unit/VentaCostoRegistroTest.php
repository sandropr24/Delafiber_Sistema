<?php

use App\Models\DetVentaModel;
use App\Models\EntregaModel;
use App\Models\PagoVentaModel;
use App\Models\VentaModel;
use App\Services\VoucherStorage;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class VentaCostoRegistroTest extends CIUnitTestCase
{
    private array $conexiones;
    private array $archivosPropios = [];
    private array $directoriosPropios = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->conexiones = Database::getConnections();
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, $this->conexiones);
        Factories::reset('models');
        foreach ($this->archivosPropios as $archivo) {
            if (is_file($archivo)) unlink($archivo);
        }
        foreach ($this->directoriosPropios as $directorio) {
            if (is_dir($directorio)) rmdir($directorio);
        }
        parent::tearDown();
    }

    /**
     * Ejecuta registrarVenta() real y su bloqueo de configuración. Solo se
     * sustituyen transporte SQL y escrituras de modelos: nunca conecta a BD.
     */
    private function registrar(int $local = 2, string $escenario = 'normal'): array
    {
        $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cobro-storage-' . bin2hex(random_bytes(8));
        $storage = new VoucherStorage($directorio);
        $this->directoriosPropios[] = $directorio;
        $articulos = [
            1 => ['idproducto' => 1, 'nombre' => 'Cámara', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'preciocosto' => '999.00', 'garantiameses' => 12],
            2 => ['idproducto' => 2, 'nombre' => 'Cable', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'preciocosto' => '0.00'],
            3 => ['idproducto' => 3, 'nombre' => 'Instalación', 'tipo' => 'SERVICIO', 'controlastock' => 0, 'preciocosto' => '15.00'],
            4 => ['idproducto' => 4, 'nombre' => 'Kit', 'tipo' => 'COMBO', 'controlastock' => 0, 'preciocosto' => '888.00', 'garantiameses' => 1],
        ];
        foreach ($articulos as &$articulo) {
            $articulo += ['controlaserie' => 0, 'idunidad' => $articulo['idproducto'] === 2 ? 2 : 1, 'estado' => 1, 'precioventa' => '1.00'];
        }
        unset($articulo);
        $costos = $local === 2 ? [1 => '10.1234', 2 => '2.9700'] : [1 => '20.4567', 2 => '4.0000'];
        if ($escenario === 'costo_invalido') $costos[1] = '-1.0000';
        $estado = (object) [
            'detalles' => [], 'inserciones' => [], 'kardex' => [],
            'commit' => 0, 'rollback' => 0, 'pagos' => [], 'storage' => $storage,
        ];
        $resultado = function (array $filas): ResultInterface {
            $r = $this->createMock(ResultInterface::class);
            $r->method('getRowArray')->willReturn($filas[0] ?? null);
            $r->method('getResultArray')->willReturn($filas);
            $r->method('getRow')->willReturnCallback(fn($campo = 0) => is_string($campo) ? ($filas[0][$campo] ?? null) : ($filas[0] ?? null));
            return $r;
        };
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'query', 'table', 'tableExists', 'connect', 'transBegin', 'transCommit', 'transRollback', 'transStatus', 'insertID',
        ])->getMock();
        $db->method('tableExists')->willReturnCallback(fn(string $tabla): bool => $tabla === 'usuario_local');
        $db->DBDriver = 'MySQLi';
        $db->expects($this->never())->method('connect');
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->method('insertID')->willReturn(42);
        $db->method('transCommit')->willReturnCallback(function () use ($estado) { $estado->commit++; return true; });
        $db->method('transRollback')->willReturnCallback(function () use ($estado) { $estado->rollback++; return true; });
        $db->method('query')->willReturnCallback(function ($sql, $parametros = []) use ($resultado, $articulos, $costos, $local, $estado, $escenario) {
            if (str_starts_with($sql, 'SELECT idusuario FROM usuarios')) return $resultado([]);
            if (str_starts_with($sql, 'SELECT u.idusuario, r.rol')) return $resultado([['idusuario' => 7, 'rol' => 'Administrador']]);
            if (str_starts_with($sql, 'SELECT * FROM cajas')) return $resultado([['idcaja' => 8, 'idlocal' => $local, 'estado' => 1]]);
            if (str_starts_with($sql, 'SELECT * FROM aperturacaja')) return $resultado([['idapertura' => 9, 'idcaja' => 8, 'idusuario' => 7, 'estado' => $escenario === 'cerrada' ? 'CERRADA' : 'ABIERTA']]);
            if (str_starts_with($sql, 'SELECT * FROM cuentasbancarias')) return $resultado([['idcuenta' => 11, 'estado' => 1, 'moneda' => 'PEN']]);
            if (str_starts_with($sql, 'SELECT DISTINCT idproducto FROM combo_detalle')) {
                return $resultado([['idproducto' => 1], ['idproducto' => 2], ['idproducto' => 3]]);
            }
            if (str_starts_with($sql, 'SELECT * FROM productos')) {
                $this->assertStringEndsWith('FOR UPDATE', $sql);
                return $resultado([$articulos[$parametros[0]]]);
            }
            if (str_starts_with($sql, 'SELECT idproducto, cantidad FROM combo_detalle')) {
                return $resultado([
                    ['idproducto' => 1, 'cantidad' => '2.00'],
                    ['idproducto' => 2, 'cantidad' => '1.25'],
                    ['idproducto' => 3, 'cantidad' => '1.00'],
                ]);
            }
            if (str_starts_with($sql, 'SELECT idunidad, abreviatura')) {
                return $resultado([['idunidad' => 1, 'abreviatura' => 'UND'], ['idunidad' => 2, 'abreviatura' => 'M']]);
            }
            if (str_contains($sql, 'FROM kardex')) {
                $this->assertStringContainsString('costopromedio', $sql);
                $this->assertStringContainsString('idproducto = ? AND idlocal = ?', $sql);
                $this->assertStringEndsWith('FOR UPDATE', $sql);
                $this->assertSame($local, $parametros[1]);
                $id = $parametros[0];
                $estado->kardex[] = $id;
                return $resultado([['idkardex' => $id + 10, 'stockactual' => '100.00', 'costopromedio' => $costos[$id]]]);
            }
            if (str_starts_with($sql, 'INSERT INTO correlativos')) return $resultado([]);
            if (str_starts_with($sql, 'SELECT * FROM correlativos')) return $resultado([['idcorrelativo' => 1, 'ultimonumero' => 0]]);
            if (str_starts_with($sql, 'SELECT MAX(CAST(numero')) return $resultado([['maximo' => 0]]);
            throw new LogicException('Consulta SQL no prevista en la prueba: ' . $sql);
        });
        $electronico = in_array($escenario, ['electronico', 'fallo_pago'], true);
        $db->method('table')->willReturnCallback(function ($tabla) use ($resultado, $estado, $escenario, $electronico) {
            $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                'select', 'selectSum', 'join', 'where', 'get', 'countAllResults', 'insert', 'update', 'delete',
            ])->getMock();
            foreach (['select', 'selectSum', 'join', 'where'] as $metodo) $builder->method($metodo)->willReturnSelf();
            $builder->expects($this->never())->method('delete');
            $builder->method('countAllResults')->willReturn(1);
            $builder->method('get')->willReturnCallback(function () use ($tabla, $resultado, $escenario, $electronico) {
                $filas = match ($tabla) {
                    'personas' => [['idpersona' => 1]],
                    'usuarios u' => [['rol' => 'Administrador']],
                    'estadoventa' => [['estado' => 'Pendiente']],
                    'reservas_stock' => [['total' => 0]],
                    'tipospago' => [['tipopago' => $electronico ? 'Yape' : 'Efectivo']],
                    'ventas' => $escenario === 'reintento' ? [['idvendedor' => 7, 'idpedido' => null, 'idcotizacion' => null]] : [],
                    'aperturacaja' => [['idcaja' => 8]],
                    default => throw new LogicException('Lectura no prevista: ' . $tabla),
                };
                return $resultado($filas);
            });
            $builder->method('insert')->willReturnCallback(function ($fila) use ($tabla, $estado) {
                $this->assertContains($tabla, ['ventas', 'venta_combo_componentes', 'reservas_stock']);
                $estado->inserciones[$tabla][] = $fila;
                return true;
            });
            $builder->method('update')->willReturnCallback(function () use ($tabla) {
                $this->assertSame('correlativos', $tabla);
                return true;
            });
            return $builder;
        });
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, ['tests' => $db]);

        $detalleModel = $this->getMockBuilder(DetVentaModel::class)->disableOriginalConstructor()->onlyMethods(['insert'])->getMock();
        $detalleModel->method('insert')->willReturnCallback(function ($fila) use ($estado) {
            $estado->detalles[] = $fila;
            return count($estado->detalles);
        });
        Factories::injectMock('models', DetVentaModel::class, $detalleModel);
        foreach ([PagoVentaModel::class, EntregaModel::class] as $clase) {
            $modelo = $this->getMockBuilder($clase)->disableOriginalConstructor()->onlyMethods(['insert'])->getMock();
            $modelo->method('insert')->willReturnCallback(function ($fila) use ($clase, $estado, $escenario, $storage) {
                if ($clase === PagoVentaModel::class) {
                    $estado->pagos[] = $fila;
                    if ($fila['voucher']) $this->archivosPropios[] = $storage->ruta($fila['voucher']);
                    if ($escenario === 'fallo_pago') return false;
                }
                return 1;
            });
            Factories::injectMock('models', $clase, $modelo);
        }
        $archivosVoucher = [];
        if ($electronico) {
            $ruta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'venta-voucher-' . bin2hex(random_bytes(8)) . '.png';
            file_put_contents($ruta, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
            $this->archivosPropios[] = $ruta;
            $foto = $this->getMockBuilder(UploadedFile::class)->disableOriginalConstructor()->onlyMethods(['isValid', 'hasMoved', 'getTempName', 'move'])->getMock();
            $foto->method('isValid')->willReturn(true);
            $foto->method('hasMoved')->willReturn(false);
            $foto->method('getTempName')->willReturn($ruta);
            $foto->method('move')->willReturnCallback(fn($destino, $nombre) => copy($ruta, $destino . '/' . $nombre));
            $archivosVoucher[0] = $foto;
        }
        $venta = $this->getMockBuilder(VentaModel::class)->setConstructorArgs([$db])->onlyMethods(['almacenVouchers'])->getMock();
        $venta->method('almacenVouchers')->willReturn($storage);
        $ok = $venta->registrarVenta([
            'request_token' => str_repeat('a', 64), 'idcliente' => 1, 'idlocal' => $local,
            'idlinea' => 1, 'idcanal' => 1, 'idestado' => 1, 'idvendedor' => 7, 'idcajero' => 7,
            'tipodocumento' => 'NOTA_VENTA', 'formapago' => 'CONTADO',
            'idapertura' => $escenario === 'sin_caja' ? 0 : 9,
        ], [
            ['idproducto' => 1, 'cantidad' => '1', 'precioventa' => '100.00', 'costounitario' => '0.01'],
            ['idproducto' => 4, 'cantidad' => '2', 'precioventa' => '300.00'],
            ['idproducto' => 3, 'cantidad' => '1', 'precioventa' => '40.00'],
        ], [['idtipopago' => $electronico ? 2 : 1, 'monto' => '740.00', 'idcuenta' => $electronico ? 11 : null, 'referencia' => $electronico ? 'OP123' : '']],
            ['tipoentrega' => 'RECOJO_TIENDA'], $archivosVoucher);
        return [$ok, $estado];
    }

    public function testRegistroGuardaCostoDelLocalYComponentesNoCostoDelCatalogo(): void
    {
        [$ok, $estado] = $this->registrar();
        $this->assertTrue($ok, (string) session()->getFlashdata('error'));
        $this->assertSame(['10.12', '38.96', '15.00'], array_column($estado->detalles, 'costounitario'));
        $this->assertSame([1, 2], $estado->kardex);
        $this->assertSame([5.0, 2.5], array_column($estado->inserciones['reservas_stock'], 'cantidad'));
        $this->assertSame(1, $estado->commit);
        $this->assertSame(0, $estado->rollback);
        $this->assertSame(9, $estado->pagos[0]['idapertura']);
        $this->assertSame(7, $estado->pagos[0]['idusuariocobro']);
        $this->assertNull($estado->pagos[0]['idcuenta']);
        $this->assertNull($estado->pagos[0]['voucher']);
        $this->assertArrayNotHasKey('idapertura', $estado->inserciones['ventas'][0]);
    }

    public function testOtroLocalGuardaSuPropioCosto(): void
    {
        [$ok, $estado] = $this->registrar(5);
        $this->assertTrue($ok, (string) session()->getFlashdata('error'));
        $this->assertSame(['20.46', '60.91', '15.00'], array_column($estado->detalles, 'costounitario'));
    }

    public function testVentaCongelaGarantiasIndividualesYElModeloPermiteGuardarlas(): void
    {
        [$ok, $estado] = $this->registrar();
        $this->assertTrue($ok);
        $this->assertSame([12, 1, 0], array_column($estado->detalles, 'garantiameses'));
        $this->assertSame(['Cámara', 'Kit', 'Instalación'], array_column($estado->detalles, 'nombre_snapshot'));
        $componentes = $estado->inserciones['venta_combo_componentes'];
        $this->assertSame([12, 0, 0], array_column($componentes, 'garantiameses'));
        $this->assertSame(['PRODUCTO', 'PRODUCTO', 'SERVICIO'], array_column($componentes, 'tipo_snapshot'));
        $modelo = new DetVentaModel();
        $permitidos = (new ReflectionProperty(DetVentaModel::class, 'allowedFields'))->getValue($modelo);
        foreach (['garantiameses', 'nombre_snapshot', 'tipo_snapshot', 'controlaserie_snapshot'] as $campo) {
            $this->assertContains($campo, $permitidos);
        }
    }

    public function testCostoInvalidoRevierteAntesDeEscribirLaVenta(): void
    {
        [$ok, $estado] = $this->registrar(2, 'costo_invalido');
        $this->assertFalse($ok);
        $this->assertSame([], $estado->detalles);
        $this->assertSame([], $estado->inserciones);
        $this->assertSame(1, $estado->rollback);
        $this->assertSame(0, $estado->commit);
    }

    public function testReintentoNoRecalculaNiReescribeVentaExistente(): void
    {
        [$ok, $estado] = $this->registrar(2, 'reintento');
        $this->assertTrue($ok);
        $this->assertSame([], $estado->kardex);
        $this->assertSame([], $estado->detalles);
        $this->assertSame([], $estado->inserciones);
        $this->assertSame(0, $estado->commit);
    }

    public function testRegistroElectronicoGuardaAperturaCuentaYVoucherPrivado(): void
    {
        [$ok, $estado] = $this->registrar(2, 'electronico');
        $this->assertTrue($ok, (string) session()->getFlashdata('error'));
        $pago = $estado->pagos[0];
        $this->assertSame(11, $pago['idcuenta']);
        $this->assertSame(9, $pago['idapertura']);
        $this->assertSame('OP123', $pago['referencia']);
        $this->assertFileExists($estado->storage->ruta($pago['voucher']));
    }

    public function testFalloAlGuardarPagoEliminaLaFotoRecienSubida(): void
    {
        [$ok, $estado] = $this->registrar(2, 'fallo_pago');
        $this->assertFalse($ok);
        $this->assertSame(0, $estado->commit);
        $this->assertSame(1, $estado->rollback);
        $this->assertFileDoesNotExist($estado->storage->ruta($estado->pagos[0]['voucher']));
    }

    public function testVentaSinAperturaNoEscribeNiReservaInventario(): void
    {
        [$ok, $estado] = $this->registrar(2, 'sin_caja');
        $this->assertFalse($ok);
        $this->assertSame([], $estado->inserciones);
        $this->assertSame([], $estado->kardex);
    }

    public function testVentaRechazaUnaAperturaCerradaDesdeOtraPestana(): void
    {
        [$ok, $estado] = $this->registrar(2, 'cerrada');
        $this->assertFalse($ok);
        $this->assertSame([], $estado->inserciones);
        $this->assertSame([], $estado->kardex);
    }
}
