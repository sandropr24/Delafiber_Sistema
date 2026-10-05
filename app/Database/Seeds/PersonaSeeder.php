<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PersonaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombres'   => 'Sandro',
                'apellidos' => 'Pachas Romani',
                'tipodoc'   => 'DNI',
                'numerodoc' => '60807449',
                'telefono'  => '987654321',
                'direccion' => 'Calle Jose 123',
                'email'     => 'sandro24@gmail.com',
            ]
        ];

        $this->db->table('personas')->insertBatch($data);
    }
}
