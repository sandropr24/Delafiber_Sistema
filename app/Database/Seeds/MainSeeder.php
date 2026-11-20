<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        $this->call('SeguridadSeeder');
        $this->call('PersonaSeeder');
        $this->call('TipoPagoSeeder');
        $this->call('CategoriaSeeder');
        $this->call('MarcaSeeder');
        $this->call('UnidadMedidaSeeder');
        $this->call('ProductoSeeder');
        $this->call('ProveedorSeeder');
        $this->call('LocalSeeder');
        $this->call('CatalogoSeeder');
        $this->call('UsuarioSeeder');
    }
}
