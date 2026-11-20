<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        $productos = [
            [
                'categoria' => 'Seguridad y videovigilancia',
                'marca' => 'Hikvision',
                'nombre' => 'Cámara IP Hikvision 2MP',
                'descripcion' => 'Cámara IP de seguridad con visión nocturna',
                'modelo' => 'DS-2CD1023G0-I',
                'codigo' => 'PROD-001',
                'precio' => 185.00,
                'serie' => 1,
                'garantia' => 12
            ],
            [
                'categoria' => 'Redes',
                'marca' => 'TP-Link',
                'nombre' => 'Router TP-Link Archer C6',
                'descripcion' => 'Router inalámbrico doble banda AC1200',
                'modelo' => 'Archer C6',
                'codigo' => 'PROD-002',
                'precio' => 159.00,
                'serie' => 1,
                'garantia' => 12
            ],
            [
                'categoria' => 'Impresoras y tintas',
                'marca' => 'Epson',
                'nombre' => 'Impresora Epson EcoTank L3250',
                'descripcion' => 'Impresora multifuncional con WiFi',
                'modelo' => 'L3250',
                'codigo' => 'PROD-003',
                'precio' => 899.00,
                'serie' => 1,
                'garantia' => 12
            ],
            [
                'categoria' => 'Cómputo y accesorios',
                'marca' => 'Logitech',
                'nombre' => 'Mouse Logitech M90',
                'descripcion' => 'Mouse óptico USB con cable',
                'modelo' => 'M90',
                'codigo' => 'PROD-004',
                'precio' => 35.00,
                'serie' => 0,
                'garantia' => 6
            ],
            [
                'categoria' => 'Cómputo y accesorios',
                'marca' => 'Kingston',
                'nombre' => 'Memoria USB Kingston 64GB',
                'descripcion' => 'Memoria USB DataTraveler de 64GB',
                'modelo' => 'DataTraveler Exodia',
                'codigo' => 'PROD-005',
                'precio' => 45.00,
                'serie' => 0,
                'garantia' => 12
            ]
        ];

        $unidad = $this->db->table('unidadesmedida')
            ->get()
            ->getRowArray();

        if (!$unidad) {
            throw new RuntimeException('Primero registra las unidades de medida.');
        }

        foreach ($productos as $producto) {
            $categoria = $this->db->table('categorias')
                ->where('categoria', $producto['categoria'])
                ->where('idcategoriapadre', null)
                ->get()
                ->getRowArray();

            $marca = $this->db->table('marcas')
                ->where('marca', $producto['marca'])
                ->get()
                ->getRowArray();

            if (!$categoria || !$marca) {
                throw new RuntimeException(
                    'Falta categoría o marca para: ' . $producto['nombre']
                );
            }

            $existe = $this->db->table('productos')
                ->where('codigobarras', $producto['codigo'])
                ->countAllResults();

            if ($existe) {
                continue;
            }

            $this->db->table('productos')->insert([
                'idcategoria' => $categoria['idcategoria'],
                'tipo' => 'PRODUCTO',
                'nombre' => $producto['nombre'],
                'descripcion' => $producto['descripcion'],
                'idmarca' => $marca['idmarca'],
                'modelo' => $producto['modelo'],
                'codigobarras' => $producto['codigo'],
                'idunidad' => $unidad['idunidad'],
                'controlastock' => 1,
                'controlaserie' => $producto['serie'],
                'garantiameses' => $producto['garantia'],
                'preciocosto' => 0.00,
                'monedacosto' => 'PEN',
                'precioventa' => $producto['precio'],
                'estado' => 1,
                'imagen' => null
            ]);
        }
    }
}
