<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcotizacion' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idestado'     => ['type' => 'INT', 'constraint' => 11],
            'idlocal'      => ['type' => 'INT', 'constraint' => 11],
            'idusuario'    => ['type' => 'INT', 'constraint' => 11],
            'idpersona'    => ['type' => 'INT', 'constraint' => 11],
            'fecha'        => ['type' => 'DATETIME'],
            'fechavencimiento' => ['type' => 'DATE'],
            'observacion'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('idcotizacion');
        $this->forge->addForeignKey('idestado', 'estadocotizacion', 'idestadocotizacion', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cotizacion', true);
    }

    public function down()
    {
        $this->forge->dropTable('cotizacion');
    }
}
