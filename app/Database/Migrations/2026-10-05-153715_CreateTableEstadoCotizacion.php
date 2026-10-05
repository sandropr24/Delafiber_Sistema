<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableEstadoCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idestadocotizacion' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'estadocotizacion'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true]
        ]);
        $this->forge->addPrimaryKey('idestadocotizacion');
        $this->forge->createTable('estadocotizacion', true);
    }

    public function down()
    {
        $this->forge->dropTable('estadocotizacion');
    }
}
