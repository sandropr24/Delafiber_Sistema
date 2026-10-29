<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MarcaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['marca' => 'Ubiquiti'],
            ['marca' => 'MikroTik'],
            ['marca' => 'TP-Link'],
            ['marca' => 'Tenda'],
            ['marca' => 'Hikvision'],
            ['marca' => 'Dahua'],
            ['marca' => 'Epson'],
            ['marca' => 'HP'],
            ['marca' => 'Canon'],
            ['marca' => 'Logitech'],
            ['marca' => 'Kingston'],
            ['marca' => 'Cisco'],
            ['marca' => 'Generic / Genérico'],
        ];


        foreach ($data as $row) {
            $this->db->table('marcas')->ignore(true)->insert($row);
        }
    }
}