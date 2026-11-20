<?php

use App\Controllers\Admin\VentaAnulacionController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use PHPUnit\Framework\Attributes\DataProvider;

final class VentaAnulacionRedirectTest extends CIUnitTestCase
{
    private array $conexiones;

    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
        $this->conexiones = Database::getConnections();
        session()->set('idusuario', 7);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, $this->conexiones);
        session()->remove('idusuario');
        parent::tearDown();
    }

    #[DataProvider('destinos')]
    public function testRedirigeSiempreDentroDeAdmin(string $accion, string $escenario, string $destino): void
    {
        $request = $this->getMockBuilder(IncomingRequest::class)->disableOriginalConstructor()->onlyMethods(['getPost'])->getMock();
        $post = $escenario === 'invalido' ? [] : [
            'motivo' => 'Motivo suficientemente largo para solicitar la anulación.',
            'decision' => 'AUTORIZADA',
            'observacion' => 'Observación válida de revisión.',
            'inventario_verificado' => '1',
            'observacion_inventario' => 'Se verificó el inventario de esta venta.',
        ];
        $request->method('getPost')->willReturnCallback(fn($clave) => $post[$clave] ?? null);
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'table', 'query', 'connect', 'transBegin', 'transRollback',
        ])->getMock();
        $db->expects($this->never())->method('connect');
        $db->method('transBegin')->willReturn(true);
        $db->method('transRollback')->willReturn(true);
        $db->method('query')->willReturnCallback(function () {
            throw new RuntimeException('Fallo simulado después de identificar la venta.');
        });
        $db->method('table')->willReturnCallback(function ($tabla) use ($escenario) {
            $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods(['select', 'join', 'where', 'get'])->getMock();
            foreach (['select', 'join', 'where'] as $metodo) $builder->method($metodo)->willReturnSelf();
            $resultado = $this->createMock(ResultInterface::class);
            $fila = $tabla === 'usuarios u'
                ? ['rol' => $escenario === 'sin_permiso' ? 'Vendedor' : 'Administrador']
                : ($escenario === 'con_venta' ? ['idventa' => 42] : null);
            $resultado->method('getRowArray')->willReturn($fila);
            $builder->method('get')->willReturn($resultado);
            return $builder;
        });
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, ['tests' => $db]);
        $controller = new VentaAnulacionController();
        $controller->initController($request, service('response'), service('logger'));
        $response = $controller->{$accion}(42);
        $this->assertSame(site_url($destino), $response->getHeaderLine('Location'));
        $this->assertStringNotContainsString('/admin/admin/', $response->getHeaderLine('Location'));
    }

    public static function destinos(): array
    {
        return [
            ['solicitar', 'invalido', 'admin/ventas/detalles/42'],
            ['solicitar', 'con_venta', 'admin/ventas/detalles/42'],
            ['revisar', 'sin_permiso', 'admin/ventas'],
            ['revisar', 'invalido', 'admin/ventas'],
            ['revisar', 'sin_venta', 'admin/ventas'],
            ['revisar', 'con_venta', 'admin/ventas/detalles/42'],
            ['ejecutar', 'sin_permiso', 'admin/ventas'],
            ['ejecutar', 'invalido', 'admin/ventas'],
            ['ejecutar', 'sin_venta', 'admin/ventas'],
            ['ejecutar', 'con_venta', 'admin/ventas/detalles/42'],
        ];
    }
}
