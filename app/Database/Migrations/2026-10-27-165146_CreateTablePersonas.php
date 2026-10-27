<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePersonas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpersona' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tipopersona' => [
                'type'       => 'ENUM',
                'constraint' => ['NATURAL', 'JURIDICA'],
                'default'    => 'NATURAL',
            ],
            'apellidos'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nombres'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'razonsocial' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tipodoc'     => [
                'type'       => 'ENUM',
                'constraint' => ['DNI', 'RUC', 'CE', 'PASAPORTE'],
            ],
            'numerodoc'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'direccion'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'codigocliente' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'estado'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('idpersona', true);
        $this->forge->addUniqueKey(['tipodoc', 'numerodoc']);
        $this->forge->createTable('personas');

        $this->db->query("ALTER TABLE personas ADD CONSTRAINT chk_personas_tipo CHECK (
            (tipopersona = 'NATURAL' AND nombres IS NOT NULL AND apellidos IS NOT NULL) OR
            (tipopersona = 'JURIDICA' AND razonsocial IS NOT NULL)
        )");
    }

    public function down()
    {
        $this->forge->dropTable('personas', true);
    }
}
