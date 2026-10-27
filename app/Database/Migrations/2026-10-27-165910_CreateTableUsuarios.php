<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idusuario' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idpersona' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idrol'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombreusuario' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'claveacceso'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'estado'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('idusuario', true);
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idrol', 'roles', 'idrol', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('usuarios', true);
    }
}
