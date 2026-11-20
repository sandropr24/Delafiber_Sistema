<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdenServicioModel extends Model
{
    protected $table            = 'ordenes_servicio';
    protected $primaryKey       = 'idorden';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'idcliente',
        'idventa',
        'idservicio',
        'cantidad_servicio',
        'idunidad_servicio',
        'unidad_servicio_snapshot',
        'idtecnico',
        'titulo',
        'descripcion',
        'direccion',
        'telefono',
        'fecha_inicio',
        'fecha_fin',
        'fecha_finalizacion',
        'estado',
        'observaciones',
        'resultado',
        'idusuario_crea'
    ];
}
