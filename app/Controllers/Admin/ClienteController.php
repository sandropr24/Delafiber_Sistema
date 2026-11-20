<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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
            'formCliente' => $this->formularioAnterior(),
        ];

        return view('clientes/index', $data);
    }

    public function guardar(): RedirectResponse
    {
        $idPersonaRaw = trim((string) $this->request->getPost('idpersona'));
        if ($idPersonaRaw !== '' && !ctype_digit($idPersonaRaw)) {
            return redirect()->to(base_url('admin/clientes'))->with('error', 'El cliente indicado no es válido.');
        }

        $idPersona = $idPersonaRaw === '' ? null : (int) $idPersonaRaw;
        if ($idPersona !== null && !$this->esClienteEditable($idPersona)) {
            return redirect()->to(base_url('admin/clientes'))->with('error', 'El cliente no existe o no puede modificarse.');
        }

        try {
            $datosPersona = $this->personaModel->datosCliente($this->request->getPost());
        } catch (\InvalidArgumentException $e) {
            return redirect()->to(base_url('admin/clientes'))->withInput()->with('errors', ['datos' => $e->getMessage()]);
        }

        $reglas = $this->personaModel->reglasCliente((string) $datosPersona['tipodoc']);
        // Se conserva la política existente de no repetir documentos en el directorio.
        $reglas['numerodoc'] .= '|is_unique[personas.numerodoc,idpersona,' . ($idPersona ?: 'NULL') . ']';

        $mensajes = [
            'numerodoc' => [
                'is_unique' => 'El número de documento ya se encuentra registrado.',
                'regex_match' => 'Ingrese un DNI de 8 dígitos, RUC de 11 dígitos o documento extranjero de 5 a 20 caracteres alfanuméricos.',
            ],
            'nombres' => ['required' => 'Ingrese los nombres del cliente.'],
            'apellidos' => ['required' => 'Ingrese los apellidos del cliente.'],
            'razonsocial' => ['required' => 'Ingrese la razón social de la empresa.'],
        ];

        if (!$this->validateData($datosPersona, $reglas, $mensajes)) {
            return redirect()->to(base_url('admin/clientes'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        if (!empty($idPersona)) {
            $datosPersona['idpersona'] = $idPersona;
        } else {
            $datosPersona['estado'] = 1;
        }

        if (!$this->personaModel->save($datosPersona)) {
            return redirect()->to(base_url('admin/clientes'))
                ->withInput()
                ->with('errors', $this->personaModel->errors());
        }

        $mensaje = !empty($idPersona) ? 'Cliente actualizado con éxito.' : 'Cliente registrado con éxito.';
        return redirect()->to(base_url('admin/clientes'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !$this->esClienteEditable($id) || !$cliente = $this->personaModel->find($id)) {
            return redirect()->to(base_url('admin/clientes'))->with('error', 'El cliente no existe.');
        }

        $nuevoEstado = ((int) ($cliente['estado'] ?? 1) === 1) ? 0 : 1;
        $this->personaModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('admin/clientes'))->with('mensaje', 'Estado del cliente actualizado correctamente.');
    }

    private function esClienteEditable(int $idPersona): bool
    {
        if (!$this->personaModel->find($idPersona)) {
            return false;
        }

        return $this->personaModel->builder()->db()
            ->table('usuarios')
            ->where('idpersona', $idPersona)
            ->countAllResults() === 0;
    }

    private function formularioAnterior(): array
    {
        $datos = session()->get('_ci_old_input')['post'] ?? [];
        if (($datos['origen_formulario'] ?? '') !== 'cliente') {
            return [];
        }

        return array_filter(
            array_intersect_key($datos, array_flip(['idpersona', 'tipodoc', 'numerodoc', 'nombres', 'apellidos', 'razonsocial', 'telefono', 'email', 'direccion'])),
            static fn($valor) => is_scalar($valor)
        );
    }
}
