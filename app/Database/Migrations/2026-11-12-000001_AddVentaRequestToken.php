<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVentaRequestToken extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ventas', [
            'request_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_ventas_request_token ON ventas (request_token)');
    }

    public function down()
    {
        $this->forge->dropColumn('ventas', 'request_token');
    }
}
