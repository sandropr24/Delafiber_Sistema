<?php

use App\Services\ServicioWorkflow;
use CodeIgniter\Test\CIUnitTestCase;

final class ServicioWorkflowTest extends CIUnitTestCase
{
    private ServicioWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = new ServicioWorkflow();
    }

    public function testPermiteFlujoOperativoCompleto(): void
    {
        $this->workflow->validarTransicion('PROGRAMADO', 'EN_PROCESO', '');
        $this->workflow->validarTransicion('EN_PROCESO', 'FINALIZADO', 'Trabajo realizado correctamente');

        $this->assertTrue(true);
    }

    public function testRechazaSaltarDirectamenteAFinalizado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no está permitido');

        $this->workflow->validarTransicion('PROGRAMADO', 'FINALIZADO', 'Trabajo realizado');
    }

    public function testFinalizacionExigeResultadoTecnico(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Describe el trabajo realizado');

        $this->workflow->validarTransicion('EN_PROCESO', 'FINALIZADO', 'No');
    }

    public function testCancelacionExigeMotivo(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('motivo de cancelación');

        $this->workflow->validarTransicion('PENDIENTE', 'CANCELADO', '');
    }

    public function testExponeCatalogoUnicoDeEstados(): void
    {
        $this->assertSame(['PENDIENTE', 'PROGRAMADO', 'EN_PROCESO', 'FINALIZADO', 'CANCELADO'], ServicioWorkflow::ESTADOS);
        $this->assertSame('En proceso', ServicioWorkflow::ETIQUETAS[ServicioWorkflow::EN_PROCESO]);
    }
}
