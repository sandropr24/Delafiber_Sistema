<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTecnicos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idtecnico' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombres' => ['type' => 'VARCHAR', 'constraint' => 100],
            'apellidos' => ['type' => 'VARCHAR', 'constraint' => 100],
            'dni' => ['type' => 'VARCHAR', 'constraint' => 8],
            'telefono' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'especialidad' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'estado' => ['type' => 'ENUM', 'constraint' => ['ACTIVO', 'INACTIVO'], 'default' => 'ACTIVO'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('idtecnico', true);
        $this->forge->addUniqueKey('dni', 'uq_tecnicos_dni');
        $this->forge->createTable('tecnicos');
    }

    public function down()
    {
        $this->forge->dropTable('tecnicos', true);
    }
}
