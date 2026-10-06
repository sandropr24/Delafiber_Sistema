<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaModel extends Model
{
    protected $table            = 'categorias';
    protected $primaryKey       = 'idcategoria';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['categoria'];
    protected $validationRules = [
        'idcategoria' => 'permit_empty|numeric',
        'categoria'   => 'required|is_unique[categorias.categoria,idcategoria,{idcategoria}]|min_length[3]|max_length[80]'
    ];


    protected $validationMessages = [
        'categoria' => [
            'required'  => 'El nombre de la categoría es obligatorio.',
            'is_unique' => 'Esta categoría ya se encuentra registrada.',
            'min_length' => 'El nombre de la categoría debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre de la categoría no puede superar los 80 caracteres.'
        ]
    ];

    protected $skipValidation = false;
}
