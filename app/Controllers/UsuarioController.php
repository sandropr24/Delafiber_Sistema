<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\PersonaModel;

class UsuarioController extends BaseController
{
    protected $usuarioModel;
    protected $personaModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->personaModel = new PersonaModel();
    }

    public function index()
    {
        $data = [
            'titulo'   => 'Usuarios y Personal',
            'usuarios' => $this->usuarioModel->obtenerUsuariosConPersona(),
        ];

        return view('usuarios/index', $data);
    }

    public function guardar()
    {
        $idUsuario = $this->request->getPost('idusuario');
        $idPersona = $this->request->getPost('idpersona');

        $db = \Config\Database::connect();
        $db->transStart();

        $datosPersona = [
            'nombres'   => trim((string) $this->request->getPost('nombres')),
            'apellidos' => trim((string) $this->request->getPost('apellidos')),
            'tipodoc'   => $this->request->getPost('tipodoc'),
            'numerodoc' => trim((string) $this->request->getPost('numerodoc')),
            'telefono'  => trim((string) $this->request->getPost('telefono')) ?: null,
            'email'     => trim((string) $this->request->getPost('email')) ?: null,
            'direccion' => trim((string) $this->request->getPost('direccion')) ?: null,
        ];

        if (!empty($idPersona)) {
            $datosPersona['idpersona'] = $idPersona;
            $this->personaModel->save($datosPersona);
        } else {
            $idPersona = $this->personaModel->insert($datosPersona);
        }

        $datosUsuario = [
            'idpersona'     => $idPersona,
            'nombreusuario' => trim((string) $this->request->getPost('nombreusuario')),
            'rol'           => $this->request->getPost('rol'),
            'estado'        => 1, // Usuario activo por defecto
        ];

        $clave = trim((string) $this->request->getPost('claveacceso'));

        if (!empty($idUsuario)) {
            $datosUsuario['idusuario'] = $idUsuario;
            if (!empty($clave)) {
                $datosUsuario['claveacceso'] = password_hash($clave, PASSWORD_BCRYPT);
            }
            $this->usuarioModel->save($datosUsuario);
        } else {
            if (empty($clave)) {
                $db->transRollback();
                return redirect()->to(base_url('usuarios'))
                                 ->withInput()
                                 ->with('error', 'La contraseña es obligatoria para nuevos usuarios.');
            }
            $datosUsuario['claveacceso'] = password_hash($clave, PASSWORD_BCRYPT);
            $this->usuarioModel->insert($datosUsuario);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to(base_url('usuarios'))
                             ->withInput()
                             ->with('error', 'Ocurrió un error al guardar la información del usuario.');
        }

        $mensaje = !empty($idUsuario) ? 'Usuario actualizado con éxito.' : 'Usuario registrado con éxito.';
        return redirect()->to(base_url('usuarios'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to(base_url('usuarios'))->with('error', 'El usuario no existe.');
        }

        if ((int) $usuario['idusuario'] === (int) session()->get('idusuario')) {
            return redirect()->to(base_url('usuarios'))->with('error', 'No puedes desactivar tu propia cuenta activa.');
        }

        $nuevoEstado = ((int) $usuario['estado'] === 1) ? 0 : 1;
        $this->usuarioModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('usuarios'))->with('mensaje', 'Estado del usuario actualizado.');
    }

    public function eliminar($id = null)
    {
        $db = \Config\Database::connect();

        $usuario = $db->table('usuarios')->where('idusuario', $id)->get()->getRowArray();

        if (!$usuario) {
            return redirect()->to(base_url('usuarios'))->with('error', 'El usuario no existe.');
        }

        if ((int) $usuario['idusuario'] === (int) session()->get('idusuario')) {
            return redirect()->to(base_url('usuarios'))->with('error', 'No puedes eliminar tu propia cuenta activa.');
        }

        $db->table('usuarios')->where('idusuario', $id)->delete();

        return redirect()->to(base_url('usuarios'))->with('mensaje', 'Usuario eliminado con éxito.');
    }
}