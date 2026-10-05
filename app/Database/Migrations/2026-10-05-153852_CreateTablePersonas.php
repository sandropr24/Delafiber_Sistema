<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePersonas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpersona'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'apellidos'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'nombres'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipodoc'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'numerodoc'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'direccion'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('idpersona');
        $this->forge->createTable('personas', true);
    }

    public function down()
    {
        $this->forge->dropTable('personas');
    }
}
