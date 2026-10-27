<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCajas extends Migration
{
     public function up()
    {
        $this->forge->addField([
            'idcaja'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idlocal' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'  => ['type' => 'VARCHAR', 'constraint' => 60],
            'estado'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('idcaja', true);
        $this->forge->addUniqueKey(['idlocal', 'nombre']);
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cajas');
    }

    public function down()
    {
        $this->forge->dropTable('cajas', true);
    }
}
