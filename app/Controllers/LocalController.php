<?php

namespace App\Controllers;

use App\Models\LocalModel;

class LocalController extends BaseController
{
    protected $localModel;

    public function __construct()
    {
        $this->localModel = new LocalModel();
    }

    public function index()
    {
        $data = [
            'titulo'  => 'Sedes y Locales',
            'locales' => $this->localModel->orderBy('nombrelocal', 'ASC')->findAll(),
        ];

        return view('locales/index', $data);
    }

    public function guardar()
    {
        $id = $this->request->getPost('idlocal');

        $datos = [
            'nombrelocal' => trim((string) $this->request->getPost('nombrelocal')),
            'direccion'   => trim((string) $this->request->getPost('direccion')),
            'tipolocal'   => trim((string) $this->request->getPost('tipolocal')),
        ];

        if (!empty($id)) {
            $datos['idlocal'] = $id;
        }

        if (!$this->localModel->save($datos)) {
            return redirect()->to(base_url('locales'))
                             ->withInput()
                             ->with('errors', $this->localModel->errors());
        }

        $mensaje = !empty($id) ? 'Local actualizado correctamente.' : 'Local registrado correctamente.';
        return redirect()->to(base_url('locales'))->with('mensaje', $mensaje);
    }

    public function eliminar($id = null)
    {
        if (!$id || !$this->localModel->find($id)) {
            return redirect()->to(base_url('locales'))->with('error', 'El local no existe.');
        }

        $db = \Config\Database::connect();
        
        //Validacion para evitar la eliminacion de un local si ya tiene movimiento
        $enKardex = $db->table('kardex')->where('idlocal', $id)->countAllResults();
        if ($enKardex > 0) {
            return redirect()->to(base_url('locales'))->with('error', 'No se puede eliminar: el local tiene movimientos de inventario registrados.');
        }

        $this->localModel->delete($id);
        return redirect()->to(base_url('locales'))->with('mensaje', 'Local eliminado correctamente.');
    }
}