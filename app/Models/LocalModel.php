<?php

namespace App\Models;

use CodeIgniter\Model;

class LocalModel extends Model
{
    protected $table            = 'locales';
    protected $primaryKey       = 'idlocal';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombrelocal',
        'direccion',
        'tipolocal',
    ];

    protected $validationRules = [
        'idlocal'     => 'permit_empty|is_natural_no_zero',
        'nombrelocal' => 'required|min_length[3]|max_length[80]|is_unique[locales.nombrelocal,idlocal,{idlocal}]',
        'direccion'   => 'required|min_length[3]|max_length[150]',
        'tipolocal'   => 'required|max_length[40]',
    ];

    protected $validationMessages = [
        'nombrelocal' => [
            'required'  => 'El nombre del local es obligatorio.',
            'is_unique' => 'Ya existe un local registrado con ese nombre.',
        ],
        'direccion' => [
            'required' => 'La dirección es obligatoria.',
        ],
        'tipolocal' => [
            'required' => 'El tipo de local es obligatorio.',
        ],
    ];
}