<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableAperturaDeCaja extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idapertura'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idcaja'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fechaapertura' => ['type' => 'DATETIME'],
            'montoinicial'  => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'fechacierre'   => ['type' => 'DATETIME', 'null' => true],
            'estado'        => [
                'type'       => 'ENUM',
                'constraint' => ['ABIERTA', 'CERRADA'],
                'default'    => 'ABIERTA',
            ],
            'observacion'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('idapertura', true);
        $this->forge->addForeignKey('idcaja', 'cajas', 'idcaja', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('aperturacaja'); 
    }

    public function down()
    {
        $this->forge->dropTable('aperturacaja', true);
    }
}
