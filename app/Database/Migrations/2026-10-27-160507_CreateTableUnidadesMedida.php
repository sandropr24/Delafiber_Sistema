<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUnidadesMedida extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idunidad' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'unidad' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'unique'     => true,
            ],
            'abreviatura' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'unique'     => true,
            ],
        ]);
        $this->forge->addKey('idunidad', true);
        $this->forge->createTable('unidadesmedida');
    }

    public function down()
    {
        $this->forge->dropTable('unidadesmedida', true);
    }
}
