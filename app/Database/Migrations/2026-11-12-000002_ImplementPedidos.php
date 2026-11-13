<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementPedidos extends Migration
{
    public function up()
    {
        if ($this->db->query('SELECT idpedido FROM ventas WHERE idpedido IS NOT NULL GROUP BY idpedido HAVING COUNT(*) > 1 LIMIT 1')->getRowArray()) {
            throw new \RuntimeException('Existen pedidos vinculados a varias ventas. Revise esos vínculos antes de migrar.');
        }
        $this->forge->addColumn('estadopedido', ['codigo' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true]]);
        $this->db->query('CREATE UNIQUE INDEX uq_estadopedido_codigo ON estadopedido(codigo)');
        foreach (['PENDIENTE' => 'Pendiente', 'CONFIRMADO' => 'Confirmado', 'CONVERTIDO' => 'Convertido a venta', 'CANCELADO' => 'Cancelado'] as $codigo => $etiqueta) {
            $estado = $this->db->table('estadopedido')->where('estado', $etiqueta)->get()->getRowArray();
            if ($estado) $this->db->table('estadopedido')->where('idestado', $estado['idestado'])->update(['codigo' => $codigo]);
            else $this->db->table('estadopedido')->insert(['estado' => $etiqueta, 'codigo' => $codigo]);
        }
        $this->forge->addColumn('pedido', [
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'descuento' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'tipodescuento' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'NINGUNO'],
            'valordescuento' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'motivodescuento' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'observaciones' => ['type' => 'TEXT', 'null' => true],
            'request_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'revision' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'fecha_confirmacion' => ['type' => 'DATETIME', 'null' => true],
            'fecha_cancelacion' => ['type' => 'DATETIME', 'null' => true],
            'fecha_conversion' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->db->query("UPDATE pedido SET codigo = CONCAT('PED-', LPAD(idpedido, GREATEST(6, LENGTH(idpedido)), '0'))");
        $this->db->query('CREATE UNIQUE INDEX uq_pedido_codigo ON pedido(codigo)');
        $this->db->query('CREATE UNIQUE INDEX uq_pedido_request_token ON pedido(request_token)');
        $this->db->query('CREATE INDEX ix_pedido_estado_fecha ON pedido(idestado, fecha)');
        $this->db->query('CREATE INDEX ix_pedido_cliente_fecha ON pedido(idpersona, fecha)');
        $this->db->query('CREATE INDEX ix_pedido_local_fecha ON pedido(idlocal, fecha)');
        $this->forge->addColumn('detpedido', [
            'nombre_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'config_snapshot' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->db->query('UPDATE pedido p LEFT JOIN (SELECT idpedido, SUM(ROUND(cantidad * preciounitario, 2)) AS importe FROM detpedido GROUP BY idpedido) d ON d.idpedido = p.idpedido SET p.subtotal = COALESCE(d.importe, 0), p.total = COALESCE(d.importe, 0)');
        $this->forge->addField([
            'idhistorial' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idpedido' => ['type' => 'INT', 'unsigned' => true],
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'evento' => ['type' => 'VARCHAR', 'constraint' => 30],
            'estado_anterior' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'estado_nuevo' => ['type' => 'VARCHAR', 'constraint' => 20],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'fecha' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idhistorial', true);
        $this->forge->addKey(['idpedido', 'fecha']);
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('historial_pedido');
        // Una venta por pedido durante toda su vida; las ventas directas mantienen NULL.
        $this->db->query('CREATE UNIQUE INDEX uq_ventas_pedido ON ventas(idpedido)');
    }

    public function down()
    {
        if ($this->db->table('historial_pedido')->countAllResults() || $this->db->table('pedido')->where('request_token IS NOT NULL', null, false)->countAllResults()) {
            throw new \RuntimeException('No se puede revertir mientras existan pedidos del módulo o historial comercial.');
        }
        $this->db->query('DROP INDEX uq_ventas_pedido ON ventas');
        $this->forge->dropTable('historial_pedido');
        foreach (['uq_pedido_codigo', 'uq_pedido_request_token', 'ix_pedido_estado_fecha', 'ix_pedido_cliente_fecha', 'ix_pedido_local_fecha'] as $indice) $this->db->query('DROP INDEX ' . $indice . ' ON pedido');
        $this->forge->dropColumn('detpedido', ['nombre_snapshot', 'config_snapshot']);
        $this->forge->dropColumn('pedido', ['codigo', 'subtotal', 'descuento', 'total', 'tipodescuento', 'valordescuento', 'motivodescuento', 'observaciones', 'request_token', 'updated_at', 'revision', 'fecha_confirmacion', 'fecha_cancelacion', 'fecha_conversion']);
        $this->forge->dropColumn('estadopedido', 'codigo');
    }
}
