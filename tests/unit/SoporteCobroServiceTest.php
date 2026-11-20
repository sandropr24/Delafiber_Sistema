<?php

use App\Services\SoporteCobroService;
use App\Services\VoucherStorage;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SoporteCobroServiceTest extends CIUnitTestCase
{
    private array $bloqueos = [];

    private function servicio(array $cuentas = [], ?VoucherStorage $storage = null, bool $activo = true): SoporteCobroService
    {
        $this->bloqueos = [];
        $storage ??= $this->createMock(VoucherStorage::class);
        $storage->expects($this->never())->method('guardar');
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['table', 'query', 'connect'])->getMock();
        $db->DBDriver = 'MySQLi';
        $db->expects($this->never())->method('connect');
        $db->method('table')->willReturnCallback(function ($tabla) use ($activo) {
            $this->assertSame('tipospago', $tabla);
            $filtros = [];
            $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods(['where', 'get'])->getMock();
            $builder->method('where')->willReturnCallback(function ($campo, $valor) use (&$filtros, $builder) {
                $filtros[$campo] = $valor;
                return $builder;
            });
            $builder->method('get')->willReturnCallback(function () use (&$filtros, $activo) {
                $this->assertSame(1, $filtros['estado']);
                $resultado = $this->createMock(ResultInterface::class);
                $nombre = $activo ? ([1 => 'Efectivo', 2 => 'Yape', 3 => 'Transferencia Bancaria'][$filtros['idtipopago']] ?? null) : null;
                $resultado->method('getRow')->willReturn($nombre);
                return $resultado;
            });
            return $builder;
        });
        $db->method('query')->willReturnCallback(function ($sql, $parametros) use ($cuentas) {
            $this->assertSame('SELECT * FROM cuentasbancarias WHERE idcuenta = ? FOR UPDATE', $sql);
            $this->bloqueos[] = $parametros[0];
            $resultado = $this->createMock(ResultInterface::class);
            $resultado->method('getRowArray')->willReturn($cuentas[$parametros[0]] ?? null);
            return $resultado;
        });
        return new SoporteCobroService($db, $storage);
    }

    private function cuenta(array $cambios = []): array
    {
        return array_replace(['idcuenta' => 11, 'estado' => 1, 'moneda' => 'PEN'], $cambios);
    }

    private function pago(array $cambios = []): array
    {
        return array_replace(['idtipopago' => 2, 'monto' => '15.00', 'idcuenta' => 11, 'referencia' => 'OP123'], $cambios);
    }

    public function testEfectivoDescartaRutaDeVoucherDelClienteYNoNecesitaCuenta(): void
    {
        $filas = $this->servicio()->preparar([
            ['idtipopago' => 1, 'monto' => '30.00', 'montorecibido' => '50.00', 'voucher' => '../../otro.jpg'],
        ], [], '30.00');
        $this->assertSame('20.00', $filas[0]['vuelto']);
        $this->assertNull($filas[0]['archivo']);
        $this->assertNull($filas[0]['idcuenta']);
        $this->assertArrayNotHasKey('voucher', $filas[0]);
    }

    public function testPagoElectronicoValidaCuentaYFotoSinGuardarHastaConfirmar(): void
    {
        $foto = $this->createMock(UploadedFile::class);
        $storage = $this->createMock(VoucherStorage::class);
        $storage->expects($this->once())->method('validar')->with($foto)->willReturn('jpg');
        $filas = $this->servicio([11 => $this->cuenta()], $storage)->preparar([5 => $this->pago()], [5 => $foto], '15.00');
        $this->assertSame(11, $filas[5]['idcuenta']);
        $this->assertSame($foto, $filas[5]['archivo']);
        $this->assertSame('OP123', $filas[5]['referencia']);
    }

    #[DataProvider('cuentasNoAdmitidas')]
    public function testRechazaCuentaInexistenteInactivaOEnDolares(?array $cuenta): void
    {
        $servicio = $this->servicio([11 => $cuenta]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('activa y en soles');
        $servicio->preparar([$this->pago()], [], '15.00');
    }

    public static function cuentasNoAdmitidas(): array
    {
        return [[null], [['idcuenta' => 11, 'estado' => 0, 'moneda' => 'PEN']], [['idcuenta' => 11, 'estado' => 1, 'moneda' => 'USD']]];
    }

    public function testNoAceptaUnaRutaComoSustitutoDeUnaFotoSubida(): void
    {
        $servicio = $this->servicio([11 => $this->cuenta()]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('voucher');
        $servicio->preparar([$this->pago(['voucher' => 'foto.jpg'])], [0 => 'foto.jpg'], '15.00');
    }

    public function testReferenciaEsObligatoriaYNoPuedeSerUnArray(): void
    {
        $servicio = $this->servicio([11 => $this->cuenta()]);
        $this->expectException(InvalidArgumentException::class);
        $servicio->preparar([$this->pago(['referencia' => []])], [], '15.00');
    }

    public function testRechazaMedioDesactivado(): void
    {
        $servicio = $this->servicio([], null, false);
        $this->expectException(InvalidArgumentException::class);
        $servicio->preparar([['idtipopago' => 1, 'monto' => '15.00']], [], '15.00');
    }

    public function testPagoMixtoBloqueaCuentasEnOrdenAunqueElFormularioLasInvierta(): void
    {
        $foto = $this->createMock(UploadedFile::class);
        $storage = $this->createMock(VoucherStorage::class);
        $storage->method('validar')->willReturn('jpg');
        $servicio = $this->servicio([11 => $this->cuenta(), 20 => $this->cuenta(['idcuenta' => 20])], $storage);
        $filas = $servicio->preparar([5 => $this->pago(['idcuenta' => 20]), 8 => $this->pago(['idtipopago' => 3])], [5 => $foto, 8 => $foto], '30.00');
        $this->assertSame([11, 20], $this->bloqueos);
        $this->assertCount(2, $filas);
    }

    public function testNoPermitePagosQueNoCoincidanConLaVenta(): void
    {
        $servicio = $this->servicio();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('coincidir');
        $servicio->preparar([['idtipopago' => 1, 'monto' => '15.00']], [], '20.00');
    }

    public function testAdelantoPuedeSerMenorPeroNoMayorAlTotal(): void
    {
        $filas = $this->servicio()->preparar([['idtipopago' => 1, 'monto' => '15.00']], [], '20.00', false);
        $this->assertSame('15.00', $filas[0]['monto']);

        $this->expectException(InvalidArgumentException::class);
        $this->servicio()->preparar([['idtipopago' => 1, 'monto' => '20.01']], [], '20.00', false);
    }
}
