<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RegistroIncidencia extends Migration
{
    public function up()
        {
            $this->db->query("ALTER TABLE entregas MODIFY COLUMN estado ENUM('PENDIENTE','EN_CAMINO','ENTREGADO','REPROGRAMADO','INCIDENCIA','RECHAZADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE'");

            $this->forge->addField([
                'idincidencia' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'identrega' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'idusuario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'idtecnico' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 40],
                'motivo' => ['type' => 'TEXT'],
                'estado_anterior' => ['type' => 'VARCHAR', 'constraint' => 30],
                'estado_nuevo' => ['type' => 'VARCHAR', 'constraint' => 30],
                'fechaintento' => ['type' => 'DATETIME'],
                'fechareprogramada' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('idincidencia', true);
            $this->forge->addKey(['identrega', 'fechaintento']);
            $this->forge->addForeignKey('identrega', 'entregas', 'identrega', 'CASCADE', 'RESTRICT');
            $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
            $this->forge->addForeignKey('idtecnico', 'tecnicos', 'idtecnico', 'SET NULL', 'RESTRICT');
            $this->forge->createTable('entrega_incidencias', true);
        }

        public function down()
        {
            $this->forge->dropTable('entrega_incidencias', true);
        }
}
