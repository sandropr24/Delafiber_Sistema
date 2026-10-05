<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idusuario'     => ['type' => 'INT', 'auto_increment' => true],
            'idpersona'     => ['type' => 'INT'],
            'nombreusuario' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'claveacceso'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'rol'           => ['type' => 'VARCHAR', 'constraint' => 30],
            'estado'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('idusuario');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('usuarios', true);
    }

    public function down()
    {
        $this->forge->dropTable('usuarios', true);
    }
}
