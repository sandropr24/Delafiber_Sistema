<?php

use App\Libraries\CostoVenta;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CostoVentaTest extends CIUnitTestCase
{
    private function articulos(): array
    {
        return [
            1 => ['idproducto' => 1, 'nombre' => 'Cámara', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'preciocosto' => '999.00'],
            2 => ['idproducto' => 2, 'nombre' => 'Cable', 'tipo' => 'PRODUCTO', 'controlastock' => 1, 'preciocosto' => '0.00'],
            3 => ['idproducto' => 3, 'nombre' => 'Instalación', 'tipo' => 'SERVICIO', 'controlastock' => 0, 'preciocosto' => '15.00'],
            4 => ['idproducto' => 4, 'nombre' => 'Kit instalado', 'tipo' => 'COMBO', 'controlastock' => 0, 'preciocosto' => '800.00'],
        ];
    }

    public function testTomaCostoDeKardexYNoDelCatalogoNiDelFormulario(): void
    {
        $detalles = [['idproducto' => 1, 'cantidad' => 2, 'costounitario' => '0.01']];
        $this->assertSame(['125.46'], CostoVenta::calcular($detalles, $this->articulos(), [], [1 => '125.4567']));
    }

    public function testUsaElMapaDeCostosDelLocalSeleccionado(): void
    {
        $detalles = [['idproducto' => 1, 'cantidad' => 1]];
        $this->assertSame(['10.00'], CostoVenta::calcular($detalles, $this->articulos(), [], [1 => '10.0000']));
        $this->assertSame(['20.00'], CostoVenta::calcular($detalles, $this->articulos(), [], [1 => '20.0000']));
    }

    public function testSumaComponentesDelComboYDivideEntreLaCantidadVendida(): void
    {
        $detalles = [['idproducto' => 4, 'cantidad' => 2]];
        $componentes = [[
            ['idproducto' => 1, 'cantidad' => 4],
            ['idproducto' => 2, 'cantidad' => 5.5],
            ['idproducto' => 3, 'cantidad' => 2],
        ]];
        // Dos cámaras, 2.75 m de cable y una instalación por kit.
        $this->assertSame(['43.41'], CostoVenta::calcular(
            $detalles, $this->articulos(), $componentes, [1 => '10.1234', 2 => '2.9700']
        ));
    }

    public function testNoRedondeaCadaComponenteAntesDeSumar(): void
    {
        $this->assertSame(['0.01'], CostoVenta::calcular(
            [['idproducto' => 4, 'cantidad' => 1]], $this->articulos(),
            [[['idproducto' => 1, 'cantidad' => 1], ['idproducto' => 2, 'cantidad' => 1]]],
            [1 => '0.0040', 2 => '0.0040']
        ));
    }

    public function testServiciosSinStockConservanSuCostoSinExigirKardex(): void
    {
        $this->assertSame(['15.00'], CostoVenta::calcular(
            [['idproducto' => 3, 'cantidad' => 2]], $this->articulos(), [], []
        ));
        $articulos = $this->articulos();
        unset($articulos[3]['preciocosto']);
        $this->assertSame(['0.00'], CostoVenta::calcular(
            [['idproducto' => 3, 'cantidad' => 1]], $articulos, [], []
        ));
    }

    public function testAceptaCostoCeroDeKardexSinUsarElCostoAntiguo(): void
    {
        $this->assertSame(['0.00'], CostoVenta::calcular(
            [['idproducto' => 1, 'cantidad' => 1]], $this->articulos(), [], [1 => '0.0000']
        ));
    }

    public function testRechazaStockSinCostoDeKardexEnVezDeInventarUnCosto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kardex');
        CostoVenta::calcular([['idproducto' => 1, 'cantidad' => 1]], $this->articulos(), [], []);
    }

    #[DataProvider('costosInvalidos')]
    public function testRechazaCostosInvalidos($costo): void
    {
        $this->expectException(InvalidArgumentException::class);
        CostoVenta::calcular([['idproducto' => 1, 'cantidad' => 1]], $this->articulos(), [], [1 => $costo]);
    }

    public static function costosInvalidos(): array
    {
        return [[null], ['incorrecto'], ['-1.0000'], [INF], [NAN], ['100000000.00']];
    }

    public function testRechazaComboSinComponentes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CostoVenta::calcular([['idproducto' => 4, 'cantidad' => 1]], $this->articulos(), [], []);
    }

    public function testRechazaCostoDeComboQueSuperaLaColumnaDelDetalle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CostoVenta::calcular(
            [['idproducto' => 4, 'cantidad' => 1]], $this->articulos(),
            [[['idproducto' => 1, 'cantidad' => 2]]], [1 => '60000000.00']
        );
    }

    public function testMantieneLosIndicesDelDetalleYNoModificaLasEntradas(): void
    {
        $detalles = [5 => ['idproducto' => 1, 'cantidad' => 2], 9 => ['idproducto' => 1, 'cantidad' => 3]];
        $antes = $detalles;
        $this->assertSame([5 => '12.35', 9 => '12.35'], CostoVenta::calcular($detalles, $this->articulos(), [], [1 => '12.3456']));
        $this->assertSame($antes, $detalles);
    }
}
