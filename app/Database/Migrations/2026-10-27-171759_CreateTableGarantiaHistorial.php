<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableGarantiaHistorial extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idhistorial' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idgarantia'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'estado'      => [
                'type'       => 'ENUM',
                'constraint' => ['RECIBIDO', 'ENVIADO_PROVEEDOR', 'REPARADO', 'REEMPLAZADO', 'ENTREGADO_CLIENTE'],
            ],
            'fecha'       => ['type' => 'DATETIME'],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'idusuario'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('idhistorial', true);
        $this->forge->addForeignKey('idgarantia', 'garantias', 'idgarantia', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('garantia_historial');

        $this->db->query("ALTER TABLE garantia_historial MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('garantia_historial', true);
    }
}
