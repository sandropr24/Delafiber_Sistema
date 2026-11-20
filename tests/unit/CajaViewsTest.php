<?php

use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class CajaViewsTest extends CIUnitTestCase
{
    private array $conexiones;

    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
        $this->conexiones = Database::getConnections();
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['tableExists', 'connect', 'query'])->getMock();
        $db->method('tableExists')->willReturn(false);
        $db->expects($this->never())->method('connect');
        $db->expects($this->never())->method('query');
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, ['tests' => $db]);
        session()->set(['idusuario' => 7, 'rol' => 'Administrador']);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, $this->conexiones);
        session()->remove(['idusuario', 'rol']);
        parent::tearDown();
    }

    public function testPantallaInicialYConfiguracionMuestranFormulariosProtegidos(): void
    {
        $html = view('caja/index', ['esAdmin' => true, 'propias' => [], 'cajas' => [], 'aperturas' => []], ['saveData' => false]);
        $this->assertStringContainsString('admin/caja/abrir', $html);
        $this->assertStringContainsString('csrf_test_name', $html);
        $html = view('caja/configuracion', ['locales' => [], 'cajas' => [], 'cuentas' => [], 'medios' => []], ['saveData' => false]);
        $this->assertStringContainsString('admin/caja/completar-medios', $html);
        $this->assertStringContainsString('admin/caja/crear-cuenta', $html);
    }

    public function testArqueoAbiertoExigeContadosYElCerradoMuestraElRegistro(): void
    {
        $datos = [
            'apertura' => [
                'idapertura' => 9, 'estado' => 'ABIERTA', 'nombrelocal' => 'Tienda', 'caja' => '<Caja>',
                'nombreusuario' => 'Usuario', 'fechaapertura' => '2026-11-17 09:00:00',
                'montoinicial' => '100.00', 'fechacierre' => null, 'observacion' => null,
            ],
            'pagos' => [], 'puedeCerrar' => true,
            'arqueo' => [['idtipopago' => 1, 'tipopago' => 'Efectivo', 'montoesperado' => '100.00']],
        ];
        $html = view('caja/detalles', $datos, ['saveData' => false]);
        $this->assertStringContainsString('contados[1]', $html);
        $this->assertStringContainsString('&lt;Caja&gt;', $html);
        $this->assertStringNotContainsString('<Caja>', $html);
        $datos['apertura']['estado'] = 'CERRADA';
        $datos['puedeCerrar'] = false;
        $datos['arqueo'][0] += ['montocontado' => '99.00', 'diferencia' => '-1.00'];
        $html = view('caja/detalles', $datos, ['saveData' => false]);
        $this->assertStringNotContainsString('name="contados[1]"', $html);
        $this->assertStringContainsString('S/ -1.00', $html);
    }
}
