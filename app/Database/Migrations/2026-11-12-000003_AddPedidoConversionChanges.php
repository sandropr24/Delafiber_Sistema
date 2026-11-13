<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPedidoConversionChanges extends Migration
{
    public function up()
    {
        $this->forge->addColumn('historial_pedido', ['cambios' => ['type' => 'MEDIUMTEXT', 'null' => true]]);
    }

    public function down()
    {
        if ($this->db->table('historial_pedido')->where('cambios IS NOT NULL', null, false)->countAllResults()) {
            throw new \RuntimeException('No se puede retirar el registro de cambios de ventas ya realizadas.');
        }
        $this->forge->dropColumn('historial_pedido', 'cambios');
    }
}
