<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Throttle\ThrottlerInterface;

class ThrottleFilter implements FilterInterface
{
    public function __construct(private ?ThrottlerInterface $throttler = null) {}

    public function before(RequestInterface $request, $arguments = null)
    {
        [$clave, $maximo, $ventana] = array_pad($arguments ?? [], 3, null);
        if (!is_string($clave) || !ctype_digit((string) $maximo) || !ctype_digit((string) $ventana)
            || (int) $maximo < 1 || (int) $ventana < 1) throw new \LogicException('Configura throttle:clave,máximo,ventana[,email].');
        $sujeto = $request->getIPAddress();
        if (($arguments[3] ?? '') === 'email') {
            $email = $request->getPost('email');
            if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) return null;
            $sujeto = mb_strtolower(trim($email));
        }
        $throttler = $this->throttler ?? service('throttler');
        if ($throttler->check('web_' . hash('sha256', $clave . "\0" . $sujeto), (int) $maximo, (int) $ventana)) return null;
        $espera = max(1, $throttler->getTokenTime());
        $mensaje = 'Demasiadas solicitudes. Espera unos minutos antes de volver a intentarlo.';
        if ($request->isAJAX()) {
            return service('response')->setStatusCode(429)->setHeader('Retry-After', (string) $espera)
                ->setHeader('Cache-Control', 'no-store')->setJSON([
                    'success' => false, 'mensaje' => $mensaje,
                    'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
        }
        $path = $request->getUri()->getPath();
        $volver = str_contains($path, 'cliente/registro') ? 'cliente/registro' : (str_contains($path, 'checkout') ? 'checkout' : 'carrito');
        return redirect()->to(base_url($volver), 303)->with('error', $mensaje)->setHeader('Retry-After', (string) $espera);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
