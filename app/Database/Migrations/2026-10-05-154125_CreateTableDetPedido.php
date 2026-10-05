<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetPedido extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetpedido'    => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idpedido'       => ['type' => 'INT', 'constraint' => 11],
            'idproducto'     => ['type' => 'INT', 'constraint' => 11],
            'cantidad'       => ['type' => 'INT', 'constraint' => 11],
            'preciounitario' => ['type' => 'DECIMAL', 'constraint' => [10, 2]],
            'total'          => ['type' => 'DECIMAL', 'constraint' => [10, 2]]
        ]);
        $this->forge->addPrimaryKey('iddetpedido');
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detpedido', true);
    }

    public function down()
    {
        $this->forge->dropTable('detpedido');
    }
}
