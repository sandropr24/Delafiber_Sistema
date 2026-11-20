<?php

namespace App\Database\Seeds;

use App\Libraries\CobroVenta;
use CodeIgniter\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    public function run()
    {
        foreach (CobroVenta::MEDIOS as $nombre) {
            if (!$this->db->table('tipospago')->where('tipopago', $nombre)->countAllResults()) {
                $this->db->table('tipospago')->insert(['tipopago' => $nombre, 'requierecuenta' => CobroVenta::esEfectivo($nombre) ? 0 : 1]);
            }
        }
    }
}
