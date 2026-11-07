<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudesAnulacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idsolicitud' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idsolicitante' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'motivo' => ['type' => 'TEXT'],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['PENDIENTE', 'AUTORIZADA', 'RECHAZADA'],
                'default' => 'PENDIENTE',
            ],
            'fecha_solicitud' => ['type' => 'DATETIME'],
            'idrevisor' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'observacion_revision' => ['type' => 'TEXT', 'null' => true],
            'fecha_revision' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('idsolicitud');
        $this->forge->addKey(['idventa', 'estado']);
        $this->forge->addKey(['estado', 'fecha_solicitud']);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idsolicitante', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idrevisor', 'usuarios', 'idusuario', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('solicitudes_anulacion');
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes_anulacion', true);
    }
}
