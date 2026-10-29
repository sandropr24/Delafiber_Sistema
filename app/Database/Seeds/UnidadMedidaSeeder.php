<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UnidadMedidaSeeder extends Seeder
{
    public function run()
    {
        $unidades = [
            ['unidad' => 'Unidad',   'abreviatura' => 'UND'],
            ['unidad' => 'Par',      'abreviatura' => 'PAR'],
            ['unidad' => 'Juego',    'abreviatura' => 'JGO'],
            ['unidad' => 'Kit',      'abreviatura' => 'KIT'],
            ['unidad' => 'Metro',    'abreviatura' => 'M'],
            ['unidad' => 'Rollo',    'abreviatura' => 'ROL'],
            ['unidad' => 'Caja',     'abreviatura' => 'CJA'],
            ['unidad' => 'Paquete',  'abreviatura' => 'PQT'],
            ['unidad' => 'Servicio', 'abreviatura' => 'SERV'],
        ];

        $tabla = $this->db->table('unidadesmedida');

        foreach ($unidades as $u) {
            $existe = $this->db->table('unidadesmedida')
                ->groupStart()
                ->where('unidad', $u['unidad'])
                ->orWhere('abreviatura', $u['abreviatura'])
                ->groupEnd()
                ->countAllResults();

            if (!$existe) {
                $tabla->insert($u);
            }
        }
    }
}