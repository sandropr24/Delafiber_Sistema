<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run()
    {
        // Mantiene compatible el comando anterior, sin crear roles aislados
        // de sus permisos en una instalación nueva.
        $this->call('SeguridadSeeder');
    }
}
