<?php

use App\Controllers\Admin\Dashboard;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\View\View;
use Config\Database;
use Config\Services;

final class DashboardVentasTest extends CIUnitTestCase
{
    private array $conexiones;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conexiones = Database::getConnections();
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, $this->conexiones);
        Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testTodosLosBloquesDeVentasExcluyenAnulacionesYConservanSusFiltros(): void
    {
        $hoy = date('Y-m-d');
        $consultas = [
            ['ventas', 'fechaanulacion', ['monto_hoy' => '120.50']],
            ['kardex', null, []],
            ['productos', null, []],
            ['cotizacion', null, []],
            ['entregas', null, []],
            ['ventas', 'ventas.fechaanulacion', [['idventa' => 7]]],
            ['detventa dv', 'v.fechaanulacion', [['idproducto' => 1, 'unidades' => 2]]],
            ['ventas', 'fechaanulacion', [['dia' => $hoy, 'monto' => '120.50']]],
        ];
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['table', 'query', 'connect'])->getMock();
        $db->expects($this->never())->method('query');
        $db->expects($this->never())->method('connect');
        $indice = 0;
        $db->expects($this->exactly(count($consultas)))->method('table')->willReturnCallback(
            function ($tabla) use (&$indice, $consultas, $hoy) {
                $n = $indice++;
                [$esperada, $filtroAnulacion, $datos] = $consultas[$n];
                $this->assertSame($esperada, $tabla);
                $condiciones = [];
                $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                    'select', 'selectSum', 'where', 'whereIn', 'join', 'groupBy', 'orderBy', 'limit', 'get', 'countAllResults',
                ])->getMock();
                foreach (['select', 'selectSum', 'whereIn', 'join', 'groupBy', 'orderBy', 'limit'] as $metodo) {
                    $builder->method($metodo)->willReturnSelf();
                }
                $builder->method('where')->willReturnCallback(function ($clave, $valor = null) use (&$condiciones, $builder) {
                    $condiciones[$clave] = $valor;
                    return $builder;
                });
                $builder->method('countAllResults')->willReturn(0);
                $builder->method('get')->willReturnCallback(function () use (&$condiciones, $filtroAnulacion, $datos, $n, $hoy) {
                    $this->assertArrayHasKey($filtroAnulacion, $condiciones);
                    $this->assertNull($condiciones[$filtroAnulacion]);
                    if (in_array($n, [0, 7], true)) {
                        $this->assertSame(($n === 0 ? $hoy : date('Y-m-d', strtotime('-6 days'))) . ' 00:00:00', $condiciones['fecha >=']);
                        $this->assertSame(date('Y-m-d', strtotime('+1 day')) . ' 00:00:00', $condiciones['fecha <']);
                    }
                    if ($n === 6) $this->assertSame('PRODUCTO', $condiciones['p.tipo']);
                    $resultado = $this->createMock(ResultInterface::class);
                    $resultado->method('getRowArray')->willReturn($datos);
                    $resultado->method('getResultArray')->willReturn($datos);
                    return $resultado;
                });
                return $builder;
            }
        );
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, ['tests' => $db]);
        $renderer = $this->getMockBuilder(View::class)->disableOriginalConstructor()->onlyMethods(['setData', 'render'])->getMock();
        $renderer->expects($this->once())->method('setData')->willReturnCallback(function ($datos) use ($renderer) {
            $this->assertSame(120.5, $datos['totalVentasHoy']);
            $this->assertSame([['idventa' => 7]], $datos['ultimasVentas']);
            $this->assertCount(7, $datos['graficoMontos']);
            $this->assertSame([0, 0, 0, 0, 0, 0, 120.5], $datos['graficoMontos']);
            return $renderer;
        });
        $renderer->expects($this->once())->method('render')->with('dashboard/index')->willReturn('dashboard comprobado');
        Services::injectMock('renderer', $renderer);
        $this->assertSame('dashboard comprobado', (new Dashboard())->index());
    }
}
