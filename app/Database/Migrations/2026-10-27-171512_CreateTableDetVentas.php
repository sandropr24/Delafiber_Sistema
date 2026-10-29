<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetVentas extends Migration
{
   public function up()
    {
        $this->forge->addField([
            'iddetventa'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad'      => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'precioventa'   => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'costounitario' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'garantiameses' => ['type' => 'SMALLINT', 'constraint' => 6, 'default' => 0],
        ]);
        $this->forge->addKey('iddetventa', true);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detventa');   
    }

    public function down()
    {
        $this->forge->dropTable('detventa', true);
    }
}
