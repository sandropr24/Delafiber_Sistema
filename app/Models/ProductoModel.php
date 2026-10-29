<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table            = 'productos';
    protected $primaryKey       = 'idproducto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'idcategoria',
        'tipo',
        'nombre',
        'descripcion',
        'idmarca',
        'modelo',
        'codigobarras',
        'idunidad',
        'controlastock',
        'controlaserie',
        'garantiameses',
        'preciocosto',
        'monedacosto',
        'precioventa',
        'estado',
        'imagen',
    ];

    protected $validationRules = [
        'idproducto'    => 'permit_empty|is_natural_no_zero',
        'idcategoria'   => 'required|is_natural_no_zero',
        'tipo'          => 'required|in_list[PRODUCTO,SERVICIO,COMBO]',
        'nombre'        => 'required|min_length[2]|max_length[150]',
        'descripcion'   => 'permit_empty|max_length[150]',
        // La marca es obligatoria salvo en servicios: esa regla se valida en el controlador
        'idmarca'       => 'permit_empty|is_natural_no_zero',
        'modelo'        => 'permit_empty|max_length[80]',
        'codigobarras'  => 'permit_empty|max_length[50]|is_unique[productos.codigobarras,idproducto,{idproducto}]',
        'idunidad'      => 'required|is_natural_no_zero',
        'controlastock' => 'permit_empty|in_list[0,1]',
        'controlaserie' => 'permit_empty|in_list[0,1]',
        'garantiameses' => 'permit_empty|is_natural|less_than_equal_to[120]',
        'preciocosto'   => 'permit_empty|decimal|greater_than_equal_to[0]|less_than[100000000]',
        'monedacosto'   => 'permit_empty|in_list[PEN,USD]',
        'precioventa'   => 'required|decimal|greater_than_equal_to[0]|less_than[100000000]',
        'estado'        => 'permit_empty|in_list[0,1]',
        'imagen'        => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'idcategoria' => ['required' => 'Debe seleccionar una categoría.'],
        'tipo'        => ['in_list' => 'El tipo debe ser producto, servicio o combo.'],
        'idunidad'    => ['required' => 'Debe seleccionar una unidad de medida.'],
        'nombre'      => [
            'required'   => 'El nombre del producto es obligatorio.',
            'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            'max_length' => 'El nombre no puede superar los 150 caracteres.',
        ],
        'codigobarras' => [
            'is_unique'  => 'Este código ya pertenece a otro producto.',
            'max_length' => 'El código no puede superar los 50 caracteres.',
        ],
        'garantiameses' => [
            'is_natural'          => 'La garantía debe ser un número de meses entero.',
            'less_than_equal_to'  => 'La garantía no puede superar los 120 meses.',
        ],
        'preciocosto' => [
            'decimal'                  => 'Ingrese un precio de costo válido.',
            'greater_than_equal_to'    => 'El precio de costo no puede ser negativo.',
        ],
        'precioventa' => [
            'required'              => 'El precio de venta es obligatorio.',
            'decimal'               => 'Ingrese un precio de venta válido.',
            'greater_than_equal_to' => 'El precio de venta no puede ser negativo.',
        ],
    ];

    /**
     * Base del listado. La marca va con LEFT JOIN porque los servicios no tienen marca
     * (con INNER JOIN los servicios desaparecerían del listado).
     */
    private function baseConRelaciones()
    {
        return $this->select('productos.*, categorias.categoria, marcas.marca')
            ->join('categorias', 'categorias.idcategoria = productos.idcategoria')
            ->join('marcas', 'marcas.idmarca = productos.idmarca', 'left');
    }

    /**
     * @param int[]       $idsCategoria Si viene, filtra por esas categorías (padre + hijas)
     * @param string|null $tipo         PRODUCTO | SERVICIO | COMBO
     * @param int|null    $estado       0 | 1
     */
    public function buscarConRelaciones(?string $q = null, array $idsCategoria = [], ?string $tipo = null, ?int $estado = null): array
    {
        $builder = $this->baseConRelaciones();

        if (!empty($q)) {
            $builder->groupStart()
                ->like('productos.nombre', $q)
                ->orLike('productos.descripcion', $q)
                ->orLike('productos.modelo', $q)
                ->orLike('productos.codigobarras', $q)
                ->orLike('categorias.categoria', $q)
                ->orLike('marcas.marca', $q)
                ->groupEnd();
        }

        if (!empty($idsCategoria)) {
            $builder->whereIn('productos.idcategoria', $idsCategoria);
        }

        if (!empty($tipo)) {
            $builder->where('productos.tipo', $tipo);
        }

        if ($estado !== null) {
            $builder->where('productos.estado', $estado);
        }

        return $builder->orderBy('productos.idproducto', 'DESC')->findAll();
    }
}