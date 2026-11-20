<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class LocalSeeder extends Seeder
{
    public function run()
    {
        $locales = [
            [
                'direccion'   => 'Av. Principal 123, Centro',
                'nombrelocal' => 'Sucursal Central',
                'tipolocal'   => 'TIENDA',
            ],
            [
                'direccion'   => 'Calle San Jose 103',
                'nombrelocal' => 'Delafiber Core',
                'tipolocal'   => 'OFICINA',
            ],
        ];

        foreach ($locales as $local) {
            $existe = $this->db->table('locales')
                ->where('nombrelocal', $local['nombrelocal'])
                ->where('direccion', $local['direccion'])
                ->countAllResults();

            if ($existe > 0) {
                continue;
            }

            $insertado = $this->db->table('locales')->insert($local);

            if (!$insertado) {
                throw new RuntimeException(
                    'No se pudo registrar el local: '
                    . $local['nombrelocal']
                );
            }
        }
    }
}
