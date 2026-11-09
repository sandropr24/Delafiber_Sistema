<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescuentoFieldsToVentas extends Migration
{
    public function up()
        {
            $this->forge->addColumn('ventas', [
                'tipodescuento' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'NINGUNO'],
                'valordescuento' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'motivodescuento' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            ]);
        }

        public function down()
        {
            $this->forge->dropColumn('ventas', ['tipodescuento', 'valordescuento', 'motivodescuento']);
        }
}
