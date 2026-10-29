<?php

namespace App\Models;

use CodeIgniter\Model;

class EntregaModel extends Model
{
    protected $table            = 'entregas';
    protected $primaryKey       = 'identrega';
    protected $allowedFields    = [
        'idventa',
        'tipoentrega',
        'direccion',
        'referencia',
        'telefono',
        'idtecnico',
        'estado',
        'fechaencamino',
        'fechaentrega',
        'cobrocontraentrega',
        'idpagoventa'
    ];
    protected $useTimestamps    = false;

    public function obtenerEntregasFiltradas($estado = null, $idtecnico = null, $q = null)
    {
        $builder = $this->select('
                entregas.*, 
                CONCAT(ventas.serie, "-", ventas.numero) as correlativo,
                ventas.total, 
                IF(personas.tipopersona = "NATURAL", CONCAT(personas.nombres, " ", personas.apellidos), personas.razonsocial) as cliente_nombre,
                personas.telefono as cliente_telefono,
                tecnicos.nombreusuario as tecnico_nombre
            ')
            ->join('ventas', 'ventas.idventa = entregas.idventa')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('usuarios as tecnicos', 'tecnicos.idusuario = entregas.idtecnico', 'left');

        if (!empty($estado)) {
            $builder->where('entregas.estado', $estado);
        }

        if (!empty($idtecnico)) {
            $builder->where('entregas.idtecnico', $idtecnico);
        }

        if (!empty($q)) {
            $builder->groupStart()
                ->like('ventas.serie', $q)
                ->orLike('ventas.numero', $q)
                ->orLike('personas.nombres', $q)
                ->orLike('personas.apellidos', $q)
                ->orLike('personas.razonsocial', $q)
                ->orLike('entregas.direccion', $q)
                ->orLike('entregas.telefono', $q)
                ->groupEnd();
        }

        return $builder->orderBy('entregas.identrega', 'DESC')->findAll();
    }
}