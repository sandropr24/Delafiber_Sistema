<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $persona = $this->db->table('personas')->where('numerodoc', '60807449')->get()->getRowArray();

        $rol = $this->db->table('roles')->where('rol', 'Administrador')->get()->getRowArray();

        if ($persona && $rol) {

            $usuarioExiste = $this->db->table('usuarios')->where('nombreusuario', 'sandro')->countAllResults();

            if ($usuarioExiste === 0) {
                $usuario = [
                    'idpersona'     => $persona['idpersona'],
                    'idrol'         => $rol['idrol'],
                    'nombreusuario' => 'sandro',
                    'claveacceso'   => password_hash('sandro24', PASSWORD_BCRYPT),
                    'estado'        => 1
                ];

                $this->db->table('usuarios')->insert($usuario);
            }
        }
    }
}
