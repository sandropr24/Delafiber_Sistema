<?php

namespace App\Models;

use CodeIgniter\Model;

class MarcaModel extends Model
{
    protected  $table = 'marcas';
    protected  $primaryKey = 'idmarca';
    protected  $useAutoIncrement = true;
    protected  $returnType = 'array';
    protected  $allowedFields = ['marca'];

    protected $validationRules = [
        'idmarca' => 'permit_empty|numeric',
        'marca'   => 'required|min_length[2]|max_length[100]|is_unique[marcas.marca,idmarca.{idmarca}]',
    ];


    protected $validationMessages = [
        'marca' => [
            'required'   => 'El nombre de la marca es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 100 caracteres.',
            'is_unique'  => 'Esta marca ya está registrada.',
        ],
    ];
}
