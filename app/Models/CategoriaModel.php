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

    public function contarProductosActivos(int $idCategoria): int
    {
        $ids = array_column(
            $this->select('idcategoria')->where('idcategoriapadre', $idCategoria)->findAll(),
            'idcategoria'
        );
        $ids[] = $idCategoria;

        return db_connect()->table('productos')
            ->where('estado', 1)
            ->whereIn('idcategoria', $ids)
            ->countAllResults();
    }
}