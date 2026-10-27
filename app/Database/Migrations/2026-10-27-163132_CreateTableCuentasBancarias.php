<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCuentasBancarias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcuenta' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'banco' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'numerocuenta' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'cci' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'moneda' => [
                'type'       => 'ENUM',
                'constraint' => ['PEN', 'USD'],
                'default'    => 'PEN',
            ],
            'estado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('idcuenta', true);
        $this->forge->addUniqueKey(['banco', 'numerocuenta']);
        $this->forge->createTable('cuentasbancarias');
    }

    public function down()
    {
        $this->forge->dropTable('cuentasbancarias', true);
    }
}
