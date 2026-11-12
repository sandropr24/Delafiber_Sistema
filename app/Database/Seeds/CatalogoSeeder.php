<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public function run()
    {
        $lineas = [
            [
                'prefijo' => 'GRO',
                'nombre'  => 'Tienda (Grocio)',
                'estado'  => 1
            ],
            [
                'prefijo' => 'CAM',
                'nombre'  => 'Venta en campo',
                'estado'  => 1
            ],
            [
                'prefijo' => 'SER',
                'nombre'  => 'Servicio técnico',
                'estado'  => 1
            ]
        ];

        $canales = [
            ['canal' => 'Presencial'],
            ['canal' => 'WhatsApp'],
            ['canal' => 'Telefónico'],
            ['canal' => 'Facebook'],
            ['canal' => 'Instagram'],
            ['canal' => 'Página web'],
            ['canal' => 'Referido']
        ];

        $estados = [
            ['estado' => 'Pendiente'],
            ['estado' => 'Confirmada'],
            ['estado' => 'En preparación'],
            ['estado' => 'Entregada'],
            ['estado' => 'Cancelada']
        ];

        $this->db->transStart();

        foreach ($lineas as $linea) {
            $existe = $this->db->table('lineas')
                ->where('prefijo', $linea['prefijo'])
                ->countAllResults();

            if ($existe == 0) {
                $this->db->table('lineas')->insert($linea);
            }
        }

        foreach ($canales as $canal) {
            $existe = $this->db->table('canalesventa')
                ->where('canal', $canal['canal'])
                ->countAllResults();

            if ($existe == 0) {
                $this->db->table('canalesventa')->insert($canal);
            }
        }

        foreach ($estados as $estado) {
            $existe = $this->db->table('estadoventa')
                ->where('estado', $estado['estado'])
                ->countAllResults();

            if ($existe == 0) {
                $this->db->table('estadoventa')->insert($estado);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(
                'No se pudieron registrar los catálogos de venta.'
            );
        }
    }
}
