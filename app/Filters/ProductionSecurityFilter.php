<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;
use Config\Cookie;
use Psr\Log\LoggerInterface;

class ProductionSecurityFilter implements FilterInterface
{
    public function __construct(
        private ?App $app = null,
        private ?Cookie $cookie = null,
        private ?LoggerInterface $logger = null,
        private ?string $environment = null
    ) {}

    public function before(RequestInterface $request, $arguments = null)
    {
        if (($this->environment ?? ENVIRONMENT) !== 'production') return null;
        $app = $this->app ?? config(App::class);
        $cookie = $this->cookie ?? config(Cookie::class);
        if (strtolower((string) parse_url($app->baseURL, PHP_URL_SCHEME)) === 'https'
            && parse_url($app->baseURL, PHP_URL_HOST) && $cookie->secure && $app->forceGlobalSecureRequests) return null;

        ($this->logger ?? service('logger'))->critical('Inicio bloqueado: producción exige baseURL HTTPS, cookie.secure y forceGlobalSecureRequests.');
        return service('response')->setStatusCode(500)->setHeader('Cache-Control', 'no-store')
            ->setBody('Servicio no disponible por configuración de seguridad. Contacta al administrador.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
