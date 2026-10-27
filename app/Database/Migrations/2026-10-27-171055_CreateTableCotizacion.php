<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcotizacion'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'numero'           => ['type' => 'VARCHAR', 'constraint' => 30, 'unique' => true],
            'idlinea'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha'            => ['type' => 'DATETIME'],
            'fechavencimiento' => ['type' => 'DATE'],
            'tipocambio'       => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 1.0000],
            'porcentajeigv'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 18.00],
            'subtotal'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'montoigv'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'total'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'observacion'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'idestado'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idlocal'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idpersona'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('idcotizacion', true);
        $this->forge->addForeignKey('idlinea', 'lineas', 'idlinea', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idestado', 'estadocotizacion', 'idestado', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cotizacion');

        $this->db->query("ALTER TABLE cotizacion MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->db->query("ALTER TABLE cotizacion ADD CONSTRAINT chk_cot_venc CHECK (fechavencimiento >= DATE(fecha))");
        $this->db->query("ALTER TABLE cotizacion ADD CONSTRAINT chk_cot_tc CHECK (tipocambio > 0)");
        $this->db->query("ALTER TABLE cotizacion ADD CONSTRAINT chk_cot_igv CHECK (porcentajeigv >= 0)");
        $this->db->query("ALTER TABLE cotizacion ADD CONSTRAINT chk_cot_tot CHECK (total = subtotal + montoigv)");
    }

    public function down()
    {
        $this->forge->dropTable('cotizacion', true);
    }
}
