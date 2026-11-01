<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraModel extends Model
{
    protected $table            = 'compras';
    protected $primaryKey       = 'idcompra';
    protected $allowedFields    = [
        'idproveedor',
        'serie',
        'numero',
        'moneda',
        'tipocambio',
        'fechacompra',
        'fecharegistro',
        'tipocomprobante',
        'total'
    ];
    protected $useTimestamps    = false;
}
