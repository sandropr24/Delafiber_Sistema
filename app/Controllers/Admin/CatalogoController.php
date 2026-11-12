<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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
            'titulo'           => 'Gestión de Catálogos',
            'categoriasPadre'  => $categoriaModel->obtenerCategoriasPrincipales(false),
            'totalCategorias'  => $categoriaModel->countAllResults(),
            'totalMarcas'      => $marcaModel->countAllResults(),
        ]);
    }

    public function buscarCategorias()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('admin/catalogos'));
        }

        $pagina    = (int) ($this->request->getGet('page') ?? 1);
        $busqueda  = trim((string) $this->request->getGet('buscar'));
        $estado    = (string) ($this->request->getGet('estado') ?? 'todos');
        $resultado = model(CategoriaModel::class)->obtenerCategoriasPaginadas($pagina, self::LIMITE_PAGINA, $busqueda, $estado);

        return $this->response->setJSON([
            'html'           => view('catalogos/_filas_categorias', [
                'categorias'      => $resultado['filas'],
                'puedeAdministrar' => session()->get('rol') === 'Administrador'
            ]),
            'pagina'         => $resultado['pagina'],
            'totalPaginas'   => $resultado['totalPaginas'],
            'totalRegistros' => $resultado['totalRegistros'],
        ]);
    }

    public function buscarMarcas()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('admin/catalogos'));
        }

        $busqueda = trim((string) $this->request->getGet('buscar'));
        $estado   = (string) ($this->request->getGet('estado') ?? 'todos');

        $filtrar = static function ($query) use ($busqueda, $estado) {
            if ($busqueda !== '') {
                $query->like('marca', $busqueda);
            }

            if ($estado === 'activo' || $estado === 'inactivo') {
                $query->where('estado', $estado === 'activo' ? 1 : 0);
            }

            return $query;
        };

        $totalMarcas  = $filtrar(model(MarcaModel::class))->countAllResults();
        $totalPaginas = max(1, (int) ceil($totalMarcas / self::LIMITE_PAGINA));
        $pagina       = min(max(1, (int) ($this->request->getGet('page') ?? 1)), $totalPaginas);

        $marcas = $filtrar(model(MarcaModel::class))
            ->select('marcas.*, (SELECT COUNT(*) FROM productos pr WHERE pr.idmarca = marcas.idmarca) AS total_productos', false)
            ->orderBy('idmarca', 'ASC')
            ->findAll(self::LIMITE_PAGINA, ($pagina - 1) * self::LIMITE_PAGINA);

        return $this->response->setJSON([
            'html'           => view('catalogos/_filas_marcas', [
                'marcas'          => $marcas,
                'puedeAdministrar' => session()->get('rol') === 'Administrador'
            ]),
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
        $volver         = redirect()->to(base_url('admin/catalogos'));

        if (!$this->validate(['categoria' => 'required|min_length[3]|max_length[80]'])) {
            return $volver->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if ($idCategoria > 0 && !$categoriaModel->find($idCategoria)) {
            return $volver->with('error', 'La categoría que deseas editar no existe.');
        }

        if ($idPadre !== null) {
            if ($idPadre === $idCategoria) {
                return $volver->withInput()->with('error', 'Una categoría no puede ser su propio padre.');
            }

            $padre = $categoriaModel->find($idPadre);

            if (!$padre || !empty($padre['idcategoriapadre'])) {
                return $volver->withInput()->with('error', 'El padre debe ser una categoría principal existente.');
            }

            if ((int) $padre['estado'] !== 1) {
                return $volver->withInput()->with('error', 'No puedes asignar una categoría a un padre inactivo.');
            }

            if ($idCategoria && $categoriaModel->where('idcategoriapadre', $idCategoria)->countAllResults() > 0) {
                return $volver->withInput()->with('error', 'Esta categoría tiene subcategorías, no puede convertirse en subcategoría.');
            }
        }

        $nombre = trim((string) $this->request->getPost('categoria'));

        $duplicado = model(CategoriaModel::class)
            ->where('categoria', $nombre)
            ->where('idcategoriapadre', $idPadre);

        if ($idCategoria > 0) {
            $duplicado->where('idcategoria !=', $idCategoria);
        }

        if ($duplicado->first()) {
            return $volver->withInput()->with('error', 'Ya existe una categoría con ese nombre dentro del mismo nivel.');
        }

        $datos = [
            'categoria'        => $nombre,
            'idcategoriapadre' => $idPadre
        ];

        if ($idCategoria) {
            $datos['idcategoria'] = $idCategoria;
        } else {
            $datos['estado'] = 1;
        }

        if (!$categoriaModel->save($datos)) {
            return $volver->withInput()->with('error', implode(' ', $categoriaModel->errors()));
        }

        return $volver->with('success', $idCategoria
            ? 'Categoría actualizada con éxito.'
            : 'Categoría registrada con éxito.');
    }

    public function guardarMarca(): RedirectResponse
    {
        $marcaModel = model(MarcaModel::class);
        $idMarca    = (int) $this->request->getPost('idmarca');
        $volver     = redirect()->to(base_url('admin/catalogos'));

        if ($idMarca > 0 && !$marcaModel->find($idMarca)) {
            return $volver->with('error', 'La marca que deseas editar no existe.');
        }

        $regla = 'required|min_length[2]|max_length[50]|is_unique[marcas.marca'
            . ($idMarca ? ',idmarca,' . $idMarca : '') . ']';

        $mensajes = [
            'marca' => [
                'is_unique' => 'El nombre de esta marca ya se encuentra registrado.'
            ],
        ];

        if (!$this->validate(['marca' => $regla], $mensajes)) {
            return $volver->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $datos = [
            'marca' => trim((string) $this->request->getPost('marca'))
        ];

        if ($idMarca) {
            $datos['idmarca'] = $idMarca;
        }

        if (!$marcaModel->save($datos)) {
            return $volver->withInput()->with('error', implode(' ', $marcaModel->errors()));
        }

        return $volver->with('success', $idMarca
            ? 'Marca actualizada con éxito.'
            : 'Marca registrada con éxito.');
    }

    public function cambiarEstadoCategoria($id = null): RedirectResponse
    {
        $categoriaModel = model(CategoriaModel::class);
        $volver         = redirect()->to(base_url('admin/catalogos'));

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

        if ($nuevoEstado === 1 && !empty($categoria['idcategoriapadre'])) {
            $padre = $categoriaModel->find((int) $categoria['idcategoriapadre']);

            if (!$padre || (int) $padre['estado'] !== 1) {
                return $volver->with('error', 'Activa primero la categoría principal.');
            }
        }

        $db = db_connect();
        $db->transBegin();

        try {
            if (!$db->table('categorias')->where('idcategoria', $id)->update(['estado' => $nuevoEstado])) {
                throw new \RuntimeException('No se pudo actualizar la categoría.');
            }

            if ($nuevoEstado === 0 && empty($categoria['idcategoriapadre'])) {
                if (!$db->table('categorias')->where('idcategoriapadre', $id)->update(['estado' => 0])) {
                    throw new \RuntimeException('No se pudieron desactivar las subcategorías.');
                }
            }

            if ($db->transStatus() === false || !$db->transCommit()) {
                throw new \RuntimeException('No se pudo confirmar la operación.');
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Catálogos: error al cambiar categoría: ' . $e->getMessage());

            return $volver->with('error', 'No se pudo cambiar el estado de la categoría.');
        }

        return $volver->with('success', 'Estado de la categoría actualizado.');
    }

    public function cambiarEstadoMarca($id = null): RedirectResponse
    {
        $marcaModel = model(MarcaModel::class);
        $volver     = redirect()->to(base_url('admin/catalogos'));

        if (empty($id) || !$marca = $marcaModel->find($id)) {
            return $volver->with('error', 'La marca no existe.');
        }

        $nuevoEstado = ((int) ($marca['estado'] ?? 1) === 1) ? 0 : 1;

        if ($nuevoEstado === 0) {
            $totalProductos = db_connect()->table('productos')
                ->where('idmarca', (int) $id)
                ->countAllResults();

            if ($totalProductos > 0) {
                return $volver->with(
                    'error',
                    "No se puede desactivar la marca porque tiene {$totalProductos} producto(s) asociado(s)."
                );
            }
        }

        if (!$marcaModel->update($id, ['estado' => $nuevoEstado])) {
            return $volver->with('error', 'No se pudo actualizar el estado de la marca.');
        }

        return $volver->with('success', $nuevoEstado === 1
            ? 'Marca activada correctamente.'
            : 'Marca desactivada correctamente.');
    }
}
