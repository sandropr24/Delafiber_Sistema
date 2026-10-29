<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCuotas extends Migration
{
    
    public function up()
    {
        $this->forge->addField([
            'idcuota'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nrocuota'         => ['type' => 'SMALLINT', 'constraint' => 6],
            'fechavencimiento' => ['type' => 'DATE'],
            'monto'            => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'montopagado'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'estado'           => [
                'type'       => 'ENUM',
                'constraint' => ['PENDIENTE', 'PARCIAL', 'PAGADA'],
                'default'    => 'PENDIENTE',
            ],
        ]);
        $this->forge->addKey('idcuota', true);
        $this->forge->addUniqueKey(['idventa', 'nrocuota']);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cuotas');    
    }

    public function down()
    {
        $this->forge->dropTable('cuotas', true);
    }
}
