<?php

use App\Services\CronogramaCuotasService;
use CodeIgniter\Test\CIUnitTestCase;

final class CronogramaCuotasServiceTest extends CIUnitTestCase
{
    private CronogramaCuotasService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new CronogramaCuotasService();
    }

    public function testGeneraCuotasSobreElSaldoDespuesDelPagoInicial(): void
    {
        $cuotas = $this->servicio->generar('650.00', '450.00', '2', 'MENSUAL', '2026-12-15', '2026-11-17');

        $this->assertSame(['100.00', '100.00'], array_column($cuotas, 'monto'));
        $this->assertSame(['2026-12-15', '2027-01-15'], array_column($cuotas, 'fechavencimiento'));
        $this->assertSame([1, 2], array_column($cuotas, 'nrocuota'));
    }

    public function testDistribuyeCentimosSinAlterarElSaldo(): void
    {
        $cuotas = $this->servicio->generar('200.00', '0.00', '3', 'QUINCENAL', '2026-11-30', '2026-11-17');

        $this->assertSame(['66.67', '66.67', '66.66'], array_column($cuotas, 'monto'));
        $this->assertSame(20000, array_sum(array_map(static fn($cuota) => (int) round((float) $cuota['monto'] * 100), $cuotas)));
        $this->assertSame(['2026-11-30', '2026-12-15', '2026-12-30'], array_column($cuotas, 'fechavencimiento'));
    }

    public function testMantieneElFinDeMesEnFrecuenciaMensual(): void
    {
        $cuotas = $this->servicio->generar('30.00', '0.00', '3', 'MENSUAL', '2027-01-31', '2026-11-17');

        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], array_column($cuotas, 'fechavencimiento'));
    }

    public function testRechazaPlanesInconsistentes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->generar('100.00', '100.00', '2', 'MENSUAL', '2026-12-01', '2026-11-17');
    }
}
