<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCompras extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcompra'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idproveedor'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipocomprobante' => ['type' => 'VARCHAR', 'constraint' => 30],
            'serie'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'numero'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'fechacompra'     => ['type' => 'DATE'],
            'fecharegistro'   => ['type' => 'DATETIME'],
            'moneda'          => ['type' => 'ENUM', 'constraint' => ['PEN', 'USD'], 'default' => 'USD'],
            'tipocambio'      => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 1.0000],
            'total'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey('idcompra', true);
        $this->forge->addUniqueKey(['idproveedor', 'tipocomprobante', 'serie', 'numero']);
        $this->forge->addForeignKey('idproveedor', 'proveedores', 'idproveedor', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('compras');
    }

    public function down()
    {
        $this->forge->dropTable('compras', true);
    }
}
