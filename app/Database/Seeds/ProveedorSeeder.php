<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'razonsocial'     => 'Tecnologia del Peru S.A.C.',
                'ruc'             => '20100000001',
                'direccion'       => 'Av. Los Proceres 245, Lima',
                'telefono'        => '987654321',
                'email'           => 'ventas@tecnologiaperu.com',
                'nombrecomercial' => 'TecnoPeru',
            ],
            [
                'razonsocial'     => 'Distribuidora Electronica del Sur S.A.C.',
                'ruc'             => '20100000002',
                'direccion'       => 'Av. San Martin 350, Ica',
                'telefono'        => '956123478',
                'email'           => 'contacto@electronicasur.com',
                'nombrecomercial' => 'Electronica Sur',
            ],
            [
                'razonsocial'     => 'Importaciones Digitales S.A.C.',
                'ruc'             => '20100000003',
                'direccion'       => 'Av. Grau 128, Chincha Alta',
                'telefono'        => '945678123',
                'email'           => 'ventas@importdigital.com',
                'nombrecomercial' => 'ImportDigital',
            ],
            [
                'razonsocial'     => 'Soluciones Informaticas del Pacifico S.A.C.',
                'ruc'             => '20100000004',
                'direccion'       => 'Av. Bolognesi 560, Pisco',
                'telefono'        => '923456789',
                'email'           => 'contacto@solucionespacifico.com',
                'nombrecomercial' => 'SIP Tecnologia',
            ],
        ];

        $this->db->table('proveedores')->insertBatch($data);
    }
}
