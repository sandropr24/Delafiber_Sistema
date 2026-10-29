<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetPedido extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetpedido'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idpedido'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'preciounitario' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey('iddetpedido', true);
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detpedido'); 
    }

    public function down()
    {
        $this->forge->dropTable('detpedido', true);
    }
}
