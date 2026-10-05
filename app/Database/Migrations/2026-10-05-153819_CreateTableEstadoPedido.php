<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableEstadoPedido extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idestadopedido' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'estadopedido'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true]
        ]);
        $this->forge->addPrimaryKey('idestadopedido');
        $this->forge->createTable('estadopedido', true);
    }

    public function down()
    {
        $this->forge->dropTable('estadopedido');
    }
}
