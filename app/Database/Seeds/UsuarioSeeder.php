<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $persona = $this->db->table('personas')->where('numerodoc', '60807449')->get()->getRowArray();

        if ($persona) {
            $usuario = [
                'idpersona'     => $persona['idpersona'],
                'nombreusuario' => 'admin',
                'claveacceso'   => password_hash('sandro24', PASSWORD_BCRYPT),
                'rol'           => 'Administrador',
                'estado'        => 1
            ];

            $this->db->table('usuarios')->insert($usuario);
        }
    }
}
