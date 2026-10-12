<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\PersonaModel;
use CodeIgniter\HTTP\RedirectResponse;

class UsuarioController extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected PersonaModel $personaModel;

    public function __construct()
    {
        $this->usuarioModel = model(UsuarioModel::class);
        $this->personaModel = model(PersonaModel::class);
    }

    public function index(): string
    {
        $data = [
            'titulo'   => 'Usuarios y Personal',
            'usuarios' => $this->usuarioModel->obtenerUsuariosConPersona(),
        ];

        return view('usuarios/index', $data);
    }

    public function guardar(): RedirectResponse
    {
        $idUsuario = $this->request->getPost('idusuario');
        $idPersona = $this->request->getPost('idpersona');
        $clave = trim((string) $this->request->getPost('claveacceso'));

        $reglas = [
            'nombres'       => 'required|max_length[100]',
            'apellidos'     => 'required|max_length[100]',
            'tipodoc'       => 'required',
            'numerodoc'     => 'required|max_length[20]|is_unique[personas.numerodoc,idpersona,' . ($idPersona ?: 'NULL') . ']',
            'nombreusuario' => 'required|max_length[50]|is_unique[usuarios.nombreusuario,idusuario,' . ($idUsuario ?: 'NULL') . ']',
            'rol'           => 'required',
            'email'         => 'permit_empty|valid_email|max_length[100]',
            'claveacceso'   => empty($idUsuario) ? 'required|min_length[4]' : 'permit_empty|min_length[4]',
        ];

        $mensajes = [
            'numerodoc' => ['is_unique' => 'El número de documento ya se encuentra registrado.'],
            'nombreusuario' => ['is_unique' => 'El nombre de usuario ya está en uso.'],
            'claveacceso' => [
                'required' => 'La contraseña es obligatoria para nuevos usuarios.',
                'min_length' => 'La contraseña debe tener al menos 4 caracteres.'
            ]
        ];

        if (!$this->validate($reglas, $mensajes)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = $this->usuarioModel->builder()->db();
        $db->transStart();

        $camposPersona = ['nombres', 'apellidos', 'tipodoc', 'numerodoc', 'telefono', 'email', 'direccion'];
        $datosPersona = $this->request->getPost($camposPersona);
        $datosPersona = array_map(static function ($valor) {
            $limpio = trim((string) $valor);
            return $limpio === '' ? null : $limpio;
        }, $datosPersona);

        if (!empty($idPersona)) {
            $datosPersona['idpersona'] = $idPersona;
        }

        $this->personaModel->save($datosPersona);

        if (empty($idPersona)) {
            $idPersona = $this->personaModel->getInsertID();
        }

        $datosUsuario = [
            'idpersona'     => $idPersona,
            'nombreusuario' => trim((string) $this->request->getPost('nombreusuario')),
            'rol'           => $this->request->getPost('rol'),
            'estado'        => 1,
        ];

        if (!empty($idUsuario)) {
            $datosUsuario['idusuario'] = $idUsuario;
        }

        if (!empty($clave)) {
            $datosUsuario['claveacceso'] = password_hash($clave, PASSWORD_BCRYPT);
        }

        $this->usuarioModel->save($datosUsuario);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al guardar la información del usuario.');
        }

        $mensaje = !empty($idUsuario) ? 'Usuario actualizado con éxito.' : 'Usuario registrado con éxito.';
        return redirect()->to(base_url('usuarios'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        if (empty($id) || !$usuario = $this->usuarioModel->find($id)) {
            return redirect()->to(base_url('usuarios'))->with('error', 'El usuario no existe.');
        }

        if ((int) $usuario['idusuario'] === (int) session()->get('idusuario')) {
            return redirect()->to(base_url('usuarios'))->with('error', 'No puedes desactivar tu propia cuenta activa.');
        }

        $nuevoEstado = ((int) $usuario['estado'] === 1) ? 0 : 1;
        $this->usuarioModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('usuarios'))->with('mensaje', 'Estado del usuario actualizado.');
    }

    public function eliminar($id = null): RedirectResponse
    {
        if (empty($id) || !$this->usuarioModel->find($id)) {
            return redirect()->to(base_url('usuarios'))->with('error', 'El usuario no existe.');
        }

        if ((int) $id === (int) session()->get('idusuario')) {
            return redirect()->to(base_url('usuarios'))->with('error', 'No puedes eliminar tu propia cuenta activa.');
        }

        $this->usuarioModel->delete($id);

        return redirect()->to(base_url('usuarios'))->with('mensaje', 'Usuario eliminado con éxito.');
    }
}
