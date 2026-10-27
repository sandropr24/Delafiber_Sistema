<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePedido extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpedido'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tipopedido'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'idestado'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idcotizacion' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'fecha'        => ['type' => 'DATETIME'],
            'idlocal'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idpersona'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('idpedido', true);
        $this->forge->addForeignKey('idestado', 'estadopedido', 'idestado', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('pedido');

        $this->db->query("ALTER TABLE pedido MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('pedido', true);
    }
}
