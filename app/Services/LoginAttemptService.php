<?php

namespace App\Services;

use CodeIgniter\Cache\CacheInterface;

class LoginAttemptService
{
    // Hash ficticio público; no corresponde a una cuenta de la aplicación.
    public const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
    public const OK = 'OK';
    public const INVALIDO = 'INVALIDO';
    public const BLOQUEADO = 'BLOQUEADO';

    public function __construct(private ?CacheInterface $cache = null, private ?\Closure $reloj = null)
    {
        $this->cache ??= service('cache');
        $this->reloj ??= static fn() => time();
    }

    protected function verificar(string $clave, string $hash): bool
    {
        return password_verify($clave, $hash);
    }

    public function intentar(string $ambito, string $identificador, string $ip, string $clave, ?string $hash, bool $habilitado = true): string
    {
        // También se ejecuta para identidades inexistentes y bloqueadas.
        $coincide = $this->verificar($clave, $hash ?? self::DUMMY_HASH);
        $ipKey = 'login_fail_ip_' . hash('sha256', $ip);
        $userKey = 'login_fail_user_' . hash('sha256', $ambito . "\0" . mb_strtolower(trim($identificador)));
        $ahora = ($this->reloj)();
        $leer = function ($key) use ($ahora) {
            $c = $this->cache->get($key);
            return is_array($c) && ($c['hasta'] ?? 0) > $ahora ? $c : ['cantidad' => 0, 'hasta' => $ahora + 300];
        };
        $porIp = $leer($ipKey);
        $porUsuario = $leer($userKey);
        if ($porIp['cantidad'] >= 10 || $porUsuario['cantidad'] >= 5) return self::BLOQUEADO;
        if ($hash !== null && $coincide && $habilitado) {
            $this->cache->delete($userKey);
            return self::OK;
        }
        foreach ([$ipKey => $porIp, $userKey => $porUsuario] as $key => $contador) {
            $contador['cantidad']++;
            if (!$this->cache->save($key, $contador, max(1, $contador['hasta'] - $ahora))) {
                throw new \RuntimeException('No está disponible el control de intentos de acceso.');
            }
        }
        return $porIp['cantidad'] + 1 >= 10 || $porUsuario['cantidad'] + 1 >= 5 ? self::BLOQUEADO : self::INVALIDO;
    }
}
