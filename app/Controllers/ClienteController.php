<?php

namespace App\Controllers;

use App\Models\PersonaModel;
use CodeIgniter\HTTP\RedirectResponse;

class ClienteController extends BaseController
{
    protected PersonaModel $personaModel;

    public function __construct()
    {
        $this->personaModel = model(PersonaModel::class);
    }

    public function index(): string
    {
        $clientes = $this->personaModel->builder()
            ->whereNotIn('idpersona', static function ($builder) {
                return $builder->select('idpersona')->from('usuarios');
            })
            ->orderBy('idpersona', 'DESC')
            ->get()
            ->getResultArray();

        $data = [
            'titulo'   => 'Gestión de Clientes',
            'clientes' => $clientes,
        ];

        return view('clientes/index', $data);
    }

    public function guardar(): RedirectResponse
    {
        $idPersona = $this->request->getPost('idpersona');

        $reglas = [
            'nombres'   => 'required|max_length[100]',
            'apellidos' => 'required|max_length[100]',
            'tipodoc'   => 'required',
            'numerodoc' => 'required|max_length[20]|is_unique[personas.numerodoc,idpersona,' . ($idPersona ?: 'NULL') . ']',
            'email'     => 'permit_empty|valid_email|max_length[150]',
        ];

        $mensajes = [
            'numerodoc' => [
                'is_unique' => 'El número de documento ya se encuentra registrado.'
            ]
        ];

        if (!$this->validate($reglas, $mensajes)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $campos = ['nombres', 'apellidos', 'tipodoc', 'numerodoc', 'telefono', 'email', 'direccion'];
        $datosPersona = $this->request->getPost($campos);

        $datosPersona = array_map(static function ($valor) {
            $limpio = trim((string) $valor);
            return $limpio === '' ? null : $limpio;
        }, $datosPersona);

        if (!empty($idPersona)) {
            $datosPersona['idpersona'] = $idPersona;
        } else {
            $datosPersona['estado'] = 1;
        }

        if (!$this->personaModel->save($datosPersona)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->personaModel->errors());
        }

        $mensaje = !empty($idPersona) ? 'Cliente actualizado con éxito.' : 'Cliente registrado con éxito.';
        return redirect()->to(base_url('clientes'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        if (empty($id) || !$cliente = $this->personaModel->find($id)) {
            return redirect()->to(base_url('clientes'))->with('error', 'El cliente no existe.');
        }

        $nuevoEstado = ((int) ($cliente['estado'] ?? 1) === 1) ? 0 : 1;
        $this->personaModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('clientes'))->with('mensaje', 'Estado del cliente actualizado correctamente.');
    }
}
