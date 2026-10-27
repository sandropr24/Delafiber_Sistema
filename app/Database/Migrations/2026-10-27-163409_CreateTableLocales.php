<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableLocales extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idlocal' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'direccion' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'nombrelocal' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'tipolocal' => [
                'type'       => 'ENUM',
                'constraint' => ['TIENDA', 'ALMACEN', 'OFICINA'],
                'default'    => 'TIENDA',
            ],
        ]);
        $this->forge->addKey('idlocal', true);
        $this->forge->createTable('locales');
    }

    public function down()
    {
        $this->forge->dropTable('locales', true);
    }
}
