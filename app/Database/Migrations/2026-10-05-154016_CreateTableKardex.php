<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKardex extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idkardex'      => ['type' => 'INT', 'auto_increment' => true],
            'idproducto'    => ['type' => 'INT'],
            'minima'        => ['type' => 'INT', 'default' => 0],
            'maxima'        => ['type' => 'INT', 'default' => 0],
            'idlocal'       => ['type' => 'INT'],
            'stockactual'   => ['type' => 'INT', 'default' => 0],
            'costopromedio' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
        ]);
        $this->forge->addPrimaryKey('idkardex');
        $this->forge->addUniqueKey(['idproducto', 'idlocal']);
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('kardex', true);
    }

    public function down()
    {
        $this->forge->dropTable('kardex', true);
    }
}
