<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetCompras extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetcompra'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idcompra'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad'     => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'preciocompra' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey('iddetcompra', true);
        $this->forge->addForeignKey('idcompra', 'compras', 'idcompra', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detcompra');
    }

    public function down()
    {
        $this->forge->dropTable('detcompra', true);
    }
}
