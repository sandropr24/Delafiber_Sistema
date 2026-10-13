<?php

namespace App\Models;

use CodeIgniter\Model;

class DetVentaModel extends Model
{
    protected $table      = 'detventa';
    protected $primaryKey = 'iddetventa';

    protected $allowedFields = [
        'idventa',
        'idproducto',
        'cantidad',
        'precioventa',
        'costounitario'
    ];
}
