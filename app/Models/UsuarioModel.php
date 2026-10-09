<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
   protected $table            = 'usuarios';
   protected $primaryKey       = 'idusuario';
   protected $useAutoIncrement = true;
   protected $returnType       = 'array';
   protected $useSoftDeletes   = false;
   protected $protectFields    = true;

   protected $allowedFields    = [
      'idpersona',
      'nombreusuario',
      'claveacceso',
      'rol',
      'estado'
   ];

   protected $useTimestamps    = false;

   protected $beforeInsert     = ['hashearClave'];
   protected $beforeUpdate     = ['hashearClave'];


   //Busca usuario activo por nombreusuario.
   public function BuscarPorNombre(string $nombreusuario, bool $soloActivos = true): ?array
   {
      $builder = $this->select('usuarios.*, personas.nombres, personas.apellidos, personas.email, personas.numerodoc')
         ->join('personas', 'personas.idpersona = usuarios.idpersona')
         ->where('usuarios.nombreusuario', $nombreusuario);

      if ($soloActivos) {
         $builder->where('usuarios.estado', 1);
      }

      return $builder->first();
   }

   public function obtenerUsuariosConPersona() : array 
   {
         return $this->select('usuarios.*, personas.nombres, personas.apellidos, personas.numerodoc, personas.telefono, personas.email, personas.direccion')
               ->join('personas', 'personas.idpersona = usuarios.idpersona')
               ->orderBy('usuarios.idusuario', 'DESC')
               ->findAll();
   }

   protected function hashearClave(array $data)
   {
      if (isset($data['data']['claveacceso']) && !empty($data['data']['claveacceso'])) {
         $info = password_get_info($data['data']['claveacceso']);
         if ($info['algo'] === 0) {
            $data['data']['claveacceso'] = password_hash($data['data']['claveacceso'], PASSWORD_BCRYPT);
         }
      }
      return $data;
   }
}
