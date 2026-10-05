<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idproducto'   => ['type' => 'INT', 'auto_increment' => true],
            'idcategoria'  => ['type' => 'INT'],
            'descripcion'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'idmarca'      => ['type' => 'INT'],
            'modelo'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'codigobarras' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'unique' => true],
            'estado'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'precioventa'  => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addPrimaryKey('idproducto');
        $this->forge->addForeignKey('idcategoria', 'categorias', 'idcategoria', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idmarca', 'marcas', 'idmarca', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('productos', true);
    }

    public function down()
    {
        $this->forge->dropTable('productos', true);
    }
}
