<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('idusuario')) {
            return redirect()->to('admin/login');
        }

        if (! empty($arguments) && ! in_array(session()->get('rol'), $arguments, true)) {
            return redirect()->to('/admin/dashboard')->with('error', 'No tiene permiso para esa sección.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
