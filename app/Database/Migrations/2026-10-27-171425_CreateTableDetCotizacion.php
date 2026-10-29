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
    }

    public function down()
    {
        $this->forge->dropTable('detcotizacion', true);
    }
}
