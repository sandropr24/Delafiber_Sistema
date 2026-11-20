<?php

use App\Controllers\Admin\Auth;
use App\Controllers\TiendaController;
use App\Models\UsuarioModel;
use App\Services\LoginAttemptService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Libraries\MemoryDatabaseTrait;

final class LoginControllersTest extends CIUnitTestCase
{
    use MemoryDatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
        session()->remove(['idusuario', 'cliente_web', '_ci_old_input']);
    }

    protected function tearDown(): void
    {
        session()->remove(['idusuario', 'cliente_web', 'error', '_ci_old_input', 'rol', 'isLoggedIn']);
        parent::tearDown();
    }

    private function request(array $post): IncomingRequest
    {
        $r = $this->getMockBuilder(IncomingRequest::class)->disableOriginalConstructor()->onlyMethods(['getPost', 'getIPAddress'])->getMock();
        $r->method('getPost')->willReturnCallback(fn($k) => $post[$k] ?? null);
        $r->method('getIPAddress')->willReturn('192.0.2.1');
        return $r;
    }

    public function testAdminConservaSesionDeExitoYFalloSinOldInput(): void
    {
        foreach ([LoginAttemptService::OK, LoginAttemptService::INVALIDO] as $resultado) {
            session()->remove('idusuario');
            $usuario = ['idusuario' => 2, 'nombreusuario' => 'admin_prueba', 'estado' => 1, 'claveacceso' => LoginAttemptService::DUMMY_HASH, 'nombres' => 'Administrador', 'apellidos' => 'Prueba', 'rol' => 'Administrador'];
            $modelo = $this->getMockBuilder(UsuarioModel::class)->disableOriginalConstructor()->onlyMethods(['BuscarPorNombre'])->getMock();
            $modelo->method('BuscarPorNombre')->willReturn($usuario);
            $intentos = $this->getMockBuilder(LoginAttemptService::class)->disableOriginalConstructor()->onlyMethods(['intentar'])->getMock();
            $intentos->expects($this->once())->method('intentar')->with('admin', 'admin_prueba', '192.0.2.1', '', LoginAttemptService::DUMMY_HASH, true)->willReturn($resultado);
            $controller = $this->getMockBuilder(Auth::class)->onlyMethods(['usuariosLogin', 'intentosLogin'])->getMock();
            $controller->method('usuariosLogin')->willReturn($modelo);
            $controller->method('intentosLogin')->willReturn($intentos);
            $controller->initController($this->request(['nombreusuario' => 'admin_prueba', 'claveacceso' => '']), service('response'), service('logger'));
            $r = $controller->autenticar();
            $this->assertStringEndsWith($resultado === LoginAttemptService::OK ? '/admin/dashboard' : '/admin/login', $r->getHeaderLine('Location'));
            $this->assertSame($resultado === LoginAttemptService::OK, session()->has('idusuario'));
            $this->assertFalse(session()->has('_ci_old_input'));
        }
    }

    public function testCuentaPendienteNoIniciaSesionAunqueLaClaveCoincida(): void
    {
        $intentos = $this->getMockBuilder(LoginAttemptService::class)->disableOriginalConstructor()->onlyMethods(['intentar'])->getMock();
        $intentos->expects($this->once())->method('intentar')->with('cliente', 'a@example.com', '192.0.2.1', '', LoginAttemptService::DUMMY_HASH, true)->willReturn(LoginAttemptService::OK);
        $controller = $this->getMockBuilder(TiendaController::class)->onlyMethods(['cuentaLogin', 'intentosLogin'])->getMock();
        $controller->method('cuentaLogin')->willReturn(['idcliente_cuenta' => 1, 'email' => 'a@example.com', 'activo' => 0, 'email_verificado_en' => null, 'verificacion_token_hash' => str_repeat('a', 64), 'clave_hash' => LoginAttemptService::DUMMY_HASH]);
        $controller->method('intentosLogin')->willReturn($intentos);
        $controller->initController($this->request(['email' => 'A@example.com', 'clave' => '']), service('response'), service('logger'));
        $controller->autenticar();
        $this->assertFalse(session()->has('cliente_web'));
        $this->assertStringContainsString('verificar tu correo', session()->getFlashdata('error'));
        $this->assertFalse(session()->has('_ci_old_input'));
    }
}
