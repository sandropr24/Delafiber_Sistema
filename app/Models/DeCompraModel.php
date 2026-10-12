<?php

namespace App\Models;

use CodeIgniter\Model;

class DeCompraModel extends Model
{
    protected $table      = 'detcompras'; 
    protected $primaryKey = 'iddetcompra';

    protected $allowedFields = [
        'idcompra',
        'idproducto',
        'cantidad',
        'preciocompra'
    ];
}