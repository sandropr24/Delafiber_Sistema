<?php

use App\Services\GarantiaWorkflow;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class GarantiaWorkflowTest extends CIUnitTestCase
{
    public static function vencimientos(): array
    {
        return [
            ['2024-01-31 14:00:00', 1, '2024-02-29'],
            ['2025-01-31', 1, '2025-02-28'],
            ['2024-02-29', 12, '2025-02-28'],
            ['2026-11-17', 6, '2027-05-17'],
        ];
    }

    #[DataProvider('vencimientos')]
    public function testVencimientoRespetaFinDeMes(string $fecha, int $meses, string $esperado): void
    {
        $this->assertSame($esperado, (new GarantiaWorkflow())->vencimiento($fecha, $meses));
    }

    public function testElDiaDeVencimientoTodaviaEstaCubierto(): void
    {
        $this->assertSame('2026-11-17', (new GarantiaWorkflow())->validarVigencia('2025-11-17', 12, '2026-11-17'));
    }

    public static function casosInvalidos(): array
    {
        return [
            ['2025-11-17', 12, '2026-11-18'],
            ['2026-11-18', 12, '2026-11-17'],
            ['2026-02-30', 12, '2026-11-17'],
            ['2026-11-17', 0, '2026-11-17'],
            ['2026-11-17', 121, '2026-11-17'],
        ];
    }

    #[DataProvider('casosInvalidos')]
    public function testRechazaPlazosYFechasInvalidos(string $fecha, int $meses, string $hoy): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GarantiaWorkflow())->validarVigencia($fecha, $meses, $hoy);
    }

    public static function transiciones(): array
    {
        return [
            ['RECIBIDO', 'ENVIADO_PROVEEDOR'], ['RECIBIDO', 'REPARADO'], ['RECIBIDO', 'REEMPLAZADO'],
            ['ENVIADO_PROVEEDOR', 'REPARADO'], ['ENVIADO_PROVEEDOR', 'REEMPLAZADO'],
            ['REPARADO', 'ENTREGADO_CLIENTE'], ['REEMPLAZADO', 'ENTREGADO_CLIENTE'],
        ];
    }

    #[DataProvider('transiciones')]
    public function testTransicionesValidas(string $actual, string $nuevo): void
    {
        (new GarantiaWorkflow())->validarTransicion($actual, $nuevo, 'Trabajo documentado.');
        $this->assertContains($nuevo, (new GarantiaWorkflow())->siguientes($actual));
    }

    public static function transicionesInvalidas(): array
    {
        return [
            ['RECIBIDO', 'ENTREGADO_CLIENTE', 'Entrega sin resolución'],
            ['REPARADO', 'RECIBIDO', 'Retroceso de estado'],
            ['ENTREGADO_CLIENTE', 'RECIBIDO', 'Reabrir caso cerrado'],
            ['ENVIADO_PROVEEDOR', 'ENVIADO_PROVEEDOR', 'Doble solicitud'],
            ['RECIBIDO', 'REPARADO', ''],
            ['RECIBIDO', 'REPARADO', '   x   '],
        ];
    }

    #[DataProvider('transicionesInvalidas')]
    public function testNoPermiteSaltosNiObservacionesVacias(string $actual, string $nuevo, string $nota): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GarantiaWorkflow())->validarTransicion($actual, $nuevo, $nota);
    }
}
