<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConsumosServicio extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idconsumo' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idorden' => ['type' => 'INT', 'unsigned' => true],
            'idkardex' => ['type' => 'INT', 'unsigned' => true],
            'idmovimiento' => ['type' => 'INT', 'unsigned' => true],
            'cantidad' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'token_operacion' => ['type' => 'CHAR', 'constraint' => 32],
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'fecha' => ['type' => 'DATETIME']
        ]);

        $this->forge->addKey('idconsumo', true);
        $this->forge->addUniqueKey('token_operacion');
        $this->forge->addUniqueKey('idmovimiento');
        $this->forge->addKey(['idorden', 'fecha']);
        $this->forge->addKey('idkardex');
        $this->forge->addForeignKey('idorden', 'ordenes_servicio', 'idorden', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idkardex', 'kardex', 'idkardex', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('consumos_servicio', true);
    }

    public function down()
    {
        $this->forge->dropTable('consumos_servicio', true);
    }
}
