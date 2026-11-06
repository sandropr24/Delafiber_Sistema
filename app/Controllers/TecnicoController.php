<?php

namespace App\Controllers;

use App\Models\TecnicoModel;
use CodeIgniter\HTTP\RedirectResponse;

class TecnicoController extends BaseController
{
    private TecnicoModel $tecnicos;

    public function __construct()
    {
        $this->tecnicos = model(TecnicoModel::class);
    }

    private function autorizado(): bool
    {
        return session()->get('rol') === 'Administrador';
    }

    public function index()
    {
        if (!$this->autorizado()) return redirect()->to('dashboard')->with('error', 'Acceso no autorizado.');
        return view('tecnicos/index', [
            'titulo' => 'Gestión de Técnicos',
            'tecnicos' => $this->tecnicos->orderBy('idtecnico', 'DESC')->findAll(),
        ]);
    }

    public function guardar(): RedirectResponse
    {
        if (!$this->autorizado()) return redirect()->to('dashboard')->with('error', 'Acceso no autorizado.');

        $id = (int) $this->request->getPost('idtecnico');
        $actual = $id ? $this->tecnicos->find($id) : null;
        if ($id && !$actual) return redirect()->to('tecnicos')->with('error', 'El técnico no existe.');

        $datos = [
            'nombres' => trim((string) $this->request->getPost('nombres')),
            'apellidos' => trim((string) $this->request->getPost('apellidos')),
            'dni' => trim((string) $this->request->getPost('dni')),
            'telefono' => trim((string) $this->request->getPost('telefono')),
            'especialidad' => trim((string) $this->request->getPost('especialidad')),
            'estado' => $actual['estado'] ?? 'ACTIVO',
        ];
        $reglas = [
            'nombres' => 'required|max_length[100]',
            'apellidos' => 'required|max_length[100]',
            'dni' => 'required|regex_match[/^[0-9]{8}$/]|is_unique[tecnicos.dni,idtecnico,' . ($id ?: 'NULL') . ']',
            'telefono' => 'permit_empty|regex_match[/^[0-9]{9}$/]',
            'especialidad' => 'permit_empty|max_length[100]',
        ];
        if (!$this->validateData($datos, $reglas)) {
            return redirect()->to('tecnicos')->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($id) $datos['idtecnico'] = $id;
        if (!$this->tecnicos->save($datos)) {
            return redirect()->to('tecnicos')->withInput()->with('errors', $this->tecnicos->errors());
        }
        return redirect()->to('tecnicos')->with('mensaje', $id ? 'Técnico actualizado correctamente.' : 'Técnico registrado correctamente.');
    }

    public function cambiarEstado(int $id): RedirectResponse
    {
        if (!$this->autorizado()) return redirect()->to('dashboard')->with('error', 'Acceso no autorizado.');
        $tecnico = $this->tecnicos->find($id);
        if (!$tecnico) return redirect()->to('tecnicos')->with('error', 'El técnico no existe.');
        $nuevo = $tecnico['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        if (!$this->tecnicos->update($id, ['estado' => $nuevo])) {
            return redirect()->to('tecnicos')->with('error', 'No se pudo actualizar el estado.');
        }
        return redirect()->to('tecnicos')->with('mensaje', 'Estado actualizado correctamente.');
    }
}
