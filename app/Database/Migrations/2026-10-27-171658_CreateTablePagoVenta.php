<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePagoVenta extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idpagoventa'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idventa'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idcuota'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'idtipopago'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idapertura'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'idusuariocobro' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'monto'          => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'idcuenta'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'referencia'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'voucher'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'montorecibido'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'vuelto'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'fecha'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idpagoventa', true);
        $this->forge->addForeignKey('idventa', 'ventas', 'idventa', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcuota', 'cuotas', 'idcuota', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idtipopago', 'tipospago', 'idtipopago', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idapertura', 'aperturacaja', 'idapertura', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idusuariocobro', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcuenta', 'cuentasbancarias', 'idcuenta', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('pagoventa');

        $this->db->query("ALTER TABLE pagoventa MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->db->query("ALTER TABLE pagoventa ADD CONSTRAINT chk_pago_monto CHECK (monto > 0)");
    }

    public function down()
    {
        $this->forge->dropTable('pagoventa', true);
    }
}
