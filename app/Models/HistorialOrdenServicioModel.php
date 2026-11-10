<?php

namespace App\Models;

use CodeIgniter\Model;

class HistorialOrdenServicioModel extends Model
{
    protected $table            = 'historial_orden_servicio';
    protected $primaryKey       = 'idhistorial';
    protected $returnType       = 'array';
    protected $allowedFields = [
        'idorden',
        'tipoevento',
        'estado_anterior',
        'estado_nuevo',
        'observacion',
        'idusuario',
        'fecha'

    ];
}
