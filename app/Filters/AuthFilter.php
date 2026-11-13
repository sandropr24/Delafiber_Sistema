<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {

        $idUsuario = session()->get('idusuario');

        if (! $idUsuario) {
            return redirect()->to('admin/login');
        }

        $db = \Config\Database::connect();
        $usuarioActual = $db->table('usuarios u')
            ->select('u.estado, r.rol')
            ->join('roles r', 'r.idrol = u.idrol')
            ->where('u.idusuario', (int) $idUsuario)
            ->get()
            ->getRowArray();

        if (! $usuarioActual || (int) $usuarioActual['estado'] !== 1) {
            session()->destroy();
            return redirect()->to('/admin/login')->with('error', 'Tu cuenta ha sido desactivada o no existe.');
        }

        $rolActual = $usuarioActual['rol'];
        if (session()->get('rol') !== $rolActual) {
            session()->set('rol', $rolActual);
        }

        if (! empty($arguments) && ! in_array($rolActual, $arguments, true)) {
            return redirect()->to('/admin/dashboard')->with('error', 'No tiene permiso para esa sección.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
