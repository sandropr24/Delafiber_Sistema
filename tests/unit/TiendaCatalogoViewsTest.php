<?php

use App\Libraries\FiltrosTienda;
use CodeIgniter\Test\CIUnitTestCase;

final class TiendaCatalogoViewsTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function layout(): array
    {
        return [
            'titulo' => 'Tienda', 'carritoCantidad' => 0, 'clienteWeb' => null, 'busquedaGlobal' => '',
            'categoriasTienda' => [['idcategoria' => 1, 'idcategoriapadre' => null, 'categoria' => 'Redes', 'productos' => 1]],
        ];
    }

    private function producto(array $cambios = []): array
    {
        return array_replace([
            'idproducto' => 1, 'idcategoria' => 1, 'tipo' => 'PRODUCTO', 'nombre' => '<Router>',
            'descripcion' => '<script>alert(1)</script>', 'modelo' => 'R1', 'marca' => 'Marca <A>',
            'precioventa' => '120.50', 'imagen' => 'router.jpg', 'garantiameses' => 12,
            'categoria' => 'Redes', 'unidad' => 'UND',
        ], $cambios);
    }

    public function testCatalogoConservaLosFiltrosAlPaginarYPermiteQuitarlosPorSeparado(): void
    {
        $filtros = FiltrosTienda::normalizar([
            'q' => '<Router>', 'marca' => 1, 'categoria' => 1,
            'precio_min' => '10', 'precio_max' => '200', 'orden' => 'precio_desc',
        ]);
        $html = view('tienda/catalogo', $this->layout() + $filtros + [
            'items' => [$this->producto()], 'total' => 25, 'pagina' => 1, 'paginas' => 3,
            'filtrosCatalogo' => $filtros, 'ordenesCatalogo' => FiltrosTienda::ORDENES,
            'marcasTienda' => [['idmarca' => 1, 'marca' => 'Marca <A>']],
        ], ['saveData' => false]);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        $enlace = $xpath->query('//a[@rel="next"]')->item(0)->getAttribute('href');
        parse_str(parse_url($enlace, PHP_URL_QUERY), $parametros);
        $this->assertSame('2', $parametros['pagina']);
        $this->assertSame('1', $parametros['marca']);
        $this->assertSame('10.00', $parametros['precio_min']);
        $this->assertSame('200.00', $parametros['precio_max']);
        $this->assertSame('precio_desc', $parametros['orden']);
        $this->assertSame('<Router>', $parametros['q']);
        $this->assertSame('catalogo', parse_url($enlace, PHP_URL_FRAGMENT));
        $chips = $xpath->query('//nav[@class="active-filters"]/a');
        $this->assertCount(4, $chips);
        $this->assertStringStartsWith('Quitar filtro:', $chips->item(0)->getAttribute('aria-label'));
        parse_str(parse_url($chips->item(2)->getAttribute('href'), PHP_URL_QUERY), $sinMarca);
        $this->assertArrayNotHasKey('marca', $sinMarca);
        $this->assertSame('precio_desc', $sinMarca['orden']);
        $this->assertStringContainsString('&lt;Router&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('class="campaign-hero"', $html);
        $this->assertStringContainsString('12 meses de garantía', $html);
        $this->assertStringContainsString('name="csrf_test_name"', $html);
        $this->assertStringContainsString('data-cart-add', $html);
        $this->assertStringContainsString('data-cart-count', $html);
        $this->assertStringContainsString('data-cart-feedback hidden', $html);
        $this->assertStringContainsString('Seguir comprando', $html);
        $this->assertStringContainsString('Ver carrito', $html);
        $this->assertStringContainsString('tienda-carrito.js', $html);
    }

    public function testInicioNoSimulaProductosDestacadosYOfreceFiltros(): void
    {
        $filtros = FiltrosTienda::normalizar([]);
        $html = view('tienda/catalogo', $this->layout() + $filtros + [
            'items' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1,
            'filtrosCatalogo' => $filtros, 'ordenesCatalogo' => FiltrosTienda::ORDENES, 'marcasTienda' => [],
        ], ['saveData' => false]);
        $this->assertStringContainsString('Catálogo de soluciones', $html);
        $this->assertStringContainsString('class="campaign-hero"', $html);
        $this->assertStringContainsString('name="precio_min"', $html);
        $this->assertStringContainsString('name="marca"', $html);
        $this->assertStringContainsString('No encontramos coincidencias', $html);
        $this->assertStringNotContainsString('Productos destacados', $html);
    }

    public function testDetalleEscapaDatosYUsaLaFotoExistenteSinAnunciarStockReal(): void
    {
        $html = view('tienda/detalle', $this->layout() + ['producto' => $this->producto(), 'componentes' => []], ['saveData' => false]);
        $this->assertStringContainsString('data-image-zoom', $html);
        $this->assertStringContainsString('id="imagen-producto"', $html);
        $this->assertStringContainsString('Disponibilidad por confirmar', $html);
        $this->assertStringContainsString('Entrega a domicilio sin costo', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('¿Qué incluye este combo?', $html);
        $this->assertStringContainsString('step="1"', $html);
        $this->assertStringContainsString('method="post" action="' . base_url('carrito/agregar') . '" data-cart-add', $html);
    }

    public function testComboMuestraCantidadesYPlazosIndividualesNoLaGarantiaDelKit(): void
    {
        $html = view('tienda/detalle', $this->layout() + [
            'producto' => $this->producto(['tipo' => 'COMBO', 'garantiameses' => 99, 'imagen' => null]),
            'componentes' => [
                ['idproducto' => 2, 'nombre' => '<Cámara>', 'tipo' => 'PRODUCTO', 'modelo' => 'C1', 'garantiameses' => 12, 'cantidad' => '2.00', 'unidad' => 'UND'],
                ['idproducto' => 3, 'nombre' => 'Cable', 'tipo' => 'PRODUCTO', 'modelo' => null, 'garantiameses' => 0, 'cantidad' => '2.50', 'unidad' => 'M'],
                ['idproducto' => 4, 'nombre' => 'Instalación', 'tipo' => 'SERVICIO', 'modelo' => null, 'garantiameses' => 0, 'cantidad' => '1.00', 'unidad' => 'UND'],
            ],
        ], ['saveData' => false]);
        $this->assertStringContainsString('¿Qué incluye este combo?', $html);
        $this->assertStringContainsString('&lt;Cámara&gt;', $html);
        $this->assertStringContainsString('Garantía: 12 meses', $html);
        $this->assertStringContainsString('2.5<small>M</small>', $html);
        $this->assertStringNotContainsString('99 meses', $html);
        $this->assertStringNotContainsString('data-image-zoom', $html);
        $this->assertStringContainsString('Imagen no disponible', $html);
    }

    public function testCableAdmiteDecimalesYServicioNoPrometeGarantiaDeEquipo(): void
    {
        $datos = $this->layout() + ['producto' => $this->producto(['unidad' => 'M']), 'componentes' => []];
        $html = view('tienda/detalle', $datos, ['saveData' => false]);
        $this->assertStringContainsString('step="0.01"', $html);
        $datos['producto']['tipo'] = 'SERVICIO';
        $html = view('tienda/detalle', $datos, ['saveData' => false]);
        $this->assertStringContainsString('Atención sujeta a coordinación', $html);
        $this->assertStringContainsString('Agregar servicio', $html);
        $this->assertStringNotContainsString('12 meses', $html);
    }
}
