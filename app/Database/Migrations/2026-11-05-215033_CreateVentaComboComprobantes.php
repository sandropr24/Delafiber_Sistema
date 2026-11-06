<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVentaComboComponentes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcomponenteventa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'iddetventa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey('idcomponenteventa', true);
        $this->forge->addKey('iddetventa');
        $this->forge->addKey('idproducto');
        $this->forge->addForeignKey('iddetventa', 'detventa', 'iddetventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('venta_combo_componentes', true);
    }

    public function down()
    {
        $this->forge->dropTable('venta_combo_componentes', true);
    }
}
