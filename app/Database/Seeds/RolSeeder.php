<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['rol' => 'Administrador'],
            ['rol' => 'Operador'],
            ['rol' => 'Vendedor'],
        ];

        foreach ($roles as $row) {
            $existe = $this->db->table('roles')->where('rol', $row['rol'])->countAllResults();

            if ($existe === 0) {
                $this->db->table('roles')->insert($row);
            }
        }
    }
}
