<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetcotizacion' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idcotizacion'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idproducto'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad'        => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'preciounitario'  => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        ]);
        $this->forge->addKey('iddetcotizacion', true);
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detcotizacion');

        $this->db->query("ALTER TABLE detcotizacion ADD total DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(cantidad * preciounitario, 2)) STORED");
        $this->db->query("ALTER TABLE detcotizacion ADD CONSTRAINT chk_detcot_cant CHECK (cantidad > 0)");
        $this->db->query("ALTER TABLE detcotizacion ADD CONSTRAINT chk_detcot_precio CHECK (preciounitario >= 0)");
    }

    public function down()
    {
        $this->forge->dropTable('detcotizacion', true);
    }
}
