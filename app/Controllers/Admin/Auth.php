<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Services\LoginAttemptService;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('idusuario')) {
           return redirect()->to('/admin/dashboard');
        }
        return view('auth/login');
    }

    public function autenticar()
    {
        $nombre = $this->request->getPost('nombreusuario');
        $clave = $this->request->getPost('claveacceso');
        $nombre = is_string($nombre) ? trim($nombre) : '';
        $clave = is_string($clave) ? $clave : '';
        $usuario = $nombre !== '' && strlen($nombre) <= 50 && strlen($clave) <= 72 ? $this->usuariosLogin()->buscarPorNombre($nombre) : null;
        $resultado = $this->intentosLogin()->intentar('admin', $nombre, $this->request->getIPAddress(), $clave, $usuario['claveacceso'] ?? null, (int) ($usuario['estado'] ?? 0) === 1);
        if ($resultado !== LoginAttemptService::OK) return redirect()->to(base_url('admin/login'))->with('error',
            $resultado === LoginAttemptService::BLOQUEADO ? 'Demasiados intentos fallidos. Espera cinco minutos.' : 'Usuario o contraseña incorrectos.');

        session()->regenerate(true);
        session()->set([
            'idusuario'     => $usuario['idusuario'],
            'nombreusuario' => $usuario['nombreusuario'],
            'nombres'       => $usuario['nombres'],
            'apellidos'     => $usuario['apellidos'],
            'nombre'        => $usuario['nombres'] . ' ' . $usuario['apellidos'],
            'email'         => $usuario['email'] ?? '',
            'rol'           => $usuario['rol'],
            'isLoggedIn'    => true,
        ]);

        return redirect()->to('/admin/dashboard');
    }

    protected function usuariosLogin(): UsuarioModel { return new UsuarioModel(); }
    protected function intentosLogin(): LoginAttemptService { return new LoginAttemptService(); }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/admin/login');
    }
}
