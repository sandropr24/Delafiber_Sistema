<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['tipopago' => 'Efectivo'],
            ['tipopago' => 'Yape'],
            ['tipopago' => 'Plin'],
            ['tipopago' => 'Tarjeta Débito/Crédito'],
            ['tipopago' => 'Transferencia Bancaria'],
        ];

        $this->db->table('tipospago')->insertBatch($data);
    }
}