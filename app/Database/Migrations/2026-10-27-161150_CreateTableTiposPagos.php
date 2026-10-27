<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTiposPagos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idtipopago' => [
                'type'     => 'INT',
                'constraint'  => 11,
                'unsigned'    => true,
                'auto_increment'  => true,
            ],

            'tipopago'   => [
                'type'   =>  'VARCHAR',
                'constraint'  =>  40,
                'unique'  => true,
            ],

            'requierecuenta' => [
                'type'   =>  'TINYINT',
                'constraint' => 1,
                'default'  => 0,
            ],

            'estado'  => [
                'type'   =>  'TINYINT',
                'constraint' => 1,
                'default'  => 1,
            ],
        ]);
        $this->forge->addKey('idtipopago', true);
        $this->forge->createTable('tipospago');
    }

    public function down()
    {
        $this->forge->dropTable('tipospago', true);
    }
}
