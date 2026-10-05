<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddetcotizacion' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idcotizacion'    => ['type' => 'INT', 'constraint' => 11],
            'idproducto'      => ['type' => 'INT', 'constraint' => 11],
            'cantidad'        => ['type' => 'INT', 'constraint' => 11],
            'preciounitario'  => ['type' => 'DECIMAL', 'constraint' => [10, 2]],
            'total'           => ['type' => 'DECIMAL', 'constraint' => [10, 2]]
        ]);
        $this->forge->addPrimaryKey('iddetcotizacion');
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('detcotizacion', true);
    }

    public function down()
    {
        $this->forge->dropTable('detcotizacion');
    }
}
