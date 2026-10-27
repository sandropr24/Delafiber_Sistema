<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableLineas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idlinea'   => [
                'type'            => 'INT',
                'constraint'      =>  11,
                'unsigned'        =>  true,
                'auto_increment'  =>  true,
            ],

            'prefijo'  => [
                'type'         => 'VARCHAR',
                'constraint'   =>  15,
                'unique'       =>  true,
            ],

            'nombre'   => [
                'type'         => 'VARCHAR',
                'constraint'   => 60,
            ],

            'estado'  => [
                'type'         => 'TINYINT',
                'constraint'   => 1,
                'default'      => 1,
            ]
        ]);
        $this->forge->addKey('idlinea', true);
        $this->forge->createTable('lineas');
    }

    public function down()
    {
        $this->forge->dropTable('lineas', true);
    }
}
