<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableGarantias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idgarantia'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'iddetventa'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idserie'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'fechaingreso' => ['type' => 'DATETIME'],
            'falla'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'estado'       => [
                'type'       => 'ENUM',
                'constraint' => ['RECIBIDO', 'ENVIADO_PROVEEDOR', 'REPARADO', 'REEMPLAZADO', 'ENTREGADO_CLIENTE'],
                'default'    => 'RECIBIDO',
            ],
            'idproveedor' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'idusuario'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('idgarantia', true);
        $this->forge->addForeignKey('iddetventa', 'detventa', 'iddetventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idserie', 'series', 'idserie', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idproveedor', 'proveedores', 'idproveedor', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('garantias');

        $this->db->query("ALTER TABLE garantias MODIFY fechaingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down()
    {
        $this->forge->dropTable('garantias', true);
    }
}
