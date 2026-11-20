<?php

use App\Services\PedidoCorreoTransportador;
use App\Services\PedidoWebCorreoService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TransportadorPedidoFake implements PedidoCorreoTransportador
{
    public array $mensajes = [];
    public bool $fallar = false;

    public function enviar(string $destino, string $asunto, string $mensaje): void
    {
        if ($this->fallar) throw new RuntimeException('Fallo SMTP simulado.');
        $this->mensajes[] = compact('destino', 'asunto', 'mensaje');
    }
}

final class PedidoWebCorreoServiceTest extends CIUnitTestCase
{
    protected $db;
    private TransportadorPedidoFake $transportador;
    private PedidoWebCorreoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect(['database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => ''], false);
        $this->db->query('CREATE TABLE pedido (idpedido INTEGER PRIMARY KEY, codigo TEXT, tipopedido TEXT, total NUMERIC)');
        $this->db->query('CREATE TABLE pedido_web (idpedido INTEGER, nombre_contacto TEXT, email_contacto TEXT)');
        $this->db->table('pedido')->insertBatch([
            ['idpedido' => 1, 'codigo' => 'PED-000001', 'tipopedido' => 'TIENDA_WEB', 'total' => '150.00'],
            ['idpedido' => 2, 'codigo' => 'PED-000002', 'tipopedido' => 'DIRECTO', 'total' => '80.00'],
        ]);
        $this->db->table('pedido_web')->insert(['idpedido' => 1, 'nombre_contacto' => 'Ana Cliente', 'email_contacto' => 'ana@example.com']);
        $this->transportador = new TransportadorPedidoFake();
        $this->servicio = new PedidoWebCorreoService($this->db, $this->transportador);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testCorreoInicialIncluyeCodigoYEnlaceDeSeguimiento(): void
    {
        $token = str_repeat('a', 64);
        $this->assertTrue($this->servicio->recibido(1, $token));
        $this->assertCount(1, $this->transportador->mensajes);
        $correo = $this->transportador->mensajes[0];
        $this->assertSame('ana@example.com', $correo['destino']);
        $this->assertStringContainsString('PED-000001', $correo['asunto']);
        $this->assertStringContainsString('/seguimiento/' . $token, $correo['mensaje']);
        $this->assertStringContainsString('S/ 150.00', $correo['mensaje']);
    }

    public function testNotificaConfirmacionYCancelacionConMotivo(): void
    {
        $this->assertTrue($this->servicio->cambioEstado(1, 'CONFIRMADO'));
        $this->assertTrue($this->servicio->cambioEstado(1, 'CANCELADO', 'Producto sin disponibilidad'));
        $this->assertStringContainsString('confirmó', $this->transportador->mensajes[0]['mensaje']);
        $this->assertStringContainsString('Producto sin disponibilidad', $this->transportador->mensajes[1]['mensaje']);
    }

    public function testIgnoraPedidosNoWebYAbsorbeFallosDelCorreo(): void
    {
        $this->assertNull($this->servicio->cambioEstado(2, 'CONFIRMADO'));
        $this->assertCount(0, $this->transportador->mensajes);
        $this->transportador->fallar = true;
        $this->assertFalse($this->servicio->cambioEstado(1, 'CONFIRMADO'));
    }
}
