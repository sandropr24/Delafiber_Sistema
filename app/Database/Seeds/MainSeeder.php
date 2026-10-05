<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        $this->call('PersonaSeeder');
        $this->call('UsuarioSeeder');
    }
}
