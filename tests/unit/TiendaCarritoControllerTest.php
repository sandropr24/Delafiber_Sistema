<?php

use App\Controllers\TiendaController;
use App\Services\TiendaCarritoService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class TiendaCarritoControllerTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
        session()->set('tienda_carrito', [1 => '2.00']);
    }

    protected function tearDown(): void
    {
        session()->remove(['tienda_carrito', 'mensaje', 'error']);
        parent::tearDown();
    }

    private function ejecutar(bool $ajax, $id = '1', ?Throwable $error = null)
    {
        $servicio = $this->getMockBuilder(TiendaCarritoService::class)->disableOriginalConstructor()->onlyMethods(['agregar'])->getMock();
        $controlador = $this->getMockBuilder(TiendaController::class)->onlyMethods(['servicioCarrito'])->getMock();
        if (is_scalar($id) && ctype_digit((string) $id) && strlen((string) $id) <= 10 && (float) $id <= 2147483647) {
            $controlador->expects($this->once())->method('servicioCarrito')->willReturn($servicio);
            $llamada = $servicio->expects($this->once())->method('agregar')->with([1 => '2.00'], (int) $id, '1');
            if ($error) $llamada->willThrowException($error);
            else $llamada->willReturn([
                'carrito' => [1 => '3.00'], 'resumen' => ['cantidad' => 1, 'total' => '361.50'],
                'producto' => [
                    'idproducto' => 1, 'nombre' => '<Router>', 'cantidad' => '3.00', 'unidad' => 'UND',
                    'preciocosto' => '50', 'controlaserie' => 1, 'numeroserie' => 'PRIVADA',
                ],
                'cantidad_agregada' => '1.00', 'retirados' => 0,
            ]);
        } else {
            $controlador->expects($this->never())->method('servicioCarrito');
        }
        $request = $this->getMockBuilder(IncomingRequest::class)->disableOriginalConstructor()->onlyMethods(['getPost', 'isAJAX'])->getMock();
        $request->method('getPost')->willReturnCallback(static fn($campo) => ['idproducto' => $id, 'cantidad' => '1', 'precioventa' => '0.01'][$campo] ?? null);
        $request->method('isAJAX')->willReturn($ajax);
        $controlador->initController($request, service('response'), service('logger'));
        return $controlador->agregarCarrito();
    }

    public function testAjaxDevuelveResumenPublicoYTokenSinRedireccionNiFlash(): void
    {
        $response = $this->ejecutar(true);
        $datos = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($datos['success']);
        $this->assertSame(['cantidad' => 1, 'total' => '361.50'], $datos['carrito']);
        $this->assertSame('<Router>', $datos['producto']['nombre']);
        $this->assertSame(csrf_token(), $datos['csrf']['name']);
        $this->assertSame(csrf_hash(), $datos['csrf']['hash']);
        $this->assertSame([1 => '3.00'], session('tienda_carrito'));
        $this->assertFalse(session()->has('mensaje'));
        foreach (['preciocosto', 'controlaserie', 'numeroserie'] as $privado) $this->assertArrayNotHasKey($privado, $datos['producto']);
        $this->assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testSinJavaScriptConservaLaRedireccionYElMensaje(): void
    {
        $response = $this->ejecutar(false);
        $this->assertStringEndsWith('/carrito', $response->getHeaderLine('Location'));
        $this->assertSame('Producto agregado al carrito.', session()->getFlashdata('mensaje'));
        $this->assertSame([1 => '3.00'], session('tienda_carrito'));
    }

    #[DataProvider('errores')]
    public function testErrorAjaxNoGuardaCantidadesYDevuelveEstadoYToken(Throwable $error, int $estado, string $mensaje): void
    {
        $response = $this->ejecutar(true, '1', $error);
        $datos = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($estado, $response->getStatusCode());
        $this->assertFalse($datos['success']);
        $this->assertSame($mensaje, $datos['mensaje']);
        $this->assertSame(csrf_hash(), $datos['csrf']['hash']);
        $this->assertSame([1 => '2.00'], session('tienda_carrito'));
        $this->assertArrayNotHasKey('carrito', $datos);
    }

    public static function errores(): array
    {
        return [
            [new InvalidArgumentException('Cantidad inválida.'), 422, 'Cantidad inválida.'],
            [new RuntimeException('Error SQL privado'), 500, 'No se pudo actualizar el carrito.'],
        ];
    }

    #[DataProvider('identificadores')]
    public function testRechazaIdentificadoresMalformadosSinConsultar($id): void
    {
        $response = $this->ejecutar(true, $id);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([1 => '2.00'], session('tienda_carrito'));
    }

    public static function identificadores(): array
    {
        return [[['1']], ['1 OR 1=1'], ['-1'], [str_repeat('9', 40)]];
    }
}
