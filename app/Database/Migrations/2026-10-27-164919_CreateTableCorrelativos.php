<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCorrelativos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcorrelativo' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tipodocumento' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'prefijo' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
            ],
            'anio' => [
                'type'       => 'SMALLINT',
                'constraint' => 6,
                'default'    => 0,
            ],
            'ultimonumero' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('idcorrelativo', true);
        $this->forge->addUniqueKey(['tipodocumento', 'prefijo', 'anio']);
        $this->forge->createTable('correlativos');
    }

    public function down()
    {
        $this->forge->dropTable('correlativos', true);
    }
}
