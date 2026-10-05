<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTiposPago extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idtipopago' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tipopago'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true]
        ]);
        $this->forge->addPrimaryKey('idtipopago');
        $this->forge->createTable('tipospago', true);
    }

    public function down()
    {
        $this->forge->dropTable('tipospago');
    }
}
