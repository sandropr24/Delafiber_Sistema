<?php

use App\Services\LoginAttemptService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

final class LoginSecurityTest extends CIUnitTestCase
{
    private function servicio(): array
    {
        $s = (object) ['valores' => [], 'ahora' => 1000, 'ttl' => []];
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(fn($key) => $s->valores[$key] ?? null);
        $cache->method('save')->willReturnCallback(function ($key, $valor, $ttl) use ($s) {
            $s->valores[$key] = $valor; $s->ttl[] = $ttl; return true;
        });
        $cache->method('delete')->willReturnCallback(function ($key) use ($s) { unset($s->valores[$key]); return true; });
        return [new LoginAttemptService($cache, fn() => $s->ahora), $s, $cache];
    }

    public function testCincoFallosBloqueanUsuarioPeroNoOtroDesdeOtraIp(): void
    {
        [$servicio, $s] = $this->servicio();
        $clave = bin2hex(random_bytes(16));
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        for ($i = 0; $i < 4; $i++) $this->assertSame(LoginAttemptService::INVALIDO, $servicio->intentar('admin', 'uno', '192.0.2.1', '', $hash));
        $this->assertSame(LoginAttemptService::BLOQUEADO, $servicio->intentar('admin', 'uno', '192.0.2.1', '', $hash));
        $this->assertSame(LoginAttemptService::BLOQUEADO, $servicio->intentar('admin', 'uno', '192.0.2.2', $clave, $hash));
        $this->assertSame(LoginAttemptService::OK, $servicio->intentar('admin', 'otro', '192.0.2.2', $clave, $hash));
        foreach (array_keys($s->valores) as $key) $this->assertMatchesRegularExpression('/^login_fail_(ip|user)_[a-f0-9]{64}$/', $key);
    }

    public function testAccesoCorrectoNoConsumeCupoYLimpiaSoloElUsuario(): void
    {
        [$servicio, $s] = $this->servicio();
        $clave = bin2hex(random_bytes(16));
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $servicio->intentar('cliente', 'a@example.com', '192.0.2.1', '', $hash);
        $ipKey = 'login_fail_ip_' . hash('sha256', '192.0.2.1');
        $userKey = 'login_fail_user_' . hash('sha256', "cliente\0a@example.com");
        for ($i = 0; $i < 12; $i++) $this->assertSame(LoginAttemptService::OK, $servicio->intentar('cliente', 'a@example.com', '192.0.2.1', $clave, $hash));
        $this->assertSame(1, $s->valores[$ipKey]['cantidad']);
        $this->assertArrayNotHasKey($userKey, $s->valores);
        $this->assertSame([300, 300], $s->ttl);
    }

    public function testDiezFallosDeDistintosUsuariosBloqueanLaIpYExpiranEn300Segundos(): void
    {
        [$servicio, $s] = $this->servicio();
        for ($i = 0; $i < 10; $i++) $servicio->intentar('cliente', 'persona' . $i, '192.0.2.1', '', null);
        $this->assertSame(LoginAttemptService::BLOQUEADO, $servicio->intentar('cliente', 'otra', '192.0.2.1', '', null));
        $s->ahora += 300;
        $this->assertSame(LoginAttemptService::INVALIDO, $servicio->intentar('cliente', 'otra', '192.0.2.1', '', null));
    }

    public function testUsuarioInexistenteEjecutaVerificacionContraHashFicticio(): void
    {
        [, $s, $cache] = $this->servicio();
        $servicio = $this->getMockBuilder(LoginAttemptService::class)->setConstructorArgs([$cache, fn() => $s->ahora])->onlyMethods(['verificar'])->getMock();
        $servicio->expects($this->once())->method('verificar')->with('', LoginAttemptService::DUMMY_HASH)->willReturn(false);
        $this->assertSame(LoginAttemptService::INVALIDO, $servicio->intentar('admin', 'no-existe', '192.0.2.1', '', null));
    }

    public function testProxySoloConfiaEnLaListaConfigurada(): void
    {
        $antes = $_ENV['app.proxyIPs'] ?? null;
        try {
            $_ENV['app.proxyIPs'] = '192.0.2.10, 198.51.100.0/24';
            $app = new App();
            $this->assertSame(['192.0.2.10' => 'X-Forwarded-For', '198.51.100.0/24' => 'X-Forwarded-For'], $app->proxyIPs);
            $request = new IncomingRequest($app, new URI('http://example.com/'), null, new \CodeIgniter\HTTP\UserAgent());
            $request->setGlobal('server', ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_X_FORWARDED_FOR' => '203.0.113.8']);
            $request->setHeader('X-Forwarded-For', '203.0.113.8');
            $this->assertSame('203.0.113.8', $request->getIPAddress());
            $otra = new IncomingRequest($app, new URI('http://example.com/'), null, new \CodeIgniter\HTTP\UserAgent());
            $otra->setGlobal('server', ['REMOTE_ADDR' => '192.0.2.11', 'HTTP_X_FORWARDED_FOR' => '203.0.113.8']);
            $otra->setHeader('X-Forwarded-For', '203.0.113.8');
            $this->assertSame('192.0.2.11', $otra->getIPAddress());
        } finally {
            if ($antes === null) unset($_ENV['app.proxyIPs']); else $_ENV['app.proxyIPs'] = $antes;
        }
    }
}
