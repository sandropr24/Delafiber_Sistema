<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MarcaModel;
use CodeIgniter\HTTP\ResponseInterface;

class MarcaController extends BaseController
{

    protected $marcaModel;

    public function __construct()
    {
        $this->marcaModel = model(MarcaModel::class);
    }

    public function index()
    {
        $data = [
            'titulo' => 'Marcas',
            'marcas' => $this->marcaModel->orderBy('marca', 'ASC')->findAll(),
        ];

        return view('marcas/index', $data);
    }

    public function guardar()
    {
        $id = $this->request->getPost('idmarca');

        $datos = [
            'marca' => trim((string) $this->request->getPost('marca'))
        ];

        if ($id) {
            $datos['idmarca'] = $id;
        }

        if (! $this->marcaModel->save($datos)) {
            return redirect()->back()->withInput()->with('errors', $this->marcaModel->errors());
        }

        $mensaje = $id ? 'Marca actualizada con exito.' : 'Marca registrada exitosamente';
        return redirect()->to('/marcas')->with('mensaje', $mensaje);
    }


    public function eliminar($id = null)
    {
        if (! $id || ! $this->marcaModel->find($id)) {
            return redirect()->to('/marcas')->with('error', 'La marca no existe');
        }

        $productosAsociados = $this->marcaModel->builder('productos')->where('idmarca', $id)->countAllResults();

        if ($productosAsociados > 0) {
            return redirect()->to('/marcas')->with('error', 'No se pudo eliminar: existen productos asociados a esta marca ');
        }

        $this->marcaModel->delete($id);
        return redirect()->to('/marcas')->with('mensaje', 'Marca eliminado correctamente');
    }
}
