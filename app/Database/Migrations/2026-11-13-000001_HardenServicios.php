<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class HardenServicios extends Migration
{
    public function up()
    {
        $invalida = $this->db->query("SELECT idorden FROM ordenes_servicio WHERE estado NOT IN ('PENDIENTE','PROGRAMADO','EN_PROCESO','FINALIZADO','CANCELADO') LIMIT 1")->getRowArray();
        if ($invalida) {
            throw new RuntimeException('Corrige los estados inválidos de órdenes de servicio antes de migrar.');
        }

        $huerfano = $this->db->query('SELECT cs.idconsumo FROM consumos_servicio cs LEFT JOIN movimientos m ON m.idmovimiento = cs.idmovimiento WHERE m.idmovimiento IS NULL LIMIT 1')->getRowArray();
        if ($huerfano) {
            throw new RuntimeException('Existen consumos de servicio sin movimiento de Kardex.');
        }

        $duplicada = $this->db->query('SELECT idventa, idservicio FROM ordenes_servicio WHERE idventa IS NOT NULL GROUP BY idventa, idservicio HAVING COUNT(*) > 1 LIMIT 1')->getRowArray();
        if ($duplicada) {
            throw new RuntimeException('Hay varias órdenes históricas para una misma venta y servicio. Define manualmente sus cantidades antes de migrar.');
        }

        $cantidadCombo = $this->db->tableExists('venta_combo_componentes')
            ? " + COALESCE((SELECT SUM(c.cantidad) FROM venta_combo_componentes c JOIN detventa dc ON dc.iddetventa = c.iddetventa WHERE dc.idventa = os.idventa AND c.idproducto = os.idservicio), 0)"
            : '';
        $sinCantidadVendida = $this->db->query("SELECT os.idorden FROM ordenes_servicio os WHERE os.idventa IS NOT NULL AND
            COALESCE((SELECT SUM(d.cantidad) FROM detventa d WHERE d.idventa = os.idventa AND d.idproducto = os.idservicio), 0){$cantidadCombo} <= 0 LIMIT 1")->getRowArray();
        if ($sinCantidadVendida) {
            throw new RuntimeException('Hay órdenes vinculadas que no corresponden a una cantidad de servicio vendida.');
        }

        $this->forge->addColumn('ordenes_servicio', [
            'cantidad_servicio' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'idservicio'],
            'idunidad_servicio' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'cantidad_servicio'],
            'unidad_servicio_snapshot' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'idunidad_servicio'],
        ]);

        $this->db->query("UPDATE ordenes_servicio os
            LEFT JOIN productos p ON p.idproducto = os.idservicio
            LEFT JOIN unidadesmedida um ON um.idunidad = p.idunidad
            SET os.cantidad_servicio = CASE WHEN os.idventa IS NULL THEN 1.00 ELSE
                COALESCE((SELECT SUM(d.cantidad) FROM detventa d WHERE d.idventa = os.idventa AND d.idproducto = os.idservicio), 0){$cantidadCombo} END,
                os.idunidad_servicio = p.idunidad,
                os.unidad_servicio_snapshot = COALESCE(um.abreviatura, 'SERV')");

        $sinCantidad = $this->db->query('SELECT idorden FROM ordenes_servicio WHERE cantidad_servicio IS NULL OR cantidad_servicio <= 0 LIMIT 1')->getRowArray();
        if ($sinCantidad) {
            throw new RuntimeException('Hay órdenes vinculadas que no corresponden a una cantidad de servicio vendida.');
        }

        $this->db->query("ALTER TABLE ordenes_servicio
            MODIFY cantidad_servicio DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            MODIFY unidad_servicio_snapshot VARCHAR(10) NOT NULL DEFAULT 'SERV',
            MODIFY estado ENUM('PENDIENTE','PROGRAMADO','EN_PROCESO','FINALIZADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE'");
        $this->db->query('ALTER TABLE ordenes_servicio ADD CONSTRAINT fk_orden_servicio_unidad FOREIGN KEY (idunidad_servicio) REFERENCES unidadesmedida(idunidad) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->db->query('CREATE INDEX ix_ordenes_servicio_venta_servicio_estado ON ordenes_servicio(idventa, idservicio, estado)');

        $this->db->query("UPDATE ordenes_servicio os SET
            created_at = COALESCE(created_at, (SELECT MIN(h.fecha) FROM historial_orden_servicio h WHERE h.idorden = os.idorden), fecha_inicio, NOW()),
            updated_at = COALESCE(updated_at, (SELECT MAX(h.fecha) FROM historial_orden_servicio h WHERE h.idorden = os.idorden), created_at, NOW())");
        $this->db->query('ALTER TABLE ordenes_servicio MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->db->query('ALTER TABLE consumos_servicio ADD CONSTRAINT fk_consumos_servicio_movimiento FOREIGN KEY (idmovimiento) REFERENCES movimientos(idmovimiento) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->forge->addColumn('movimientos', [
            'idmovimiento_origen' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'idmovimiento'],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_movimiento_origen ON movimientos(idmovimiento_origen)');
        $this->db->query('ALTER TABLE movimientos ADD CONSTRAINT fk_movimiento_origen FOREIGN KEY (idmovimiento_origen) REFERENCES movimientos(idmovimiento) ON DELETE RESTRICT ON UPDATE RESTRICT');

        $this->forge->addField([
            'idanulacionconsumo' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idconsumo' => ['type' => 'INT', 'unsigned' => true],
            'idmovimiento' => ['type' => 'INT', 'unsigned' => true],
            'cantidad' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 255],
            'token_operacion' => ['type' => 'CHAR', 'constraint' => 32],
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'fecha' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idanulacionconsumo', true);
        $this->forge->addUniqueKey('idconsumo');
        $this->forge->addUniqueKey('idmovimiento');
        $this->forge->addUniqueKey('token_operacion');
        $this->forge->addKey(['idusuario', 'fecha']);
        $this->forge->addForeignKey('idconsumo', 'consumos_servicio', 'idconsumo', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idmovimiento', 'movimientos', 'idmovimiento', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('anulaciones_consumo_servicio');

        $this->forge->addField([
            'idpermiso' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clave' => ['type' => 'VARCHAR', 'constraint' => 60],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('idpermiso', true);
        $this->forge->addUniqueKey('clave');
        $this->forge->createTable('permisos');

        $this->forge->addField([
            'idrol' => ['type' => 'INT', 'unsigned' => true],
            'idpermiso' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['idrol', 'idpermiso'], true);
        $this->forge->addForeignKey('idrol', 'roles', 'idrol', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idpermiso', 'permisos', 'idpermiso', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('rol_permiso');

        $this->forge->addField([
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'idlocal' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['idusuario', 'idlocal'], true);
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('usuario_local');

        $permisos = [
            'servicios.ver' => 'Consultar órdenes y agenda de servicios',
            'servicios.gestionar' => 'Crear, programar y actualizar órdenes',
            'inventario.consumir' => 'Registrar materiales usados en servicios',
            'inventario.anular_consumo' => 'Anular consumos de servicios',
        ];
        foreach ($permisos as $clave => $descripcion) {
            $this->db->table('permisos')->insert(compact('clave', 'descripcion'));
        }
        $this->db->query("INSERT INTO rol_permiso (idrol, idpermiso)
            SELECT r.idrol, p.idpermiso FROM roles r CROSS JOIN permisos p
            WHERE r.rol = 'Administrador' OR (r.rol = 'Operador' AND p.clave <> 'inventario.anular_consumo')");
        $this->db->query('INSERT INTO usuario_local (idusuario, idlocal) SELECT u.idusuario, l.idlocal FROM usuarios u CROSS JOIN locales l');
    }

    public function down()
    {
        if ($this->db->table('anulaciones_consumo_servicio')->countAllResults()) {
            throw new RuntimeException('Existen anulaciones de consumo; no se puede retirar la estructura de auditoría.');
        }
        $this->forge->dropTable('usuario_local', true);
        $this->forge->dropTable('rol_permiso', true);
        $this->forge->dropTable('permisos', true);
        $this->forge->dropTable('anulaciones_consumo_servicio', true);
        $this->db->query('ALTER TABLE consumos_servicio DROP FOREIGN KEY fk_consumos_servicio_movimiento');
        $this->db->query('ALTER TABLE movimientos DROP FOREIGN KEY fk_movimiento_origen');
        $this->db->query('DROP INDEX uq_movimiento_origen ON movimientos');
        $this->forge->dropColumn('movimientos', 'idmovimiento_origen');
        $this->db->query('ALTER TABLE ordenes_servicio DROP FOREIGN KEY fk_orden_servicio_unidad');
        $this->db->query('DROP INDEX ix_ordenes_servicio_venta_servicio_estado ON ordenes_servicio');
        $this->forge->dropColumn('ordenes_servicio', ['cantidad_servicio', 'idunidad_servicio', 'unidad_servicio_snapshot']);
        $this->db->query("ALTER TABLE ordenes_servicio MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE', MODIFY created_at DATETIME NULL, MODIFY updated_at DATETIME NULL");
    }
}
