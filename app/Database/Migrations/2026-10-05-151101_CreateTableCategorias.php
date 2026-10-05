<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCategorias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcategoria' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'categoria'   => ['type' => 'VARCHAR', 'constraint' => 80, 'unique' => true]
        ]);
        $this->forge->addPrimaryKey('idcategoria');
        $this->forge->createTable('categorias', true);
    }

    public function down()
    {
        $this->forge->dropTable('categorias');
    }
}
