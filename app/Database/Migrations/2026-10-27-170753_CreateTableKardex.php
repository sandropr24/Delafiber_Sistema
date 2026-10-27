<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableKardex extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idkardex'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idproducto'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idlocal'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'minima'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'maxima'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'stockactual'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'costopromedio' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'default' => 0.0000],
        ]);
        $this->forge->addKey('idkardex', true);
        $this->forge->addUniqueKey(['idproducto', 'idlocal']);
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('kardex');

        $this->db->query("ALTER TABLE kardex ADD CONSTRAINT chk_kardex_stock CHECK (stockactual >= 0)");
    }

    public function down()
    {
        $this->forge->dropTable('kardex', true);
    }
}
