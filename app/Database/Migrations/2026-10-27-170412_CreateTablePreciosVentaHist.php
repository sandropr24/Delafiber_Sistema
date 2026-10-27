<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePreciosVentaHist extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idprecio' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idproducto'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'precioanterior' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'precionuevo'    => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'fecha'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idprecio', true);
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('preciosventa_hist');

        $this->db->query("ALTER TABLE preciosventa_hist MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('preciosventa_hist', true);
    }
}
