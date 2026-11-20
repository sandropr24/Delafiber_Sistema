<?php

namespace App\Database\Seeds;

use App\Services\SeguridadInicialService;
use CodeIgniter\Database\Seeder;

class SeguridadSeeder extends Seeder
{
    public function run()
    {
        (new SeguridadInicialService($this->db))->inicializar();
    }
}
