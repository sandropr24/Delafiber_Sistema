<?php

namespace App\Models;

use CodeIgniter\Model;

class KardexModel extends Model
{
    protected $table            = 'kardex';
    protected $primaryKey       = 'idkardex';
    protected $allowedFields    = [
        'idproducto', 'minima', 'maxima', 'idlocal', 'stockactual', 'costopromedio'
    ];
    protected $useTimestamps    = false;

    public function obtenerStockGeneral()
    {
        return $this->select('kardex.*, productos.descripcion as producto, locales.nombrelocal as local')
                    ->join('productos', 'productos.idproducto = kardex.idproducto')
                    ->join('locales', 'locales.idlocal = kardex.idlocal')
                    ->findAll();
    }
}