<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaModel extends Model
{
    protected $table            = 'categorias';
    protected $primaryKey       = 'idcategoria';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'idcategoriapadre',
        'categoria',
        'estado',
    ];

    protected $validationRules = [
        'idcategoria'      => 'permit_empty|is_natural_no_zero',
        'idcategoriapadre' => 'permit_empty|is_natural_no_zero',
        'categoria'        => 'required|min_length[3]|max_length[80]',
        'estado'           => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'categoria' => [
            'required'   => 'El nombre de la categoría es obligatorio.',
            'min_length' => 'El nombre de la categoría debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre de la categoría no puede superar los 80 caracteres.',
        ],
    ];

    protected $skipValidation = false;

    public function obtenerCategoriasPrincipales(bool $soloActivas = true): array
    {
        $builder = $this->where('idcategoriapadre', null);

        if ($soloActivas) {
            $builder->where('estado', 1);
        }

        return $builder->orderBy('categoria', 'ASC')->findAll();
    }

    public function obtenerCategoriasConPadre(): array
    {
        $todas = $this->select(
            'categorias.*, p.categoria AS categoriapadre,
            (SELECT COUNT(*) FROM productos pr WHERE pr.idcategoria = categorias.idcategoria) AS total_productos',
            false
        )
            ->join('categorias p', 'p.idcategoria = categorias.idcategoriapadre', 'left')
            ->orderBy('categorias.categoria', 'ASC')
            ->findAll();

        $principales = [];
        $hijos       = [];

        foreach ($todas as $cat) {
            if (empty($cat['idcategoriapadre'])) {
                $principales[] = $cat;
            } else {
                $hijos[$cat['idcategoriapadre']][] = $cat;
            }
        }

        $ordenadas = [];

        foreach ($principales as $padre) {
            $listaHijos = $hijos[$padre['idcategoria']] ?? [];

            $padre['nivel']           = 0;
            $padre['total_hijos']     = count($listaHijos);
            $padre['productos_hijos'] = array_sum(array_column($listaHijos, 'total_productos'));
            $ordenadas[]              = $padre;

            foreach ($listaHijos as $hijo) {
                $hijo['nivel']           = 1;
                $hijo['total_hijos']     = 0;
                $hijo['productos_hijos'] = 0;
                $ordenadas[]             = $hijo;
            }
        }

        return $ordenadas;
    }

    /**
     * @return array{filas: array, pagina: int, totalPaginas: int, totalRegistros: int}
     */
    public function obtenerCategoriasPaginadas(
        int $pagina,
        int $porPagina = 10,
        string $busqueda = '',
        string $estado = 'todos'
    ): array {
        $porPagina = max(1, $porPagina);
        $busqueda = trim($busqueda);

        $filtrarPadres = static function ($query) use ($busqueda, $estado) {
            $query->where('categorias.idcategoriapadre', null);

            if ($busqueda !== '') {
                $query->groupStart()
                    ->like('categorias.categoria', $busqueda)
                    ->orWhereIn('categorias.idcategoria', static function ($sub) use ($busqueda) {
                        return $sub->select('idcategoriapadre')
                            ->from('categorias')
                            ->where('idcategoriapadre IS NOT NULL', null, false)
                            ->like('categoria', $busqueda);
                    })
                    ->groupEnd();
            }

            if ($estado === 'activo' || $estado === 'inactivo') {
                $query->where('categorias.estado', $estado === 'activo' ? 1 : 0);
            }

            return $query;
        };

        $totalPrincipales = $filtrarPadres(new self())->countAllResults();
        $totalPaginas = max(1, (int) ceil($totalPrincipales / $porPagina));
        $pagina = min(max(1, $pagina), $totalPaginas);

        $productos = '(SELECT COUNT(*) FROM productos pr WHERE pr.idcategoria = categorias.idcategoria) AS total_productos';

        $principales = $filtrarPadres(new self())
            ->select("categorias.*, NULL AS categoriapadre, {$productos}", false)
            ->orderBy('categorias.idcategoria', 'ASC')
            ->findAll($porPagina, ($pagina - 1) * $porPagina);

        $hijosPorPadre = [];

        if ($principales) {
            $consultaHijos = (new self())
                ->select("categorias.*, p.categoria AS categoriapadre, {$productos}", false)
                ->join('categorias p', 'p.idcategoria = categorias.idcategoriapadre', 'left')
                ->whereIn('categorias.idcategoriapadre', array_column($principales, 'idcategoria'));

            if ($estado === 'activo' || $estado === 'inactivo') {
                $consultaHijos->where('categorias.estado', $estado === 'activo' ? 1 : 0);
            }

            $hijos = $consultaHijos
                ->orderBy('categorias.idcategoria', 'ASC')
                ->findAll();

            foreach ($hijos as $hijo) {
                $hijosPorPadre[$hijo['idcategoriapadre']][] = $hijo;
            }
        }

        $filas = [];

        foreach ($principales as $padre) {
            $lista = $hijosPorPadre[$padre['idcategoria']] ?? [];

            $padre['nivel'] = 0;
            $padre['total_hijos'] = count($lista);
            $padre['productos_hijos'] = array_sum(array_column($lista, 'total_productos'));
            $filas[] = $padre;

            foreach ($lista as $hijo) {
                $hijo['nivel'] = 1;
                $hijo['total_hijos'] = 0;
                $hijo['productos_hijos'] = 0;
                $filas[] = $hijo;
            }
        }

        return [
            'filas'          => $filas,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalPrincipales,
        ];
    }

    public function contarProductosActivos(int $idCategoria): int
    {
        $ids = array_column(
            $this->select('idcategoria')
                ->where('idcategoriapadre', $idCategoria)
                ->findAll(),
            'idcategoria'
        );

        $ids[] = $idCategoria;

        return db_connect()->table('productos')
            ->where('estado', 1)
            ->whereIn('idcategoria', $ids)
            ->countAllResults();
    }
}
