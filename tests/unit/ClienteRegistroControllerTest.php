<?php

use App\Controllers\TiendaController;
use App\Services\ClienteRegistroService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ClienteRegistroControllerTest extends CIUnitTestCase
{
    #[DataProvider('resultados')]
    public function testRegistroSiempreRespondeIgualSinAutenticarNiGuardarPassword(bool $resultado): void
    {
        helper(['url', 'form']);
        session()->remove(['cliente_web', '_ci_old_input']);
        $servicio = $this->getMockBuilder(ClienteRegistroService::class)->disableOriginalConstructor()->onlyMethods(['registrar'])->getMock();
        $servicio->expects($this->once())->method('registrar')->with([])->willReturn($resultado);
        $controller = $this->getMockBuilder(TiendaController::class)->onlyMethods(['registroClientes'])->getMock();
        $controller->method('registroClientes')->willReturn($servicio);
        $request = $this->getMockBuilder(IncomingRequest::class)->disableOriginalConstructor()->onlyMethods(['getPost'])->getMock();
        $request->method('getPost')->willReturn([]);
        $controller->initController($request, service('response'), service('logger'));
        $r = $controller->registrar();
        $this->assertStringEndsWith('/cliente/registro', $r->getHeaderLine('Location'));
        $this->assertSame(ClienteRegistroService::MENSAJE, session()->getFlashdata('mensaje'));
        $this->assertFalse(session()->has('cliente_web'));
        $this->assertFalse(session()->has('_ci_old_input'));
        session()->remove('mensaje');
    }

    public static function resultados(): array { return [[true], [false]]; }
}
