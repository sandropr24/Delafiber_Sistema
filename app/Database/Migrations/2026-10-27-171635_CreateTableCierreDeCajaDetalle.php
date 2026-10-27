<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCierreDeCajaDetalle extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idapertura'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idtipopago'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'montoesperado' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'montocontado'  => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'observacion'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey(['idapertura', 'idtipopago'], true);
        $this->forge->addForeignKey('idapertura', 'aperturacaja', 'idapertura', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idtipopago', 'tipospago', 'idtipopago', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cierrecaja_detalle');

        $this->db->query("ALTER TABLE cierrecaja_detalle ADD diferencia DECIMAL(10,2) GENERATED ALWAYS AS (montocontado - montoesperado) STORED");
    }

    public function down()
    {
        $this->forge->dropTable('cierrecaja_detalle', true);
    }
}
