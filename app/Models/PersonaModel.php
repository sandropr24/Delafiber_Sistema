<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonaModel extends Model
{
    protected $table            = 'personas';
    protected $primaryKey       = 'idpersona';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'apellidos',
        'nombres',
        'tipodoc',
        'numerodoc',
        'direccion',
        'telefono',
        'email',
    ];

    protected $validationRules = [
        'idpersona' => 'permit_empty|is_natural_no_zero',
        'apellidos' => 'required|min_length[2]|max_length[100]',
        'nombres'   => 'required|min_length[2]|max_length[100]',
        'tipodoc'   => 'required|max_length[20]',
        'numerodoc' => 'required|max_length[20]',
        'direccion' => 'permit_empty|max_length[150]',
        'telefono'  => 'permit_empty|max_length[20]',
        'email'     => 'permit_empty|valid_email|max_length[100]',
    ];
}