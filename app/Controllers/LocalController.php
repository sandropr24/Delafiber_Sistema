<?php

namespace App\Controllers;

use App\Models\LocalModel;
use CodeIgniter\HTTP\RedirectResponse;

class LocalController extends BaseController
{
    protected LocalModel $localModel;

    public function __construct()
    {
        $this->localModel = model(LocalModel::class);
    }

    public function index(): string
    {
        $data = [
            'titulo'  => 'Sedes y Locales',
            'locales' => $this->localModel->orderBy('nombrelocal', 'ASC')->findAll(),
        ];

        return view('locales/index', $data);
    }

    public function guardar(): RedirectResponse
    {

        $reglas = [
            'nombrelocal' => 'required|min_length[3]|max_length[255]',
            'direccion'   => 'required|max_length[255]',
            'tipolocal'   => 'required|max_length[100]',
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $id = $this->request->getPost('idlocal');
        $datos = $this->request->getPost(['nombrelocal', 'direccion', 'tipolocal']);
        $datos = array_map('trim', $datos);

        if (!empty($id)) {
            $datos['idlocal'] = $id;
        }

        if (!$this->localModel->save($datos)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->localModel->errors());
        }

        $mensaje = !empty($id) ? 'Local actualizado correctamente.' : 'Local registrado correctamente.';
        return redirect()->to(base_url('locales'))->with('mensaje', $mensaje);
    }

    public function eliminar($id = null): RedirectResponse
    {
        if (empty($id) || !$this->localModel->find($id)) {
            return redirect()->to(base_url('locales'))->with('error', 'El local no existe.');
        }

        $db = $this->localModel->builder()->db();

        $enKardex = $db->table('kardex')->where('idlocal', $id)->countAllResults();
        if ($enKardex > 0) {
            return redirect()->to(base_url('locales'))
                ->with('error', 'No se puede eliminar: el local tiene movimientos de inventario registrados.');
        }

        try {
            $this->localModel->delete($id);
            return redirect()->to(base_url('locales'))->with('mensaje', 'Local eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->to(base_url('locales'))->with('error', 'Ocurrió un error al intentar eliminar el local.');
        }
    }
}
