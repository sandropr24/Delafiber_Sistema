<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarEstadoMarcas extends Migration
{
    public function up()
    {
        $fields = [
            'estado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'marca', 
            ],
        ];
        
        $this->forge->addColumn('marcas', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('marcas', 'estado');
    }
}