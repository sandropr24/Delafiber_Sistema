<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCategorias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcategoria' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idcategoriapadre' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'estado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('idcategoria', true);
        $this->forge->addUniqueKey(['idcategoriapadre', 'categoria']);
        $this->forge->addForeignKey('idcategoriapadre', 'categorias', 'idcategoria', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('categorias');
    }

    public function down()
    {
        $this->forge->dropTable('categorias', true);
    }
}
