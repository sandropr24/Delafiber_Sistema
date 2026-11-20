<?php

use App\Filters\ThrottleFilter;
use App\Services\PedidoCorreoTransportador;
use App\Services\PedidoWhatsAppTransportador;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Throttle\Throttler;

final class ThrottleFilterTest extends CIUnitTestCase
{
    private function filtro(): ThrottleFilter
    {
        $valores = [];
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(static function ($key) use (&$valores) { return $valores[$key] ?? null; });
        $cache->method('save')->willReturnCallback(static function ($key, $valor) use (&$valores) { $valores[$key] = $valor; return true; });
        return new ThrottleFilter((new Throttler($cache))->setTestTime(1000));
    }

    private function request(bool $ajax = true, string $email = 'cliente@example.com', string $ip = '192.0.2.1'): IncomingRequest
    {
        $request = $this->getMockBuilder(IncomingRequest::class)->disableOriginalConstructor()
            ->onlyMethods(['getIPAddress', 'getPost', 'isAJAX', 'getUri'])->getMock();
        $request->method('getIPAddress')->willReturn($ip);
        $request->method('getPost')->with('email')->willReturn($email);
        $request->method('isAJAX')->willReturn($ajax);
        $request->method('getUri')->willReturn(new URI('http://example.com/checkout'));
        return $request;
    }

    public function testSextaSolicitudNoLlegaAlControladorNiALosTransportadores(): void
    {
        $correo = new class implements PedidoCorreoTransportador {
            public array $mensajes = [];
            public function enviar(string $destino, string $asunto, string $mensaje): void { $this->mensajes[] = $mensaje; }
        };
        $whatsapp = new class implements PedidoWhatsAppTransportador {
            public array $mensajes = [];
            public function enviar(string $destino, array $datos): void { $this->mensajes[] = $datos; }
        };
        $filtro = $this->filtro();
        for ($i = 0; $i < 5; $i++) $this->assertNull($filtro->before($this->request(), ['checkout', '5', '600']));
        $respuesta = $filtro->before($this->request(), ['checkout', '5', '600']);
        $pedidos = 0;
        if ($respuesta === null) {
            $pedidos++;
            $correo->enviar('cliente@example.com', 'Pedido', 'Creado');
            $whatsapp->enviar('51999999999', []);
        }
        $this->assertSame(429, $respuesta->getStatusCode());
        $this->assertSame(0, $pedidos);
        $this->assertSame([], $correo->mensajes);
        $this->assertSame([], $whatsapp->mensajes);
        $this->assertNotSame('', $respuesta->getHeaderLine('Retry-After'));
    }

    public function testLimitePorCorreoNormalizadoSeComparteEntreIps(): void
    {
        $filtro = $this->filtro();
        for ($i = 0; $i < 3; $i++) $this->assertNull($filtro->before($this->request(true, ' Cliente@example.com ', '192.0.2.' . ($i + 1)), ['email', '3', '3600', 'email']));
        $this->assertSame(429, $filtro->before($this->request(true), ['email', '3', '3600', 'email'])->getStatusCode());
        $this->assertNull($filtro->before($this->request(true, 'otro@example.com'), ['email', '3', '3600', 'email']));
    }

    public function testFormularioRecibeRedireccionInternaConError(): void
    {
        helper(['url', 'form']);
        $filtro = $this->filtro();
        $filtro->before($this->request(false), ['form', '1', '60']);
        $r = $filtro->before($this->request(false), ['form', '1', '60']);
        $this->assertSame(303, $r->getStatusCode());
        $this->assertStringEndsWith('/checkout', $r->getHeaderLine('Location'));
        $this->assertStringContainsString('Demasiadas solicitudes', session()->getFlashdata('error'));
    }

    public function testRutasRealesTienenLosLimitesIndicados(): void
    {
        $rutas = service('routes')->loadRoutes();
        $this->assertContains('throttle:checkout-ip,5,600', $rutas->getFiltersForRoute('checkout', 'POST'));
        $this->assertContains('throttle:checkout-email,3,3600,email', $rutas->getFiltersForRoute('checkout', 'POST'));
        $this->assertContains('throttle:register-ip,5,3600', $rutas->getFiltersForRoute('cliente/registro', 'POST'));
        $this->assertContains('throttle:cart-add,30,60', $rutas->getFiltersForRoute('carrito/agregar', 'POST'));
        $this->assertContains('throttle:cart-update,30,60', $rutas->getFiltersForRoute('carrito/actualizar', 'POST'));
    }
}
