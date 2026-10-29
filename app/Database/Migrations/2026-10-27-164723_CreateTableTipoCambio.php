<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTipoCambio extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idtipocambio' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fecha' => [
                'type'   => 'DATE',
                'unique' => true,
            ],
            'compra' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,4',
            ],
            'venta' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,4',
            ],
        ]);
        $this->forge->addKey('idtipocambio', true);
        $this->forge->createTable('tipocambio'); 
    }

    public function down()
    {
        $this->forge->dropTable('tipocambio', true);
    }
}
