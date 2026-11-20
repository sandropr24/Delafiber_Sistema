<?php

use App\Libraries\ArqueoCaja;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ArqueoCajaTest extends CIUnitTestCase
{
    private function medios(): array
    {
        return [['idtipopago' => 1, 'tipopago' => 'Efectivo'], ['idtipopago' => 2, 'tipopago' => 'Yape']];
    }

    public function testSumaMontoAplicadoNoRecibidoYElInicialSoloVaAEfectivo(): void
    {
        $filas = ArqueoCaja::calcular($this->medios(), [
            ['idtipopago' => 1, 'monto' => '30.00', 'montorecibido' => '50.00', 'vuelto' => '20.00'],
            ['idtipopago' => 2, 'monto' => '12.30'],
            ['idtipopago' => 2, 'monto' => '0.10'],
        ], '100.00');
        $this->assertSame('130.00', $filas[1]['montoesperado']);
        $this->assertSame('12.40', $filas[2]['montoesperado']);
    }

    public function testAnularVentaNoDescuentaDineroSinReembolso(): void
    {
        $filas = ArqueoCaja::calcular($this->medios(), [
            ['idtipopago' => 1, 'monto' => '50.00', 'fechaanulacion' => '2026-11-17 10:00:00'],
        ], '0.00');
        $this->assertSame('50.00', $filas[1]['montoesperado']);
    }

    public function testSumaIngresosYRestaEgresosPorMedio(): void
    {
        $filas = ArqueoCaja::calcular($this->medios(), [['idtipopago' => 1, 'monto' => '50.00']], '100.00', [
            ['idtipopago' => 1, 'tipo' => 'INGRESO', 'monto' => '20.00'],
            ['idtipopago' => 1, 'tipo' => 'EGRESO', 'monto' => '15.50'],
            ['idtipopago' => 2, 'tipo' => 'INGRESO', 'monto' => '10.00'],
        ]);
        $this->assertSame('154.50', $filas[1]['montoesperado']);
        $this->assertSame('10.00', $filas[2]['montoesperado']);
    }

    public function testEgresoNoPuedeDejarSaldoEsperadoNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ArqueoCaja::calcular($this->medios(), [], '5.00', [
            ['idtipopago' => 1, 'tipo' => 'EGRESO', 'monto' => '5.01'],
        ]);
    }

    public function testCierreExactoPermiteObservacionVaciaYNoAceptaEsperadoDelCliente(): void
    {
        $esperados = ArqueoCaja::calcular($this->medios(), [], '100.00');
        $filas = ArqueoCaja::cierre($esperados, [1 => '100.00', 2 => '0.00', 'montoesperado' => '999.00'], '');
        $this->assertSame('100.00', $filas[0]['montoesperado']);
        $this->assertSame('100.00', $filas[0]['montocontado']);
        $this->assertNull($filas[0]['observacion']);
        $this->assertArrayNotHasKey('diferencia', $filas[0]); // La columna SQL es generada.
    }

    public function testConDiferenciasExigeExplicacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('diferencias');
        ArqueoCaja::cierre(ArqueoCaja::calcular($this->medios(), [], '10.00'), [1 => '9.00', 2 => '0.00'], '');
    }

    public function testRegistraDiferenciaPositivaONegativaSinAjustarLoContado(): void
    {
        $filas = ArqueoCaja::cierre(ArqueoCaja::calcular($this->medios(), [], '10.00'),
            [1 => '9.00', 2 => '1.50'], 'Diferencia verificada');
        $this->assertSame('9.00', $filas[0]['montocontado']);
        $this->assertSame('1.50', $filas[1]['montocontado']);
    }

    #[DataProvider('contadosInvalidos')]
    public function testRechazaArqueoIncompletoONumerosInvalidos(array $contados): void
    {
        $this->expectException(InvalidArgumentException::class);
        ArqueoCaja::cierre(ArqueoCaja::calcular($this->medios(), [], '0.00'), $contados, 'Explicación válida');
    }

    public static function contadosInvalidos(): array
    {
        return [[[]], [[1 => '0.00']], [[1 => '-1', 2 => '0']], [[1 => ['0'], 2 => '0']], [[1 => '0.001', 2 => '0']]];
    }

    public function testNoTruncaUnTotalMayorAlRangoDeLaColumna(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ArqueoCaja::calcular($this->medios(), [['idtipopago' => 1, 'monto' => '1.00']], '99999999.99');
    }
}
