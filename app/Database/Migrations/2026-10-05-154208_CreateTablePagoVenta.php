<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePagoVenta extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpagoventa'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idventa'       => ['type' => 'INT', 'constraint' => 11],
            'idtipopago'    => ['type' => 'INT', 'constraint' => 11],
            'monto'         => ['type' => 'DECIMAL', 'constraint' => [10, 2]],
            'referencia'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'montorecibido' => ['type' => 'DECIMAL', 'constraint' => [10, 2], 'null' => true],
            'vuelto'        => ['type' => 'DECIMAL', 'constraint' => [10, 2], 'null' => true],
            'fecha'         => ['type' => 'DATETIME']
        ]);
        $this->forge->addPrimaryKey('idpagoventa');
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idtipopago', 'tipopago', 'idtipopago', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('pagoventa', true);
    }

    public function down()
    {
        $this->forge->dropTable('pagoventa');
    }
}
