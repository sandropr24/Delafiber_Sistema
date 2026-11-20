<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementTiendaWeb extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE pedido MODIFY idusuario INT UNSIGNED NULL');
        $this->db->query('ALTER TABLE historial_pedido MODIFY idusuario INT UNSIGNED NULL');

        $this->forge->addField([
            'idcliente_cuenta' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idpersona' => ['type' => 'INT', 'unsigned' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100],
            'clave_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'creado_en' => ['type' => 'DATETIME'],
            'ultimo_acceso' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('idcliente_cuenta', true);
        $this->forge->addUniqueKey('idpersona');
        $this->forge->addUniqueKey('email');
        $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('cliente_cuentas');

        $this->forge->addField([
            'idpedido_web' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idpedido' => ['type' => 'INT', 'unsigned' => true],
            'idcliente_cuenta' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'modalidad' => ['type' => 'ENUM', 'constraint' => ['RECOJO', 'DOMICILIO']],
            'direccion_entrega' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'nombre_contacto' => ['type' => 'VARCHAR', 'constraint' => 150],
            'email_contacto' => ['type' => 'VARCHAR', 'constraint' => 100],
            'telefono_contacto' => ['type' => 'VARCHAR', 'constraint' => 20],
            'token_seguimiento_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'creado_en' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idpedido_web', true);
        $this->forge->addUniqueKey('idpedido');
        $this->forge->addUniqueKey('token_seguimiento_hash');
        $this->forge->addKey(['idcliente_cuenta', 'creado_en']);
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idcliente_cuenta', 'cliente_cuentas', 'idcliente_cuenta', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('pedido_web');
    }

    public function down()
    {
        if ($this->db->table('pedido_web')->countAllResults() || $this->db->table('cliente_cuentas')->countAllResults()) {
            throw new \RuntimeException('No se puede revertir mientras existan cuentas o pedidos de la tienda web.');
        }
        $this->forge->dropTable('pedido_web');
        $this->forge->dropTable('cliente_cuentas');
        $this->db->query('ALTER TABLE historial_pedido MODIFY idusuario INT UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE pedido MODIFY idusuario INT UNSIGNED NOT NULL');
    }
}
