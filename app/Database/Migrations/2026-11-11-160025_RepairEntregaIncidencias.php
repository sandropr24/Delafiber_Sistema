<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class RepairEntregaIncidencias extends Migration
{
    public function up()
    {
        // This repairs an installation where RegistroIncidencia was marked as
        // migrated but its table was not created. Never modify existing rows.
        if ($this->db->tableExists('entrega_incidencias')) {
            return;
        }

        foreach (['entregas', 'usuarios', 'tecnicos'] as $table) {
            if (!$this->db->tableExists($table)) {
                throw new RuntimeException("No se puede reparar incidencias: falta la tabla {$table}.");
            }
        }

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
        $this->forge->addForeignKey('identrega', 'entregas', 'identrega', 'RESTRICT', 'RESTRICT', 'fk_ei_fix_20261111_entrega');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT', 'fk_ei_fix_20261111_usuario');
        $this->forge->addForeignKey('idtecnico', 'tecnicos', 'idtecnico', 'RESTRICT', 'SET NULL', 'fk_ei_fix_20261111_tecnico');

        if (!$this->forge->createTable('entrega_incidencias', false)) {
            throw new RuntimeException('No se pudo crear entrega_incidencias. Revisa los logs de MariaDB.');
        }

        if (!$this->db->tableExists('entrega_incidencias')) {
            throw new RuntimeException('La migración terminó sin crear entrega_incidencias.');
        }
    }

    public function down()
    {
        // Do not DROP the incident audit history during a rollback.
        // A deliberate and separately reviewed data migration is required.
    }
}
