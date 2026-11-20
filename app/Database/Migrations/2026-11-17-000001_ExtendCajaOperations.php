<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendCajaOperations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idmovimientocaja' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idapertura' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idtipopago' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo' => ['type' => 'ENUM', 'constraint' => ['INGRESO', 'EGRESO']],
            'concepto' => ['type' => 'ENUM', 'constraint' => ['APORTE', 'RETIRO', 'GASTO', 'REEMBOLSO', 'AJUSTE']],
            'monto' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 255],
            'fecha' => ['type' => 'DATETIME'],
            'token_operacion' => ['type' => 'CHAR', 'constraint' => 64],
        ]);
        $this->forge->addKey('idmovimientocaja', true);
        $this->forge->addUniqueKey('token_operacion');
        $this->forge->addKey(['idapertura', 'idtipopago', 'idmovimientocaja']);
        $this->forge->addForeignKey('idapertura', 'aperturacaja', 'idapertura', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idtipopago', 'tipospago', 'idtipopago', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('movimientos_caja');

        $this->forge->addField([
            'idreembolso' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idanulacion' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idpagoventa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idmovimientocaja' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idusuario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'monto' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'referencia' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'fecha' => ['type' => 'DATETIME'],
            'token_operacion' => ['type' => 'CHAR', 'constraint' => 64],
        ]);
        $this->forge->addKey('idreembolso', true);
        $this->forge->addUniqueKey('idmovimientocaja');
        $this->forge->addUniqueKey('token_operacion');
        $this->forge->addKey(['idanulacion', 'idpagoventa']);
        $this->forge->addForeignKey('idanulacion', 'anulaciones_comerciales', 'idanulacion', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idpagoventa', 'pagoventa', 'idpagoventa', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idmovimientocaja', 'movimientos_caja', 'idmovimientocaja', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('reembolsos_venta');

        $this->forge->addField([
            'idcierreadministrativo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idapertura' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idadministrador' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha' => ['type' => 'DATETIME'],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addKey('idcierreadministrativo', true);
        $this->forge->addUniqueKey('idapertura');
        $this->forge->addForeignKey('idapertura', 'aperturacaja', 'idapertura', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idadministrador', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('cierres_caja_administrativos');

        $this->forge->addColumn('pagoventa', [
            'idtecnicocobro' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'idusuariocobro'],
            'identrega' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'idtecnicocobro'],
            'token_operacion' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'fecha'],
        ]);
        $this->db->query('ALTER TABLE pagoventa ADD CONSTRAINT fk_pagoventa_tecnico_cobro FOREIGN KEY (idtecnicocobro) REFERENCES tecnicos(idtecnico) ON DELETE SET NULL ON UPDATE RESTRICT');
        $this->db->query('ALTER TABLE pagoventa ADD CONSTRAINT fk_pagoventa_entrega FOREIGN KEY (identrega) REFERENCES entregas(identrega) ON DELETE SET NULL ON UPDATE RESTRICT');
        $this->db->query('CREATE INDEX ix_pagoventa_venta ON pagoventa (idventa, idpagoventa)');
        $this->db->query('CREATE INDEX ix_pagoventa_tecnico_fecha ON pagoventa (idtecnicocobro, fecha)');
        $this->db->query('CREATE UNIQUE INDEX ux_pagoventa_token_operacion ON pagoventa (token_operacion)');

        $this->db->query('DROP VIEW IF EXISTS vw_arqueo_esperado');
        $this->db->query("CREATE VIEW vw_arqueo_esperado AS
            SELECT a.idapertura, tp.idtipopago, tp.tipopago,
                IF(tp.tipopago = 'Efectivo', a.montoinicial, 0)
                + COALESCE(p.pagado, 0) + COALESCE(m.movimiento, 0) AS montoesperado
            FROM aperturacaja a
            CROSS JOIN tipospago tp
            LEFT JOIN (
                SELECT idapertura, idtipopago, SUM(monto) AS pagado
                FROM pagoventa GROUP BY idapertura, idtipopago
            ) p ON p.idapertura = a.idapertura AND p.idtipopago = tp.idtipopago
            LEFT JOIN (
                SELECT idapertura, idtipopago,
                    SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE -monto END) AS movimiento
                FROM movimientos_caja GROUP BY idapertura, idtipopago
            ) m ON m.idapertura = a.idapertura AND m.idtipopago = tp.idtipopago");
    }

    public function down()
    {
        $this->db->query('DROP VIEW IF EXISTS vw_arqueo_esperado');
        $this->db->query('DROP INDEX ix_pagoventa_tecnico_fecha ON pagoventa');
        $this->db->query('DROP INDEX ux_pagoventa_token_operacion ON pagoventa');
        $this->db->query('DROP INDEX ix_pagoventa_venta ON pagoventa');
        $this->db->query('ALTER TABLE pagoventa DROP FOREIGN KEY fk_pagoventa_entrega');
        $this->db->query('ALTER TABLE pagoventa DROP FOREIGN KEY fk_pagoventa_tecnico_cobro');
        $this->forge->dropColumn('pagoventa', ['token_operacion', 'identrega', 'idtecnicocobro']);
        $this->forge->dropTable('cierres_caja_administrativos', true);
        $this->forge->dropTable('reembolsos_venta', true);
        $this->forge->dropTable('movimientos_caja', true);
    }
}
