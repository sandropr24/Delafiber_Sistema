<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoPagoModel extends Model
{
    protected $table            = 'tipospago';
    protected $primaryKey       = 'idtipopago';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['tipopago'];
}
