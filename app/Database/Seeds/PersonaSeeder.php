<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PersonaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'tipopersona'   => 'NATURAL',
                'apellidos'     => 'Pachas Romani',
                'nombres'       => 'Sandro Steven',
                'razonsocial'   => null,
                'tipodoc'       => 'DNI',
                'numerodoc'     => '60807449',
                'direccion'     => 'Av. Las Flores 123, Lima',
                'telefono'      => '987654321',
                'email'         => 'sandroromanipr24@gmail.com',
                'codigocliente' => 'CLI-001',
            ],
        ];

        $this->db->table('personas')->insertBatch($data);
    }
}
