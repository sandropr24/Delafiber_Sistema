<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableEstadosTablas extends Migration
{
    public function up()
    {
        // Cotizacion
        $this->forge->addField([
            'idestado' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'estado'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true],
        ]);
        $this->forge->addKey('idestado', true);
        $this->forge->createTable('estadocotizacion');

        // Pedido
        $this->forge->addField([
            'idestado' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'estado'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true],
        ]);
        $this->forge->addKey('idestado', true);
        $this->forge->createTable('estadopedido');

        // Venta
        $this->forge->addField([
            'idestado' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'estado'   => ['type' => 'VARCHAR', 'constraint' => 40, 'unique' => true],
        ]);
        $this->forge->addKey('idestado', true);
        $this->forge->createTable('estadoventa');
    }

    public function down()
    {
        $this->forge->dropTable('estadocotizacion', true);
        $this->forge->dropTable('estadopedido', true);
        $this->forge->dropTable('estadoventa', true);
    }
}
