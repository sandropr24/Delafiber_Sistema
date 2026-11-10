<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdenesServicio extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idorden' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idcliente' => ['type' => 'INT', 'unsigned' => true],
            'idventa' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'idservicio' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'idtecnico' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'titulo' => ['type' => 'VARCHAR', 'constraint' => 150],
            'descripcion' => ['type' => 'TEXT', 'null' => true],
            'direccion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'telefono' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'fecha_inicio' => ['type' => 'DATETIME', 'null' => true],
            'fecha_fin' => ['type' => 'DATETIME', 'null' => true],
            'fecha_finalizacion' => ['type' => 'DATETIME', 'null' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDIENTE'],
            'observaciones' => ['type' => 'TEXT', 'null' => true],
            'resultado' => ['type' => 'TEXT', 'null' => true],
            'idusuario_crea' => ['type' => 'INT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
        ]);

        $this->forge->addKey('idorden', true);
        $this->forge->addKey('idcliente');
        $this->forge->addKey('idventa');
        $this->forge->addKey('idservicio');
        $this->forge->addKey(['idtecnico', 'fecha_inicio', 'fecha_fin']);
        $this->forge->addKey('estado');

        $this->forge->addForeignKey('idcliente', 'personas', 'idpersona', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idservicio', 'productos', 'idproducto', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idtecnico', 'tecnicos', 'idtecnico', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario_crea', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('ordenes_servicio', true);

        $this->forge->addField([
            'idhistorial' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idorden' => ['type' => 'INT', 'unsigned' => true],
            'tipoevento' => ['type' => 'VARCHAR', 'constraint' => 35],
            'estado_anterior' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'estado_nuevo' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'fecha' => ['type' => 'DATETIME']
        ]);

        $this->forge->addKey('idhistorial', true);
        $this->forge->addKey(['idorden', 'fecha']);

        $this->forge->addForeignKey('idorden', 'ordenes_servicio', 'idorden', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('historial_orden_servicio', true);
    }

    public function down()
    {
        $this->forge->dropTable('historial_orden_servicio', true);
        $this->forge->dropTable('ordenes_servicio', true);
    }
}
