<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SecureTiendaIdentity extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('pedido', ['idpersona' => ['type' => 'INT', 'unsigned' => true, 'null' => true]]);
        $this->forge->addColumn('cliente_cuentas', [
            'email_verificado_en' => ['type' => 'DATETIME', 'null' => true],
            'verificacion_token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'verificacion_expira_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_cliente_verificacion ON cliente_cuentas(verificacion_token_hash)');
    }

    public function down()
    {
        throw new \RuntimeException('No se retira automáticamente la verificación ni la identidad de pedidos anónimos.');
    }
}
