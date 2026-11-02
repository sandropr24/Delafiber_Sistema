<?php

namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Models\MarcaModel;
use CodeIgniter\HTTP\RedirectResponse;

class CatalogoController extends BaseController
{
    private const LIMITE_PAGINA = 10;

    public function index(): string
    {
        $categoriaModel = model(CategoriaModel::class);
        $marcaModel     = model(MarcaModel::class);

        return view('catalogos/index', [
            'titulo'          => 'Gestión de Catálogos',
            'categoriasPadre' => $categoriaModel->obtenerCategoriasPrincipales(false),
            'totalCategorias' => $categoriaModel->countAllResults(),
            'totalMarcas'     => $marcaModel->countAllResults(),
        ]);
    }

    public function buscarCategorias()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('catalogos'));
        }

        $pagina    = (int) ($this->request->getGet('page') ?? 1);
        $resultado = model(CategoriaModel::class)->obtenerCategoriasPaginadas($pagina, self::LIMITE_PAGINA);

        return $this->response->setJSON([
            'html'           => view('catalogos/_filas_categorias', ['categorias' => $resultado['filas']]),
            'pagina'         => $resultado['pagina'],
            'totalPaginas'   => $resultado['totalPaginas'],
            'totalRegistros' => $resultado['totalRegistros'],
        ]);
    }

    public function buscarMarcas()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('catalogos'));
        }

        $marcaModel   = model(MarcaModel::class);
        $totalMarcas  = $marcaModel->countAllResults();
        $totalPaginas = max(1, (int) ceil($totalMarcas / self::LIMITE_PAGINA));
        $pagina       = min(max(1, (int) ($this->request->getGet('page') ?? 1)), $totalPaginas);

        $marcas = $marcaModel->orderBy('idmarca', 'ASC')
            ->findAll(self::LIMITE_PAGINA, ($pagina - 1) * self::LIMITE_PAGINA);

        return $this->response->setJSON([
            'html'           => view('catalogos/_filas_marcas', ['marcas' => $marcas]),
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalMarcas,
        ]);
    }

    public function guardarCategoria(): RedirectResponse
    {
        $categoriaModel = model(CategoriaModel::class);
        $idCategoria    = (int) $this->request->getPost('idcategoria');
        $idPadre        = (int) $this->request->getPost('idcategoriapadre') ?: null;
        $volver         = redirect()->to(base_url('catalogos'));

        if (!$this->validate(['categoria' => 'required|min_length[3]|max_length[80]'])) {
            return $volver->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if ($idPadre !== null) {
            if ($idPadre === $idCategoria) {
                return $volver->withInput()->with('error', 'Una categoría no puede ser su propio padre.');
            }

            $padre = $categoriaModel->find($idPadre);
            if (!$padre || !empty($padre['idcategoriapadre'])) {
                return $volver->withInput()->with('error', 'El padre debe ser una categoría principal existente.');
            }

            if ($idCategoria && $categoriaModel->where('idcategoriapadre', $idCategoria)->countAllResults() > 0) {
                return $volver->withInput()->with('error', 'Esta categoría tiene subcategorías, no puede convertirse en subcategoría.');
            }
        }

        $datos = [
            'categoria'        => trim((string) $this->request->getPost('categoria')),
            'idcategoriapadre' => $idPadre,
        ];

        if ($idCategoria) {
            $datos['idcategoria'] = $idCategoria;
        } else {
            $datos['estado'] = 1;
        }

        if (!$categoriaModel->save($datos)) {
            return $volver->withInput()->with('error', implode(' ', $categoriaModel->errors()));
        }

        return $volver->with('success', $idCategoria ? 'Categoría actualizada con éxito.' : 'Categoría registrada con éxito.');
    }

    public function guardarMarca(): RedirectResponse
    {
        $marcaModel = model(MarcaModel::class);
        $idMarca    = (int) $this->request->getPost('idmarca');
        $volver     = redirect()->to(base_url('catalogos'));

        $regla = 'required|min_length[2]|max_length[50]|is_unique[marcas.marca'
            . ($idMarca ? ',idmarca,' . $idMarca : '') . ']';

        $mensajes = [
            'marca' => ['is_unique' => 'El nombre de esta marca ya se encuentra registrado.'],
        ];

        if (!$this->validate(['marca' => $regla], $mensajes)) {
            return $volver->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $datos = ['marca' => trim((string) $this->request->getPost('marca'))];

        if ($idMarca) {
            $datos['idmarca'] = $idMarca;
        }

        if (!$marcaModel->save($datos)) {
            return $volver->withInput()->with('error', implode(' ', $marcaModel->errors()));
        }

        return $volver->with('success', $idMarca ? 'Marca actualizada con éxito.' : 'Marca registrada con éxito.');
    }

    public function cambiarEstadoCategoria($id = null): RedirectResponse
    {
        $categoriaModel = model(CategoriaModel::class);
        $volver         = redirect()->to(base_url('catalogos'));

        if (empty($id) || !$categoria = $categoriaModel->find($id)) {
            return $volver->with('error', 'La categoría no existe.');
        }

        $nuevoEstado = ((int) ($categoria['estado'] ?? 1) === 1) ? 0 : 1;

        if ($nuevoEstado === 0) {
            $enUso = $categoriaModel->contarProductosActivos((int) $id);
            if ($enUso > 0) {
                return $volver->with('error', "No se puede desactivar: tiene {$enUso} producto(s) activo(s) en ella o en sus subcategorías.");
            }
        }

        $categoriaModel->update($id, ['estado' => $nuevoEstado]);

        if ($nuevoEstado === 0) {
            $categoriaModel->where('idcategoriapadre', $id)->set('estado', 0)->update();
        }

        return $volver->with('success', 'Estado de la categoría actualizado.');
    }

    public function cambiarEstadoMarca($id = null): RedirectResponse
    {
        $marcaModel = model(MarcaModel::class);
        $volver     = redirect()->to(base_url('catalogos'));

        if (empty($id) || !$marca = $marcaModel->find($id)) {
            return $volver->with('error', 'La marca no existe.');
        }

        $nuevoEstado = ((int) ($marca['estado'] ?? 1) === 1) ? 0 : 1;
        $marcaModel->update($id, ['estado' => $nuevoEstado]);

        return $volver->with('success', 'Estado de la marca actualizado.');
    }
}