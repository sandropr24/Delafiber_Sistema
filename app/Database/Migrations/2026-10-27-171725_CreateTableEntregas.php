<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableEntregas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'identrega'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'tipoentrega' => [
                'type'       => 'ENUM',
                'constraint' => ['RECOJO_TIENDA', 'DOMICILIO'],
            ],
            'direccion'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'referencia'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'idtecnico'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'estado'             => [
                'type'       => 'ENUM',
                'constraint' => ['PENDIENTE', 'EN_CAMINO', 'ENTREGADO'],
                'default'    => 'PENDIENTE',
            ],
            'fechaencamino'      => ['type' => 'DATETIME', 'null' => true],
            'fechaentrega'       => ['type' => 'DATETIME', 'null' => true],
            'cobrocontraentrega' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'idpagoventa'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('identrega', true);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idtecnico', 'usuarios', 'idusuario', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idpagoventa', 'pagoventa', 'idpagoventa', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('entregas');

        $this->db->query("ALTER TABLE entregas ADD CONSTRAINT chk_ent_tipo CHECK (tipoentrega = 'RECOJO_TIENDA' OR (direccion IS NOT NULL AND telefono IS NOT NULL AND idtecnico IS NOT NULL))");
        $this->db->query("ALTER TABLE entregas ADD CONSTRAINT chk_ent_estado CHECK (estado <> 'ENTREGADO' OR fechaentrega IS NOT NULL)");
    }

    public function down()
    {
        $this->forge->dropTable('entregas', true);
    }
}
