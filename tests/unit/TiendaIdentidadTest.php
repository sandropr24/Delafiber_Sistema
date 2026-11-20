<?php

use App\Services\ClienteRegistroService;
use App\Services\PedidoCorreoTransportador;
use App\Services\PedidoWebCorreoService;
use App\Services\PedidoWebService;
use App\Services\PedidoWebWhatsAppService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\MemoryDatabaseTrait;

final class TiendaIdentidadTest extends CIUnitTestCase
{
    use MemoryDatabaseTrait;

    private function correo(): PedidoCorreoTransportador
    {
        return new class implements PedidoCorreoTransportador {
            public array $mensajes = [];
            public bool $fallar = false;
            public function enviar(string $destino, string $asunto, string $mensaje): void
            {
                if ($this->fallar) throw new RuntimeException('Fallo simulado');
                $this->mensajes[] = compact('destino', 'mensaje');
            }
        };
    }

    private function datos(): array
    {
        return ['email' => 'cliente@example.com', 'nombre' => 'Cliente de prueba', 'clave' => bin2hex(random_bytes(16)), 'tipodoc' => 'DNI', 'numerodoc' => '00000001'];
    }

    public function testRegistroQuedaInactivoYElEnlaceActivaSoloUnaVez(): void
    {
        [$db, $estado] = $this->memoryDatabase(['personas' => [], 'cliente_cuentas' => []]);
        $correo = $this->correo();
        $servicio = new ClienteRegistroService($db, $correo);
        $datos = $this->datos();
        $this->assertTrue($servicio->registrar($datos));
        $cuenta = $estado->tablas['cliente_cuentas'][0];
        $this->assertSame(0, $cuenta['activo']);
        $this->assertNull($cuenta['email_verificado_en']);
        $this->assertTrue(password_verify($datos['clave'], $cuenta['clave_hash']));
        preg_match('~/cliente/verificar/([a-f0-9]{64})~', $correo->mensajes[0]['mensaje'], $m);
        $this->assertSame(hash('sha256', $m[1]), $cuenta['verificacion_token_hash']);
        $this->assertGreaterThan(time() + 86390, strtotime($cuenta['verificacion_expira_en']));
        $this->assertTrue($servicio->verificar($m[1]));
        $this->assertSame(1, $estado->tablas['cliente_cuentas'][0]['activo']);
        $this->assertNull($estado->tablas['cliente_cuentas'][0]['verificacion_token_hash']);
        $this->assertFalse($servicio->verificar($m[1]));
    }

    public function testDniAjenoNoSeApropiaNiModificaLaPersonaYNoEnviaCorreo(): void
    {
        $original = ['idpersona' => 7, 'tipodoc' => 'DNI', 'numerodoc' => '00000001', 'email' => 'titular@example.com', 'nombres' => 'Titular'];
        [$db, $estado] = $this->memoryDatabase(['personas' => [$original], 'cliente_cuentas' => []]);
        $correo = $this->correo();
        $this->assertFalse((new ClienteRegistroService($db, $correo))->registrar($this->datos()));
        $this->assertSame([$original], $estado->tablas['personas']);
        $this->assertSame([], $estado->tablas['cliente_cuentas']);
        $this->assertSame([], $correo->mensajes);
    }

    public function testReenvioNoCambiaPasswordYTokenAnteriorYVencidoNoActivan(): void
    {
        [$db, $estado] = $this->memoryDatabase(['personas' => [], 'cliente_cuentas' => []]);
        $correo = $this->correo();
        $servicio = new ClienteRegistroService($db, $correo);
        $servicio->registrar($this->datos());
        preg_match('~/verificar/([a-f0-9]{64})~', $correo->mensajes[0]['mensaje'], $anterior);
        $hash = $estado->tablas['cliente_cuentas'][0]['clave_hash'];
        $servicio->registrar($this->datos());
        $this->assertSame($hash, $estado->tablas['cliente_cuentas'][0]['clave_hash']);
        $this->assertFalse($servicio->verificar($anterior[1]));
        preg_match('~/verificar/([a-f0-9]{64})~', $correo->mensajes[1]['mensaje'], $nuevo);
        $estado->tablas['cliente_cuentas'][0]['verificacion_expira_en'] = date('Y-m-d H:i:s', time() - 1);
        $this->assertFalse($servicio->verificar($nuevo[1]));
        $this->assertSame(0, $estado->tablas['cliente_cuentas'][0]['activo']);
    }

    public function testCorreoDuplicadoVerificadoNoCambiaNadaYFalloDeCorreoNoActiva(): void
    {
        [$db, $estado] = $this->memoryDatabase(['personas' => [], 'cliente_cuentas' => []]);
        $correo = $this->correo();
        $correo->fallar = true;
        $servicio = new ClienteRegistroService($db, $correo);
        $this->assertFalse($servicio->registrar($this->datos()));
        $this->assertSame(0, $estado->tablas['cliente_cuentas'][0]['activo']);
        $estado->tablas['cliente_cuentas'][0]['email_verificado_en'] = date('Y-m-d H:i:s');
        $antes = $estado->tablas;
        $this->assertFalse($servicio->registrar($this->datos()));
        $this->assertSame($antes, $estado->tablas);
    }

    public function testInvitadoNoSeVinculaPorDniYConservaContactoYHashDeSeguimiento(): void
    {
        $titular = ['idpersona' => 7, 'numerodoc' => '00000001', 'nombres' => 'Titular'];
        [$db, $estado] = $this->memoryDatabase([
            'personas' => [$titular], 'pedido' => [], 'pedido_web' => [], 'detpedido' => [], 'historial_pedido' => [],
            'productos' => [['idproducto' => 1, 'tipo' => 'PRODUCTO', 'nombre' => 'Equipo', 'precioventa' => '10.00', 'imagen' => null, 'controlaserie' => 0, 'unidad' => 'UND', 'estado' => 1]],
            'locales' => [['idlocal' => 1]], 'estadopedido' => [['idestado' => 1, 'codigo' => 'PENDIENTE']],
        ]);
        $correo = $this->correo();
        $service = new PedidoWebService($db, new PedidoWebCorreoService($db, $correo), new PedidoWebWhatsAppService(null, ''));
        $r = $service->crear($this->datos() + ['telefono' => '999999999', 'modalidad' => 'RECOJO', 'request_token' => str_repeat('a', 64)], [1 => '1']);
        $this->assertNull($estado->tablas['pedido'][0]['idpersona']);
        $this->assertSame([$titular], $estado->tablas['personas']);
        $this->assertSame('cliente@example.com', $estado->tablas['pedido_web'][0]['email_contacto']);
        $this->assertSame(hash('sha256', $r['token']), $estado->tablas['pedido_web'][0]['token_seguimiento_hash']);
        $this->assertCount(1, $correo->mensajes);
        $r = $service->crear($this->datos() + ['telefono' => '999999999', 'modalidad' => 'RECOJO', 'request_token' => str_repeat('b', 64)], [1 => '1'], ['idcliente_cuenta' => 2, 'idpersona' => 7]);
        $this->assertSame(7, $estado->tablas['pedido'][1]['idpersona']);
    }
}
