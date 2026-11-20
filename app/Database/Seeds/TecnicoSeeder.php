<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TecnicoSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombres'       => 'David ',
                'apellidos'     => 'Rojas Torres',
                'dni'           => '78556823',
                'telefono'      => '954655454',
                'especialidad'  => 'Instalacion',
                'estado'        => 'Activo',
                'created_at'    => date('Y-m-d'),
                'updated_at'    => date('Y-m-d'),
            ],

            [
                'nombres'       => 'Walter',
                'apellidos'     => 'Pachas Castilla',
                'dni'           => '65563833',
                'telefono'      => '964376476',
                'especialidad'  => 'Mantenimiento',
                'estado'        => 'Activo',
                'created_at'    => date('Y-m-d'),
                'updated_at'    => date('Y-m-d'),
            ],
            [
                'nombres'       => 'Pedro',
                'apellidos'     => 'Tasayco Ramos',
                'dni'           => '78545484',
                'telefono'      => '955578456',
                'especialidad'  => 'Instalacion',
                'estado'        => 'Activo',
                'created_at'    => date('Y-m-d'),
                'updated_at'    => date('Y-m-d'),
            ],
        ];

        $this->db->table('tecnicos')->insertBatch($data);
    }
}
