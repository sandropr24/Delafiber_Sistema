<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProveedores extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idproveedor'     => ['type' => 'INT', 'auto_increment' => true],
            'razonsocial'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'ruc'             => ['type' => 'CHAR', 'constraint' => 11, 'unique' => true],
            'direccion'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nombrecomercial' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('idproveedor');
        $this->forge->createTable('proveedores', true);
    }

    public function down()
    {
        $this->forge->dropTable('proveedores', true);
    }
}
