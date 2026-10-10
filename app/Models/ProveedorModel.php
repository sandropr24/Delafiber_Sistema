<?php

namespace App\Models;

use CodeIgniter\Model;

class ProveedorModel extends Model
{
   protected $table = 'proveedores';
   protected $primaryKey = 'idproveedor';
   protected $allowedFields = [
    'razonsocial',
    'ruc',
    'direccion',
    'telefono',
    'email',
    'nombrecomercial',
   ];

   protected $useTimestamps = false;
}
