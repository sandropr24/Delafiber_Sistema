<?php

use App\Libraries\FiltrosTienda;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FiltrosTiendaTest extends CIUnitTestCase
{
    public function testNormalizaLosFiltrosSinAceptarCamposInternos(): void
    {
        $filtros = FiltrosTienda::normalizar([
            'q' => '  Router  ', 'tipo' => 'PRODUCTO', 'categoria' => '2', 'marca' => '3',
            'precio_min' => '12,50', 'precio_max' => '100', 'orden' => 'precio_desc',
            'preciocosto' => 1, 'estado' => 0,
        ]);
        $this->assertSame([
            'q' => 'Router', 'tipo' => 'PRODUCTO', 'categoria' => 2, 'marca' => 3,
            'precio_min' => '12.50', 'precio_max' => '100.00', 'orden' => 'precio_desc', 'avisos' => [],
        ], $filtros);
    }

    #[DataProvider('preciosInvalidos')]
    public function testIgnoraPreciosInvalidosYAdvierte($precio): void
    {
        $filtros = FiltrosTienda::normalizar(['precio_min' => $precio]);
        $this->assertSame('', $filtros['precio_min']);
        $this->assertNotEmpty($filtros['avisos']);
    }

    public static function preciosInvalidos(): array
    {
        return [[-1], ['1e3'], ['1.001'], ['100000000'], ['NaN'], ['1 OR 1=1'], [['1']], [true]];
    }

    public function testPrecioCeroEsUnFiltroValidoYElRangoEsInclusivo(): void
    {
        $filtros = FiltrosTienda::normalizar(['precio_min' => '0', 'precio_max' => '0.00']);
        $this->assertSame('0.00', $filtros['precio_min']);
        $this->assertSame('0.00', $filtros['precio_max']);
        $this->assertSame([], $filtros['avisos']);
    }

    public function testRangoInvertidoNoSeAplicaSilenciosamente(): void
    {
        $filtros = FiltrosTienda::normalizar(['precio_min' => '200', 'precio_max' => '100']);
        $this->assertSame('', $filtros['precio_min']);
        $this->assertSame('', $filtros['precio_max']);
        $this->assertStringContainsString('mínimo', implode(' ', $filtros['avisos']));
    }

    public function testParametrosMalformadosYOrdenSqlNoLleganALaConsulta(): void
    {
        $filtros = FiltrosTienda::normalizar([
            'q' => ['router'], 'tipo' => ['PRODUCTO'], 'categoria' => '-1', 'marca' => str_repeat('9', 30),
            'orden' => 'precioventa DESC; DROP TABLE productos',
        ]);
        $this->assertSame('', $filtros['q']);
        $this->assertSame('', $filtros['tipo']);
        $this->assertSame(0, $filtros['categoria']);
        $this->assertSame(0, $filtros['marca']);
        $this->assertSame('nombre_asc', $filtros['orden']);
        $this->assertSame(100, mb_strlen(FiltrosTienda::normalizar(['q' => str_repeat('á', 101)])['q']));
    }
}
