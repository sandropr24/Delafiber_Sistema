<?php

use App\Services\TiendaCatalogoService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TiendaCatalogoFiltrosTest extends CIUnitTestCase
{
    private ?BaseConnection $conexion = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sqlite3')) $this->markTestSkipped('Requiere SQLite3; nunca se utiliza la base de trabajo.');
        $this->conexion = Database::connect(['database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => ''], false);
        foreach ([
            'CREATE TABLE categorias (idcategoria INTEGER PRIMARY KEY, idcategoriapadre INTEGER, categoria TEXT, estado INTEGER)',
            'CREATE TABLE marcas (idmarca INTEGER PRIMARY KEY, marca TEXT, estado INTEGER)',
            'CREATE TABLE unidadesmedida (idunidad INTEGER PRIMARY KEY, abreviatura TEXT)',
            'CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, idcategoria INTEGER, idmarca INTEGER, idunidad INTEGER, tipo TEXT, nombre TEXT, descripcion TEXT, modelo TEXT, precioventa NUMERIC, preciocosto NUMERIC, imagen TEXT, garantiameses INTEGER, estado INTEGER)',
            'CREATE TABLE combo_detalle (idcombo INTEGER, idproducto INTEGER, cantidad NUMERIC)',
        ] as $sql) $this->conexion->query($sql);
        $this->conexion->table('categorias')->insertBatch([
            ['idcategoria' => 1, 'idcategoriapadre' => null, 'categoria' => 'Redes', 'estado' => 1],
            ['idcategoria' => 2, 'idcategoriapadre' => 1, 'categoria' => 'Routers', 'estado' => 1],
        ]);
        $this->conexion->table('marcas')->insertBatch([
            ['idmarca' => 1, 'marca' => 'Acme', 'estado' => 1],
            ['idmarca' => 2, 'marca' => 'Otra marca', 'estado' => 1],
            ['idmarca' => 3, 'marca' => 'Sin productos', 'estado' => 1],
            ['idmarca' => 4, 'marca' => 'Inactiva', 'estado' => 0],
        ]);
        $this->conexion->table('unidadesmedida')->insert(['idunidad' => 1, 'abreviatura' => 'UND']);
        $this->conexion->table('productos')->insertBatch([
            $this->producto(1, 'Router A', 100, 1),
            $this->producto(2, 'Router B', 200, 1),
            $this->producto(3, 'Router C', 200, 2),
            $this->producto(4, 'Oculto', 150, 1, ['estado' => 0]),
            $this->producto(5, 'Instalación', 80, null, ['tipo' => 'SERVICIO']),
            $this->producto(6, 'Kit', 300, null, ['tipo' => 'COMBO']),
        ]);
    }

    protected function tearDown(): void
    {
        $this->conexion?->close();
        parent::tearDown();
    }

    private function producto(int $id, string $nombre, int $precio, ?int $marca, array $cambios = []): array
    {
        return array_replace([
            'idproducto' => $id, 'idcategoria' => 2, 'idmarca' => $marca, 'idunidad' => 1,
            'tipo' => 'PRODUCTO', 'nombre' => $nombre, 'descripcion' => 'Descripción',
            'modelo' => 'Modelo', 'precioventa' => $precio, 'preciocosto' => 10,
            'imagen' => null, 'garantiameses' => 12, 'estado' => 1,
        ], $cambios);
    }

    public function testCombinaCategoriaPadreMarcaYRangoInclusivoSinExponerCostos(): void
    {
        $resultado = (new TiendaCatalogoService($this->conexion))->listar('', 'PRODUCTO', 1, 1, 12, [
            'marca' => 1, 'precio_min' => '100', 'precio_max' => '200', 'orden' => 'precio_desc',
        ]);
        $this->assertSame([2, 1], array_map('intval', array_column($resultado['items'], 'idproducto')));
        $this->assertSame(2, $resultado['total']);
        $this->assertArrayNotHasKey('preciocosto', $resultado['items'][0]);
    }

    public function testBusquedaIncluyeMarcaYOrdenEstableSeConservaAlPaginar(): void
    {
        $catalogo = new TiendaCatalogoService($this->conexion);
        $resultado = $catalogo->listar('Acme', '', 1);
        $this->assertSame([1, 2], array_map('intval', array_column($resultado['items'], 'idproducto')));
        $primera = $catalogo->listar('', 'PRODUCTO', 1, 0, 1, ['orden' => 'precio_desc']);
        $segunda = $catalogo->listar('', 'PRODUCTO', 2, 0, 1, ['orden' => 'precio_desc']);
        $this->assertSame(2, (int) $primera['items'][0]['idproducto']);
        $this->assertSame(3, (int) $segunda['items'][0]['idproducto']);
        $this->assertSame(3, $segunda['paginas']);
    }

    public function testMarcasSoloIncluyeActivasConProductosPublicados(): void
    {
        $marcas = (new TiendaCatalogoService($this->conexion))->marcas();
        $this->assertSame([1, 2], array_map('intval', array_column($marcas, 'idmarca')));
    }

    public function testComponentesSoloPublicosDeUnComboActivoSinCostosNiSeries(): void
    {
        $this->conexion->table('combo_detalle')->insertBatch([
            ['idcombo' => 6, 'idproducto' => 1, 'cantidad' => 2],
            ['idcombo' => 6, 'idproducto' => 5, 'cantidad' => 1],
            ['idcombo' => 6, 'idproducto' => 4, 'cantidad' => 1],
        ]);
        $catalogo = new TiendaCatalogoService($this->conexion);
        $componentes = $catalogo->componentes(6);
        $this->assertSame([1, 5], array_map('intval', array_column($componentes, 'idproducto')));
        $this->assertSame(2, (int) $componentes[0]['cantidad']);
        $this->assertArrayNotHasKey('preciocosto', $componentes[0]);
        $this->assertArrayNotHasKey('precioventa', $componentes[0]);
        $this->assertArrayNotHasKey('numeroserie', $componentes[0]);
        $this->conexion->table('productos')->where('idproducto', 6)->update(['estado' => 0]);
        $this->assertSame([], $catalogo->componentes(6));
        $this->assertNull($catalogo->obtener(6));
    }

    public function testOrdenNoPermitidoVuelveANombreYPrecioCeroNoSePierde(): void
    {
        $catalogo = new TiendaCatalogoService($this->conexion);
        $resultado = $catalogo->listar('', 'PRODUCTO', 99, 0, 12, ['orden' => 'p.preciocosto DESC']);
        $this->assertSame([1, 2, 3], array_map('intval', array_column($resultado['items'], 'idproducto')));
        $this->assertSame(1, $resultado['pagina']);
        $this->assertSame(0, $catalogo->listar('', '', 1, 0, 12, ['precio_max' => '0'])['total']);
    }
}
