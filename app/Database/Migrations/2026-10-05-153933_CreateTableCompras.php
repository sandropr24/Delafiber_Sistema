<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompras extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcompra'        => ['type' => 'INT', 'auto_increment' => true],
            'idproveedor'     => ['type' => 'INT'],
            'serie'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'fechacompra'     => ['type' => 'DATE'],
            'fecharegistro'   => ['type' => 'DATETIME'],
            'tipocomprobante' => ['type' => 'VARCHAR', 'constraint' => 30],
            'total'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addPrimaryKey('idcompra');
        $this->forge->addForeignKey('idproveedor', 'proveedores', 'idproveedor', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('compras', true);
    }

    public function down()
    {
        $this->forge->dropTable('compras', true);
    }
}
