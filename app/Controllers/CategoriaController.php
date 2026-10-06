<?php

namespace App\Controllers;

use App\Models\CategoriaModel;

class CategoriaController extends BaseController
{
    protected $categoriaModel;

    public function __construct()
    {
        $this->categoriaModel = model(CategoriaModel::class);
    }

    public function index()
    {
        $data = [
            'titulo' => 'Gestion de Categorias',
            'categorias' => $this->categoriaModel->orderBy('idcategoria', 'DESC')->findAll()
        ];
        return view('categorias/index', $data);
    }

    public function guardar()
    {
        $id  = $this->request->getPost('idcategoria');

        $datos = [
            'categoria' => trim((string) $this->request->getPost('nombrecategoria'))
        ];

        if ($id) {
            $datos['idcategoria'] = $id;
        }

        if (! $this->categoriaModel->save($datos)) {
            return redirect()->back()->withInput()->with('errors', $this->categoriaModel->errors());
        }

        $mensaje = $id ? 'Categoría actualizada correctamente.' : 'Categoría registrada correctamente.';
        return redirect()->to('/categorias')->with('mensaje', $mensaje);
    }


    public function eliminar($id = null)
    {
        if (! $id || ! $this->categoriaModel->find($id)) {
            return redirect()->to('/categorias')->with('error', 'La categoría no existe.');
        }


        $productosAsociados = $this->categoriaModel->builder('productos')->where('idcategoria', $id)->countAllResults();

        if ($productosAsociados > 0) {
            return redirect()->to('/categorias')->with('error', 'No se puede eliminar: tiene productos asignados.');
        }

        $this->categoriaModel->delete($id);
        return redirect()->to('/categorias')->with('mensaje', 'Categoría eliminada.');
    }
}
