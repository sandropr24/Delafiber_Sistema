<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableReservasStock extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idreserva' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idventa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'idkardex' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cantidad' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'estado' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'RESERVADO',
                    'CONSUMIDO',
                    'LIBERADO'
                ],
                'default' => 'RESERVADO',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('idreserva');

        $this->forge->addKey('idventa');
        $this->forge->addKey('idkardex');
        $this->forge->addKey(['idkardex', 'estado']);

        $this->forge->addForeignKey(
            'idventa',
            'ventas',
            'idventa',
            'RESTRICT',
            'RESTRICT'
        );

        $this->forge->addForeignKey(
            'idkardex',
            'kardex',
            'idkardex',
            'RESTRICT',
            'RESTRICT'
        );

        $this->forge->createTable('reservas_stock', true);
    }

    public function down()
    {
        $this->forge->dropTable('reservas_stock', true);
    }
}
