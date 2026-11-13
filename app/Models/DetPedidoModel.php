<?php
namespace App\Models;

use CodeIgniter\Model;

class DetPedidoModel extends Model
{
    protected $table = 'detpedido';
    protected $primaryKey = 'iddetpedido';
    protected $returnType = 'array';
    protected $allowedFields = ['idpedido', 'idproducto', 'cantidad', 'preciounitario', 'nombre_snapshot', 'config_snapshot'];
}
