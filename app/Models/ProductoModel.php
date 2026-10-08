<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table            = 'productos';
    protected $primaryKey       = 'idproducto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'idcategoria',
        'descripcion',
        'idmarca',
        'modelo',
        'codigobarras',
        'estado',
        'precioventa',
        'imagen',
    ];

    protected $validationRules = [
        'idproducto'   => 'permit_empty|is_natural_no_zero',
        'idcategoria'  => 'required|is_natural_no_zero',
        'idmarca'      => 'required|is_natural_no_zero',
        'descripcion'  => 'required|min_length[3]|max_length[150]',
        'modelo'       => 'permit_empty|max_length[80]',
        'codigobarras' => 'permit_empty|max_length[50]|is_unique[productos.codigobarras,idproducto,{idproducto}]',
        'precioventa'  => 'required|decimal|greater_than_equal_to[0]',
        'estado'       => 'permit_empty|in_list[0,1]',
        'imagen'       => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'idcategoria'  => ['required' => 'Debe seleccionar una categoría.'],
        'idmarca'      => ['required' => 'Debe seleccionar una marca.'],
        'descripcion'  => [
            'required'   => 'La descripción del producto es obligatoria.',
            'max_length' => 'La descripción no puede superar los 150 caracteres.',
        ],
        'codigobarras' => [
            'is_unique'  => 'Este código de barras ya pertenece a otro producto.',
        ],
        'precioventa'  => [
            'required' => 'El precio de venta es obligatorio.',
            'decimal'  => 'Ingrese un precio válido.',
        ],
    ];

    public function obtenerConRelaciones()
    {
        return $this->select('productos.*, categorias.categoria, marcas.marca')
                    ->join('categorias', 'categorias.idcategoria = productos.idcategoria')
                    ->join('marcas', 'marcas.idmarca = productos.idmarca')
                    ->orderBy('productos.idproducto', 'DESC')
                    ->findAll();
    }
}