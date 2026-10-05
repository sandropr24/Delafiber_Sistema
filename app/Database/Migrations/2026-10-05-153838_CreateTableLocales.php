<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableLocales extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idlocal'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'direccion'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'nombrelocal' => ['type' => 'VARCHAR', 'constraint' => 80],
            'tipolocal'   => ['type' => 'VARCHAR', 'constraint' => 40]
        ]);
        $this->forge->addPrimaryKey('idlocal');
        $this->forge->createTable('locales', true);
    }

    public function down()
    {
        $this->forge->dropTable('locales');
    }
}
