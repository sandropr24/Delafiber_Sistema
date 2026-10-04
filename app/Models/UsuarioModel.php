<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
   protected $table = 'usuarios';
   protected $primaryKey = 'idusuario';
   protected $allowedFields = ['nombre', 'correo', 'claveacceso', 'rol','created_at', 'updated_at'];
   protected $returnType = 'array';

   public function BuscarPorNombre( string $nombreusuario): ? array
   {
     return $this->select('usuarios.* , personas.nombres , personas.apellidos')
                    ->join('personas', 'personas.idpersona = usuarios.idpersona')
                    ->where('usuarios.nombreusuario', $nombreusuario)
                    ->first();
   }
}
