<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCreditosInternet extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcredito' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'idpersona' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'codigocliente' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'monto'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'nrocuotas'     => ['type' => 'SMALLINT', 'constraint' => 6, 'default' => 1],
            'estado'        => [
                'type'       => 'ENUM',
                'constraint' => ['PENDIENTE', 'COBRADO'],
                'default'    => 'PENDIENTE',
            ],
            'fecharegistro'  => ['type' => 'DATETIME'],
            'fechacobro'     => ['type' => 'DATETIME', 'null' => true],
            'idusuariocobro' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('idcredito', true);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idusuariocobro', 'usuarios', 'idusuario', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('creditosinternet');  
    }

    public function down()
    {
        $this->forge->dropTable('creditosinternet', true);
    }
}
