<?php

use App\Services\GarantiaWorkflow;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class GarantiaViewsTest extends CIUnitTestCase
{
    private array $conexiones;

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
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
        session()->remove(['idusuario', 'rol', '_ci_old_input']);
        parent::tearDown();
    }

    private function articulo(): array
    {
        return [
            'iddetventa' => 20, 'idcomponenteventa' => 55, 'idserie' => 40,
            'garantiameses' => 12, 'producto' => '<Cámara>', 'cliente' => '<Cliente>',
            'numerodoc' => '12345678', 'serie' => 'NV01', 'numero' => '00000001', 'fecha' => date('Y-m-d'),
            'numeroserie' => '<CAM-01>', 'vencimiento' => date('Y-m-d', strtotime('+1 year')), 'aviso' => '',
        ];
    }

    public function testRecepcionEscapaDatosYContieneCsrfYSeleccionExacta(): void
    {
        $html = view('garantias/crear', ['buscar' => '12345678', 'venta' => 0, 'articulos' => [$this->articulo()], 'token' => str_repeat('a', 64), 'esAdmin' => true], ['saveData' => false]);
        $this->assertStringContainsString('&lt;Cámara&gt;', $html);
        $this->assertStringNotContainsString('<Cámara>', $html);
        $this->assertStringContainsString('csrf_test_name', $html);
        $this->assertStringContainsString('name="idcomponenteventa" value="55"', $html);
        $this->assertStringContainsString('name="idserie" value="40"', $html);
        $this->assertStringContainsString('name="falla"', $html);
    }

    public function testHistoricoSoloOfreceFormularioDeVerificacionAlAdministrador(): void
    {
        $articulo = $this->articulo();
        $articulo['garantiameses'] = null;
        $articulo['aviso'] = 'Plazo histórico: requiere verificación de administrador';
        $datos = ['buscar' => '12345678', 'venta' => 0, 'articulos' => [$articulo], 'token' => str_repeat('a', 64), 'esAdmin' => true];
        $html = view('garantias/crear', $datos, ['saveData' => false]);
        $this->assertStringContainsString('name="meses_verificados"', $html);
        $datos['esAdmin'] = false;
        $html = view('garantias/crear', $datos, ['saveData' => false]);
        $this->assertStringNotContainsString('name="meses_verificados"', $html);
        $this->assertStringNotContainsString('admin/garantias/guardar', $html);
    }

    public function testListadoVacioNoOfreceRegistroAlVendedor(): void
    {
        $html = view('garantias/index', [
            'buscar' => '<script>', 'estado' => '', 'resultado' => ['items' => [], 'total' => 0, 'paginas' => 1, 'pagina' => 1],
            'etiquetas' => GarantiaWorkflow::ETIQUETAS, 'puedeGestionar' => false,
        ], ['saveData' => false]);
        $this->assertStringContainsString('No hay casos', $html);
        $this->assertStringNotContainsString('admin/garantias/crear', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testDetalleMuestraHistorialYNoPermiteEditarUnCasoCerrado(): void
    {
        $caso = [
            'idgarantia' => 1, 'estado' => 'ENTREGADO_CLIENTE', 'cliente' => '<Cliente>', 'numerodoc' => '12345678',
            'serie' => 'NV01', 'numero' => '00000001', 'fechaventa' => date('Y-m-d'), 'producto' => 'Cámara',
            'idcomponenteventa' => 55, 'numeroserie' => 'CAM-01', 'garantiameses' => 12,
            'fechavencimiento' => date('Y-m-d', strtotime('+1 year')), 'plazo_verificado' => 0,
            'fechaingreso' => date('Y-m-d H:i:s'), 'responsable' => 'Admin', 'proveedor' => null,
            'falla' => '<No enciende>', 'observacion' => null,
        ];
        $datos = [
            'caso' => $caso, 'historial' => [['fecha' => date('Y-m-d H:i:s'), 'estado' => 'RECIBIDO', 'nombreusuario' => 'Admin', 'proveedor' => null, 'observacion' => '<Falla>']],
            'etiquetas' => GarantiaWorkflow::ETIQUETAS, 'siguientes' => [], 'proveedores' => [], 'puedeGestionar' => true,
        ];
        $html = view('garantias/detalles', $datos, ['saveData' => false]);
        $this->assertStringContainsString('Caso cerrado', $html);
        $this->assertStringContainsString('&lt;Falla&gt;', $html);
        $this->assertStringNotContainsString('admin/garantias/cambiar-estado/', $html);
        $datos['caso']['estado'] = 'RECIBIDO';
        $datos['siguientes'] = ['ENVIADO_PROVEEDOR', 'REPARADO', 'REEMPLAZADO'];
        $html = view('garantias/detalles', $datos, ['saveData' => false]);
        $this->assertStringContainsString('name="estado_actual" value="RECIBIDO"', $html);
        $this->assertStringContainsString('csrf_test_name', $html);
        $this->assertStringNotContainsString('<option value="ENTREGADO_CLIENTE"', $html);
    }
}
