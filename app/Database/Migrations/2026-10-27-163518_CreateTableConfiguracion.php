<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableConfiguracion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'clave' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'valor' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);
        $this->forge->addKey('clave', true);
        $this->forge->createTable('configuracion');
    }

    public function down()
    {
        $this->forge->dropTable('configuracion', true);
    }
}
