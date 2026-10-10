<?php

namespace App\Models;

use CodeIgniter\Model;

class MovimientoModel extends Model
{
    protected $table            = 'movimientos';
    protected $primaryKey       = 'idmovimiento';
    protected $allowedFields    = [
        'idkardex', 'idcompra', 'idusuario', 'tipo', 
        'fecha', 'cantidad', 'saldo', 'descripcion', 'motivo'
    ];
    protected $useTimestamps    = false;

    public function obtenerHistorialPorKardex($idkardex)
    {
        return $this->select('movimientos.*, usuarios.username as usuario')
                    ->join('usuarios', 'usuarios.idusuario = movimientos.idusuario', 'left')
                    ->where('movimientos.idkardex', $idkardex)
                    ->orderBy('movimientos.fecha', 'DESC')
                    ->findAll();
    }
}