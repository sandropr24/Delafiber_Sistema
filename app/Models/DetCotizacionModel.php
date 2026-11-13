<?php
namespace App\Models;

use CodeIgniter\Model;

class DetCotizacionModel extends Model
{
    protected $table = 'detcotizacion';
    protected $primaryKey = 'iddetcotizacion';
    protected $returnType = 'array';
    protected $allowedFields = ['idcotizacion', 'idproducto', 'cantidad', 'preciounitario', 'precio_origen', 'nombre_snapshot', 'descripcion', 'config_snapshot'];
}
