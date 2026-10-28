<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        $principales = [
            'Cómputo y accesorios',
            'Energía y protección',
            'Impresoras y tintas',
            'Seguridad y videovigilancia',
            'Redes',
            'Radioenlaces',
            'Servicios técnicos',
        ];

        foreach ($principales as $nombre) {
            $existe = $this->db->table('categorias')
                ->where('categoria', $nombre)
                ->where('idcategoriapadre', null)
                ->countAllResults();

            if (!$existe) {
                $this->db->table('categorias')->insert([
                    'categoria'        => $nombre,
                    'idcategoriapadre' => null,
                    'estado'           => 1,
                ]);
            }
        }
    }
}