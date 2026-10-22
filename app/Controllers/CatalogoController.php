<?php

namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Models\MarcaModel;

class CatalogoController extends BaseController
{
    public function index()
    {
        $categoriaModel = model(CategoriaModel::class);
        $marcaModel     = model(MarcaModel::class);

        $data = [
            'titulo'     => 'Gestión de Catálogos',
            'categorias' => $categoriaModel->orderBy('idcategoria', 'ASC')->findAll(),
            'marcas'     => $marcaModel->orderBy('idmarca', 'ASC')->findAll(),
        ];

        return view('catalogos/index', $data);
    }

    public function guardarCategoria()
    {
        $categoriaModel = model(CategoriaModel::class);

        $rules = [
            'categoria' => 'required|min_length[2]|is_unique[categorias.categoria]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('catalogos'))->with('error', 'El nombre de la categoría es obligatorio o ya existe.');
        }

        $categoriaModel->save([
            'categoria' => $this->request->getPost('categoria')
        ]);

        return redirect()->to(base_url('catalogos'))->with('success', 'Categoría registrada con éxito.');
    }

    public function guardarMarca()
    {
        $marcaModel = model(MarcaModel::class);

        $rules = [
            'marca' => 'required|min_length[2]|is_unique[marcas.marca]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('catalogos'))->with('error', 'El nombre de la marca es obligatorio o ya existe.');
        }

        $marcaModel->save([
            'marca' => $this->request->getPost('marca')
        ]);

        return redirect()->to(base_url('catalogos'))->with('success', 'Marca registrada con éxito.');
    }

    public function actualizarCategoria()
    {
        $categoriaModel = model(CategoriaModel::class);
        $id = $this->request->getPost('idcategoria');

        $rules = [
            'categoria' => "required|min_length[2]|is_unique[categorias.categoria,idcategoria,{$id}]"
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('catalogos'))->with('error', 'El nombre de la categoría es obligatorio o ya está en uso por otra.');
        }

        $categoriaModel->update($id, [
            'categoria' => $this->request->getPost('categoria')
        ]);

        return redirect()->to(base_url('catalogos'))->with('success', 'Categoría actualizada con éxito.');
    }

    public function actualizarMarca()
    {
        $marcaModel = model(MarcaModel::class);
        $id = $this->request->getPost('idmarca');

        $rules = [
            'marca' => "required|min_length[2]|is_unique[marcas.marca,idmarca,{$id}]"
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('catalogos'))->with('error', 'El nombre de la marca es obligatorio o ya está en uso por otra.');
        }

        $marcaModel->update($id, [
            'marca' => $this->request->getPost('marca')
        ]);

        return redirect()->to(base_url('catalogos'))->with('success', 'Marca actualizada con éxito.');
    }

    public function eliminarCategoria($id)
    {
        $categoriaModel = model(CategoriaModel::class);

        try {
            $categoriaModel->delete($id);
            return redirect()->to(base_url('catalogos'))->with('success', 'Categoría eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->to(base_url('catalogos'))->with('error', 'No se puede eliminar la categoría porque está siendo utilizada por algún producto.');
        }
    }

    public function eliminarMarca($id)
    {
        $marcaModel = model(MarcaModel::class);

        try {
            $marcaModel->delete($id);
            return redirect()->to(base_url('catalogos'))->with('success', 'Marca eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->to(base_url('catalogos'))->with('error', 'No se puede eliminar la marca porque está siendo utilizada por algún producto.');
        }
    }
}
