<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementGarantias extends Migration
{
    public function up()
    {
        // No reconstruir garantías históricas a partir del catálogo actual.
        foreach (['detventa', 'venta_combo_componentes'] as $tabla) {
            $campos = [
                'nombre_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'tipo_snapshot' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'controlaserie_snapshot' => ['type' => 'TINYINT', 'null' => true],
            ];
            if ($tabla === 'venta_combo_componentes') {
                $campos['garantiameses'] = ['type' => 'SMALLINT', 'null' => true];
            }
            $this->forge->addColumn($tabla, $campos);
        }
        $this->forge->addColumn('garantias', [
            'idproducto' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'idcomponenteventa' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'producto_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'serie_snapshot' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'garantiameses' => ['type' => 'SMALLINT', 'null' => true],
            'fechavencimiento' => ['type' => 'DATE', 'null' => true],
            'plazo_verificado' => ['type' => 'TINYINT', 'default' => 0],
            'request_token' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_garantia_request ON garantias(request_token)');
        $this->db->query('CREATE INDEX ix_garantia_articulo_estado ON garantias(iddetventa, idproducto, estado)');
        $this->db->query('ALTER TABLE garantias ADD CONSTRAINT fk_garantia_producto FOREIGN KEY (idproducto) REFERENCES productos(idproducto) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->db->query('ALTER TABLE garantias ADD CONSTRAINT fk_garantia_componente FOREIGN KEY (idcomponenteventa) REFERENCES venta_combo_componentes(idcomponenteventa) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->forge->addColumn('garantia_historial', [
            'idproveedor' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->db->query('ALTER TABLE garantia_historial ADD CONSTRAINT fk_garantia_hist_proveedor FOREIGN KEY (idproveedor) REFERENCES proveedores(idproveedor) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down()
    {
        if ($this->db->table('garantias')->where('request_token IS NOT NULL', null, false)->countAllResults()
            || $this->db->table('detventa')->where('tipo_snapshot IS NOT NULL', null, false)->countAllResults()) {
            throw new \RuntimeException('Existen ventas o casos con garantías registradas; no se retirará su trazabilidad.');
        }
        $this->db->query('ALTER TABLE garantia_historial DROP FOREIGN KEY fk_garantia_hist_proveedor');
        $this->forge->dropColumn('garantia_historial', 'idproveedor');
        $this->db->query('ALTER TABLE garantias DROP FOREIGN KEY fk_garantia_componente');
        $this->db->query('ALTER TABLE garantias DROP FOREIGN KEY fk_garantia_producto');
        $this->db->query('DROP INDEX uq_garantia_request ON garantias');
        $this->db->query('DROP INDEX ix_garantia_articulo_estado ON garantias');
        $this->forge->dropColumn('garantias', ['idproducto', 'idcomponenteventa', 'producto_snapshot', 'serie_snapshot', 'garantiameses', 'fechavencimiento', 'plazo_verificado', 'request_token']);
        $this->forge->dropColumn('venta_combo_componentes', ['nombre_snapshot', 'tipo_snapshot', 'controlaserie_snapshot', 'garantiameses']);
        $this->forge->dropColumn('detventa', ['nombre_snapshot', 'tipo_snapshot', 'controlaserie_snapshot']);
    }
}
