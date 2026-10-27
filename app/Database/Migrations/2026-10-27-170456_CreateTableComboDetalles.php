<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableComboDetalles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcombo'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad'   => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey(['idcombo', 'idproducto'], true);
        $this->forge->addForeignKey('idcombo', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('combo_detalle');

        $this->db->query("ALTER TABLE combo_detalle ADD CONSTRAINT chk_combo_cant CHECK (cantidad > 0)");
        $this->db->query("ALTER TABLE combo_detalle ADD CONSTRAINT chk_combo_diff CHECK (idcombo <> idproducto)");
    }

    public function down()
    {
        $this->forge->dropTable('combo_detalle', true);
    }
}
