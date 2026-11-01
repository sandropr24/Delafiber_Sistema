<?php

namespace App\Models;

use CodeIgniter\Model;

class KardexModel extends Model
{
    protected $table            = 'kardex';
    protected $primaryKey       = 'idkardex';
    protected $allowedFields    = [
        'idproducto',
        'minima',
        'maxima',
        'idlocal',
        'stockactual',
        'costopromedio'
    ];
    protected $useTimestamps    = false;

    public function obtenerStockGeneral()
    {
        return $this->select('kardex.*, productos.nombre as producto, locales.nombrelocal as local, unidadesmedida.abreviatura as unidad')
            ->join('productos', 'productos.idproducto = kardex.idproducto')
            ->join('unidadesmedida', 'unidadesmedida.idunidad = productos.idunidad', 'left')
            ->join('locales', 'locales.idlocal = kardex.idlocal')
            ->findAll();
    }

    public function buscarStockFiltrado($q = null, $idlocal = null)
    {
        $builder = $this->select('kardex.*, productos.nombre as producto, locales.nombrelocal as local, unidadesmedida.abreviatura as unidad')
            ->join('productos', 'productos.idproducto = kardex.idproducto')
            ->join('unidadesmedida', 'unidadesmedida.idunidad = productos.idunidad', 'left')
            ->join('locales', 'locales.idlocal = kardex.idlocal');

        if (!empty($idlocal)) {
            $builder->where('kardex.idlocal', $idlocal);
        }

        if (!empty($q)) {
            $builder->groupStart()
                ->like('productos.nombre', $q)
                ->orLike('productos.descripcion', $q)
                ->orLike('locales.nombrelocal', $q)
                ->groupEnd();
        }

        return $builder->orderBy('kardex.idkardex', 'DESC')->findAll();
    }
}
