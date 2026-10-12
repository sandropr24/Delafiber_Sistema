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
        }

        if (!$this->personaModel->save($datosPersona)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->personaModel->errors());
        }

        $mensaje = !empty($idPersona) ? 'Cliente actualizado con éxito.' : 'Cliente registrado con éxito.';
        return redirect()->to(base_url('clientes'))->with('mensaje', $mensaje);
    }

    public function eliminar($id = null): RedirectResponse
    {
        if (empty($id) || !$this->personaModel->find($id)) {
            return redirect()->to(base_url('clientes'))->with('error', 'El cliente no existe.');
        }

        $db = $this->personaModel->builder()->db();

        $esUsuario = $db->table('usuarios')->where('idpersona', $id)->countAllResults();
        if ($esUsuario > 0) {
            return redirect()->to(base_url('clientes'))->with('error', 'No se puede eliminar: esta persona está asignada como usuario del sistema.');
        }

        if ($db->tableExists('ventas')) {
            $columnaVentas = $db->fieldExists('idcliente', 'ventas') ? 'idcliente' : ($db->fieldExists('idpersona', 'ventas') ? 'idpersona' : null);

            if ($columnaVentas !== null) {
                $tieneVentas = $db->table('ventas')->where($columnaVentas, $id)->countAllResults();
                if ($tieneVentas > 0) {
                    return redirect()->to(base_url('clientes'))->with('error', 'No se puede eliminar: el cliente registra compras o comprobantes.');
                }
            }
        }

        $this->personaModel->delete($id);

        return redirect()->to(base_url('clientes'))->with('mensaje', 'Cliente eliminado con éxito.');
    }
}
