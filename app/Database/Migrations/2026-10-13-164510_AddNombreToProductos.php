<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNombreToProductos extends Migration
{
    public function up()
    {
        $fields = [
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
                'after'      => 'idproducto' 
            ],
        ];

        $this->forge->addColumn('productos', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('productos', 'nombre');
    }
}
