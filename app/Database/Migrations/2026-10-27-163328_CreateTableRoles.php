<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRoles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idrol' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'rol' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'unique'     => true,
            ],
        ]);
        $this->forge->addKey('idrol', true);
        $this->forge->createTable('roles');
    }

    public function down()
    {
        $this->forge->dropTable('roles', true);
    }
}
