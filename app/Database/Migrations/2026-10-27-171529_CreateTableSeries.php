<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSeries extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idserie'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idproducto'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'numeroserie' => ['type' => 'VARCHAR', 'constraint' => 60],
            'idlocal'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'estado'      => [
                'type'       => 'ENUM',
                'constraint' => ['EN_STOCK', 'VENDIDO', 'EN_GARANTIA', 'BAJA'],
                'default'    => 'EN_STOCK',
            ],
            'iddetcompra' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'iddetventa'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('idserie', true);
        $this->forge->addUniqueKey(['idproducto', 'numeroserie']);
        $this->forge->addForeignKey('idproducto', 'productos', 'idproducto', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('iddetcompra', 'detcompra', 'iddetcompra', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('iddetventa', 'detventa', 'iddetventa', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('series');
    }

    public function down()
    {
        $this->forge->dropTable('series', true);
    }
}
