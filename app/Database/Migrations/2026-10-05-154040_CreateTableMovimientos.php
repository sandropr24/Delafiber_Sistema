<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMovimientos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idmovimiento'  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'idkardex'      => ['type' => 'INT', 'constraint' => 11],
            'idcompra'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'idusuario'     => ['type' => 'INT', 'constraint' => 11],
            'tipo'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'fecha'         => ['type' => 'DATETIME'],
            'cantidad'      => ['type' => 'INT', 'constraint' => 11],
            'saldo'         => ['type' => 'INT', 'constraint' => 11],
            'descripcion'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'motivo'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true]
        ]);
        $this->forge->addPrimaryKey('idmovimiento');
        $this->forge->addForeignKey('idkardex', 'kardex', 'idkardex', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcompra', 'compras', 'idcompra', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('movimientos', true);
    }

    public function down()
    {
        $this->forge->dropTable('movimientos');
    }
}
