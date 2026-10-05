<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetVenta extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetventa'    => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idventa'       => ['type' => 'INT', 'constraint' => 11],
            'idproducto'    => ['type' => 'INT', 'constraint' => 11],
            'cantidad'      => ['type' => 'INT', 'constraint' => 11],
            'precioventa'   => ['type' => 'DECIMAL', 'constraint' => [10, 2]],
            'costounitario' => ['type' => 'DECIMAL', 'constraint' => [10, 2]]
        ]);
        $this->forge->addPrimaryKey('iddetventa');
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detventa', true);
    }

    public function down()
    {
        $this->forge->dropTable('detventa');
    }
}
