<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCanalesVenta extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcanal' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'canal' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'unique'     => true,
            ],
        ]);
        $this->forge->addKey('idcanal', true);
        $this->forge->createTable('canalesventa');
    }

    public function down()
    {
        $this->forge->dropTable('canalesventa', true);
    }
}
