<?php

namespace App\Models;

use CodeIgniter\Model;

class PagoVentaModel extends Model
{
    protected $table            = 'pagoventa';
    protected $primaryKey       = 'idpagoventa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    =
    [
        'idventa',
        'idtipopago',
        'idcuota',
        'idapertura',
        'idusuariocobro',
        'idtecnicocobro',
        'identrega',
        'idcuenta',
        'voucher',
        'monto',
        'referencia',
        'montorecibido',
        'vuelto',
        'fecha',
        'token_operacion'
    ];

    protected $useTimestamps = false;
}
