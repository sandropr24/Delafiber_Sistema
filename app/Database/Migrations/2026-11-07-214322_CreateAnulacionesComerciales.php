<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnulacionesComerciales extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idanulacion' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idsolicitud' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha_anulacion' => ['type' => 'DATETIME'],
            'observacion_inventario' => ['type' => 'TEXT'],
            'monto_pagado' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'monto_pendiente_reembolso' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'estado_reembolso' => [
                'type' => 'ENUM',
                'constraint' => ['NO_APLICA', 'PENDIENTE', 'COMPLETADO'],
                'default' => 'NO_APLICA',
            ],
        ]);
        $this->forge->addPrimaryKey('idanulacion');
        $this->forge->addUniqueKey('idventa');
        $this->forge->addUniqueKey('idsolicitud');
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idsolicitud', 'solicitudes_anulacion', 'idsolicitud', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('anulaciones_comerciales');
    }

    public function down()
    {
        if ($this->db->table('anulaciones_comerciales')->countAllResults() > 0) {
            throw new \RuntimeException('Hay anulaciones registradas; no se puede eliminar su historial.');
        }
        $this->forge->dropTable('anulaciones_comerciales', true);
    }
}
