<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableVentas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idventa'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idcliente'     => ['type' => 'INT', 'constraint' => 11],
            'idlocal'       => ['type' => 'INT', 'constraint' => 11],
            'idpedido'      => ['type' => 'INT', 'constraint' => 11],
            'idcajero'      => ['type' => 'INT', 'constraint' => 11],
            'tipodocumento' => ['type' => 'VARCHAR', 'constraint' => 20],
            'serie'         => ['type' => 'VARCHAR', 'constraint' => 10],
            'numero'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'estafacturado' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'cdr'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fecha'         => ['type' => 'DATETIME'],
            'total'         => ['type' => 'DECIMAL', 'constraint' => [10, 2]],
        ]);
        $this->forge->addPrimaryKey('idventa');
        $this->forge->addUniqueKey(['tipodocumento', 'serie', 'numero']);
        $this->forge->addForeignKey('idcliente', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcajero', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('ventas', true);
    }

    public function down()
    {
        $this->forge->dropTable('ventas');
    }
}
