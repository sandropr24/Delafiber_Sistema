<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('idusuario')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function autenticar()
    {
        // Limita los intentos
        $throttler = \Config\Services::throttler();
        if ($throttler->check('login_' . md5($this->request->getIPAddress()), 5, 300) === false) {
            return redirect()->back()->with('error', 'Demasiados intentos. Espere unos minutos.');
        }

        // Validar que lleguen los datos
        if (! $this->validate([
            'nombreusuario' => 'required|max_length[50]',
            'claveacceso'   => 'required|max_length[72]',
        ])) {
            return redirect()->back()->withInput()->with('error', 'Complete usuario y contraseña.');
        }

        $usuario = (new UsuarioModel())->buscarPorNombre($this->request->getPost('nombreusuario'));
        $clave   = (string) $this->request->getPost('claveacceso');

        if (! $usuario || (int) $usuario['estado'] !== 1 || ! password_verify($clave, $usuario['claveacceso'])) {
            return redirect()->back()->withInput()->with('error', 'Usuario o contraseña incorrectos.');
        }

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

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
