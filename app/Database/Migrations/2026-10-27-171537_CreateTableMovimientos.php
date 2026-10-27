<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMovimientos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idmovimiento' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idkardex'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo'         => [
                'type'       => 'ENUM',
                'constraint' => ['ENTRADA', 'SALIDA', 'AJUSTE', 'ANULACION'],
            ],
            'fecha'       => ['type' => 'DATETIME'],
            'cantidad'    => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'saldo'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'motivo'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'idusuario'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idcompra'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'idventa'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('idmovimiento', true);
        $this->forge->addForeignKey('idkardex', 'kardex', 'idkardex', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcompra', 'compras', 'idcompra', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('movimientos');

        $this->db->query("ALTER TABLE movimientos MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('movimientos', true);
    }
}
