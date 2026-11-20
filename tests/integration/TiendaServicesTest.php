<?php

use App\Services\PedidoWebService;
use App\Services\PedidoWebCorreoService;
use App\Services\PedidoCorreoTransportador;
use App\Services\TiendaCarritoService;
use App\Services\TiendaCatalogoService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TiendaServicesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect(['database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '', 'foreignKeys' => true], false);
        $queries = [
            'CREATE TABLE categorias (idcategoria INTEGER PRIMARY KEY, idcategoriapadre INTEGER, categoria TEXT, estado INTEGER DEFAULT 1)',
            'CREATE TABLE marcas (idmarca INTEGER PRIMARY KEY, marca TEXT)',
            'CREATE TABLE unidadesmedida (idunidad INTEGER PRIMARY KEY, abreviatura TEXT)',
            'CREATE TABLE productos (idproducto INTEGER PRIMARY KEY, idcategoria INTEGER, idmarca INTEGER, idunidad INTEGER, tipo TEXT, nombre TEXT, descripcion TEXT, modelo TEXT, precioventa NUMERIC, preciocosto NUMERIC, imagen TEXT, garantiameses INTEGER, controlaserie INTEGER, estado INTEGER)',
            'CREATE TABLE locales (idlocal INTEGER PRIMARY KEY, nombrelocal TEXT, tipolocal TEXT)',
            'CREATE TABLE estadopedido (idestado INTEGER PRIMARY KEY, codigo TEXT, estado TEXT)',
            'CREATE TABLE personas (idpersona INTEGER PRIMARY KEY AUTOINCREMENT, tipopersona TEXT, nombres TEXT, apellidos TEXT, razonsocial TEXT, tipodoc TEXT, numerodoc TEXT, direccion TEXT, telefono TEXT, email TEXT, estado INTEGER)',
            'CREATE TABLE pedido (idpedido INTEGER PRIMARY KEY AUTOINCREMENT, tipopedido TEXT, idestado INTEGER, fecha TEXT, idlocal INTEGER, idusuario INTEGER, idpersona INTEGER, codigo TEXT, subtotal NUMERIC, descuento NUMERIC, total NUMERIC, tipodescuento TEXT, valordescuento NUMERIC, observaciones TEXT, request_token TEXT, updated_at TEXT, revision INTEGER)',
            'CREATE TABLE detpedido (iddetpedido INTEGER PRIMARY KEY AUTOINCREMENT, idpedido INTEGER, idproducto INTEGER, cantidad NUMERIC, preciounitario NUMERIC, nombre_snapshot TEXT, config_snapshot TEXT)',
            'CREATE TABLE cliente_cuentas (idcliente_cuenta INTEGER PRIMARY KEY, idpersona INTEGER, email TEXT)',
            'CREATE TABLE pedido_web (idpedido_web INTEGER PRIMARY KEY AUTOINCREMENT, idpedido INTEGER, idcliente_cuenta INTEGER, modalidad TEXT, direccion_entrega TEXT, nombre_contacto TEXT, email_contacto TEXT, telefono_contacto TEXT, token_seguimiento_hash TEXT, creado_en TEXT)',
            'CREATE TABLE historial_pedido (idhistorial INTEGER PRIMARY KEY AUTOINCREMENT, idpedido INTEGER, idusuario INTEGER, evento TEXT, estado_anterior TEXT, estado_nuevo TEXT, motivo TEXT, fecha TEXT)',
        ];
        foreach ($queries as $query) $this->db->query($query);
        $this->db->table('categorias')->insert(['idcategoria' => 1, 'categoria' => 'Redes']);
        $this->db->table('marcas')->insert(['idmarca' => 1, 'marca' => 'Marca']);
        $this->db->table('unidadesmedida')->insert(['idunidad' => 1, 'abreviatura' => 'UND']);
        $this->db->table('productos')->insertBatch([
            ['idproducto' => 1, 'idcategoria' => 1, 'idmarca' => 1, 'idunidad' => 1, 'tipo' => 'PRODUCTO', 'nombre' => 'Router', 'descripcion' => 'Router empresarial', 'modelo' => 'R1', 'precioventa' => 120.50, 'preciocosto' => 50, 'imagen' => null, 'garantiameses' => 12, 'controlaserie' => 0, 'estado' => 1],
            ['idproducto' => 2, 'idcategoria' => 1, 'idmarca' => null, 'idunidad' => 1, 'tipo' => 'SERVICIO', 'nombre' => 'Instalacion', 'descripcion' => '', 'modelo' => null, 'precioventa' => 80, 'preciocosto' => 10, 'imagen' => null, 'garantiameses' => 0, 'controlaserie' => 0, 'estado' => 0],
        ]);
        $this->db->table('locales')->insert(['idlocal' => 1, 'nombrelocal' => 'Principal', 'tipolocal' => 'TIENDA']);
        $this->db->table('estadopedido')->insert(['idestado' => 1, 'codigo' => 'PENDIENTE', 'estado' => 'Pendiente']);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testCatalogoSoloExponeCamposPublicosYActivos(): void
    {
        $resultado = (new TiendaCatalogoService($this->db))->listar('', '', 1);
        $this->assertSame([1], array_map('intval', array_column($resultado['items'], 'idproducto')));
        $this->assertArrayNotHasKey('preciocosto', $resultado['items'][0]);
        $this->assertArrayNotHasKey('monedacosto', $resultado['items'][0]);
        $this->assertSame('UND', $resultado['items'][0]['unidad']);
    }

    public function testCategoriaPrincipalIncluyeProductosDeSusSubcategorias(): void
    {
        $this->db->table('categorias')->insert(['idcategoria' => 2, 'idcategoriapadre' => 1, 'categoria' => 'Routers', 'estado' => 1]);
        $this->db->table('productos')->insert([
            'idproducto' => 3, 'idcategoria' => 2, 'idmarca' => 1, 'idunidad' => 1,
            'tipo' => 'PRODUCTO', 'nombre' => 'Access point', 'descripcion' => 'Equipo WiFi',
            'modelo' => 'AP1', 'precioventa' => 90, 'preciocosto' => 40, 'imagen' => null,
            'garantiameses' => 12, 'controlaserie' => 0, 'estado' => 1,
        ]);

        $resultado = (new TiendaCatalogoService($this->db))->listar('', '', 1, 1);
        $categorias = (new TiendaCatalogoService($this->db))->categorias();

        $this->assertSame([3, 1], array_map('intval', array_column($resultado['items'], 'idproducto')));
        $this->assertSame(2, $categorias[0]['productos']);
        $this->assertSame(1, $categorias[1]['idcategoriapadre']);
    }

    public function testCarritoIgnoraPrecioClienteYRecalculaConPrecioActual(): void
    {
        $resumen = (new TiendaCarritoService($this->db))->resolver([1 => '2.00']);
        $this->assertSame('241.00', $resumen['total']);
        $this->assertSame('120.5', (string) $resumen['items'][0]['precioventa']);
        $this->assertSame([2], (new TiendaCarritoService($this->db))->resolver([2 => 1])['no_disponibles']);
    }

    public function testPedidoWebQuedaPendienteSinUsuarioYGuardaSoloHashDelToken(): void
    {
        $transportador = new class implements PedidoCorreoTransportador {
            public array $mensajes = [];
            public function enviar(string $destino, string $asunto, string $mensaje): void { $this->mensajes[] = compact('destino', 'asunto', 'mensaje'); }
        };
        $correos = new PedidoWebCorreoService($this->db, $transportador);
        $pedido = (new PedidoWebService($this->db, $correos))->crear([
            'request_token' => str_repeat('a', 64),
            'modalidad' => 'DOMICILIO', 'nombre' => 'Ana Cliente', 'email' => 'ana@example.com',
            'telefono' => '999 999 999', 'tipodoc' => 'DNI', 'numerodoc' => '12345678',
            'direccion' => 'Avenida Principal 123', 'observaciones' => 'Llamar antes',
        ], [1 => '2']);

        $cabecera = $this->db->table('pedido')->where('idpedido', $pedido['idpedido'])->get()->getRowArray();
        $web = $this->db->table('pedido_web')->where('idpedido', $pedido['idpedido'])->get()->getRowArray();
        $this->assertNull($cabecera['idusuario']);
        $this->assertNull($cabecera['idpersona']);
        $this->assertSame('TIENDA_WEB', $cabecera['tipopedido']);
        $this->assertSame('241', (string) $cabecera['total']);
        $this->assertSame(hash('sha256', $pedido['token']), $web['token_seguimiento_hash']);
        $this->assertNotSame($pedido['token'], $web['token_seguimiento_hash']);
        $this->assertTrue($pedido['correo_enviado']);
        $this->assertCount(1, $transportador->mensajes);
        $this->assertStringContainsString('/seguimiento/' . $pedido['token'], $transportador->mensajes[0]['mensaje']);
    }
}
