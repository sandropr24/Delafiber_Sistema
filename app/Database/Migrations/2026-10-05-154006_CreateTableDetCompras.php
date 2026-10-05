<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetCompras extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetcompra'  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idcompra'     => ['type' => 'INT', 'constraint' => 11],
            'idproducto'   => ['type' => 'INT', 'constraint' => 11],
            'cantidad'     => ['type' => 'INT', 'constraint' => 11],
            'preciocompra' => ['type' => 'DECIMAL', 'constraint' => [10, 2]]
        ]);
        $this->forge->addPrimaryKey('iddetcompra');
        $this->forge->addForeignKey('idcompra', 'compras', 'idcompra', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detcompras', true);
    }

    public function down()
    {
        $this->forge->dropTable('detcompras');
    }
}
