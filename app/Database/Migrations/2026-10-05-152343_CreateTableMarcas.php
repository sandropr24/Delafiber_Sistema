<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMarcas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idmarca' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'marca'   => ['type' => 'VARCHAR', 'constraint' => 80, 'unique' => true]
        ]);
        $this->forge->addPrimaryKey('idmarca');
        $this->forge->createTable('marcas', true);
    }

    public function down()
    {
        $this->forge->dropTable('marcas');
    }
}
