<?php

use App\Filters\ProductionSecurityFilter;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Cookie;
use Config\Filters;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

final class ProductionSecurityTest extends CIUnitTestCase
{
    #[DataProvider('configuraciones')]
    public function testBloqueaSoloProduccionInsegura(string $entorno, string $url, bool $segura, bool $forzar, bool $bloquear): void
    {
        $app = new App();
        $app->baseURL = $url;
        $app->forceGlobalSecureRequests = $forzar;
        $cookie = new Cookie();
        $cookie->secure = $segura;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($bloquear ? $this->once() : $this->never())->method('critical');
        $respuesta = (new ProductionSecurityFilter($app, $cookie, $logger, $entorno))->before($this->createMock(RequestInterface::class));
        if ($bloquear) $this->assertSame(500, $respuesta->getStatusCode());
        else $this->assertNull($respuesta);
    }

    public static function configuraciones(): array
    {
        return [
            ['production', 'http://example.com/', true, true, true],
            ['production', 'https://example.com/', false, true, true],
            ['production', 'https://example.com/', true, false, true],
            ['production', 'https://example.com/', true, true, false],
            ['testing', 'http://example.com/', false, false, false],
            ['development', 'http://example.com/', false, false, false],
        ];
    }

    public function testGuardiaSeEjecutaAntesDeHttpsYEnvNoContieneElSecretoInicial(): void
    {
        $this->assertSame('productionsecurity', (new Filters())->required['before'][0]);
        $this->assertContains('.env', file(ROOTPATH . '.gitignore', FILE_IGNORE_NEW_LINES));
        $contenido = file_get_contents(ROOTPATH . '.env');
        // No pasar el contenido ni el secreto a una aserción que pueda imprimirlo.
        $this->assertFalse((bool) preg_match('/^\s*seed\.adminPassword\s*=/m', $contenido));
        $this->assertTrue((bool) preg_match('/^# seed\.adminPassword =\s*$/m', $contenido));
    }
}
