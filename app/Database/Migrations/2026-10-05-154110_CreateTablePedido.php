<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePedido extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpedido'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idestado'      => ['type' => 'INT', 'constraint' => 11],
            'idcotizacion'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'idlocal'       => ['type' => 'INT', 'constraint' => 11],
            'idpersona'     => ['type' => 'INT', 'constraint' => 11],
            'idusuario'     => ['type' => 'INT', 'constraint' => 11],
            'tipopedido'    => ['type' => 'VARCHAR', 'constraint' => 40],
            'fecha'         => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('idpedido');
        $this->forge->addForeignKey('idestado', 'estadopedido', 'idestadopedido', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('pedido', true);
    }

    public function down()
    {
        $this->forge->dropTable('pedido');
    }
}
