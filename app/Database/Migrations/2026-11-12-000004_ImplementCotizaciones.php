<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementCotizaciones extends Migration
{
    public function up()
    {
        if ($this->db->query('SELECT idcotizacion FROM pedido WHERE idcotizacion IS NOT NULL GROUP BY idcotizacion HAVING COUNT(*) > 1 LIMIT 1')->getRowArray()) throw new \RuntimeException('Revise cotizaciones vinculadas a varios pedidos antes de migrar.');
        $this->forge->addColumn('estadocotizacion', ['codigo' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true]]);
        $this->db->query('CREATE UNIQUE INDEX uq_estadocotizacion_codigo ON estadocotizacion(codigo)');
        foreach (['BORRADOR' => 'Borrador', 'EMITIDA' => 'Emitida', 'ACEPTADA' => 'Aceptada', 'RECHAZADA' => 'Rechazada', 'A_PEDIDO' => 'Convertida a pedido', 'A_VENTA' => 'Convertida a venta'] as $codigo => $nombre) {
            $fila = $this->db->table('estadocotizacion')->where('estado', $nombre)->get()->getRowArray();
            if ($fila) $this->db->table('estadocotizacion')->where('idestado', $fila['idestado'])->update(['codigo' => $codigo]);
            else $this->db->table('estadocotizacion')->insert(['estado' => $nombre, 'codigo' => $codigo]);
        }
        $this->forge->modifyColumn('cotizacion', ['numero' => ['type' => 'VARCHAR', 'constraint' => 60]]);
        $this->forge->addColumn('cotizacion', [
            'moneda_origen' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'PEN'],
            'precios_incluyen_igv' => ['type' => 'TINYINT', 'default' => 1],
            'condiciones' => ['type' => 'TEXT', 'null' => true],
            'cliente_snapshot' => ['type' => 'TEXT', 'null' => true],
            'request_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'revision' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'fecha_emision' => ['type' => 'DATETIME', 'null' => true],
            'fecha_conversion' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_cotizacion_request_token ON cotizacion(request_token)');
        $this->db->query('CREATE INDEX ix_cotizacion_estado_fecha ON cotizacion(idestado, fecha)');
        $this->db->query('CREATE INDEX ix_cotizacion_cliente_fecha ON cotizacion(idpersona, fecha)');
        $this->forge->addColumn('detcotizacion', [
            'precio_origen' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'null' => true],
            'nombre_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'config_snapshot' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->db->query('UPDATE detcotizacion SET precio_origen = preciounitario');
        $this->forge->addField([
            'idserie' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idlinea' => ['type' => 'INT', 'unsigned' => true, 'unique' => true],
            'prefijo' => ['type' => 'VARCHAR', 'constraint' => 15, 'unique' => true],
            'numero_inicial' => ['type' => 'INT', 'unsigned' => true, 'default' => 3571],
        ]);
        $this->forge->addKey('idserie', true);
        $this->forge->addForeignKey('idlinea', 'lineas', 'idlinea', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('cotizacion_series');
        foreach (['CCTV' => 'CCTV', 'REDES' => 'Redes', 'RADIO' => 'Radio', 'COMPUTO' => 'Cómputo'] as $prefijo => $nombre) {
            if (!$this->db->table('lineas')->where('prefijo', $prefijo)->countAllResults()) $this->db->table('lineas')->insert(['prefijo' => $prefijo, 'nombre' => $nombre, 'estado' => 1]);
        }
        foreach ($this->db->table('lineas')->get()->getResultArray() as $linea) {
            $prefijo = strtoupper(trim($linea['prefijo']));
            if (preg_match('/^[A-Z0-9]{1,15}$/D', $prefijo)) $this->db->table('cotizacion_series')->insert(['idlinea' => $linea['idlinea'], 'prefijo' => $prefijo, 'numero_inicial' => 3571]);
        }
        $this->forge->addField([
            'idhistorial' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idcotizacion' => ['type' => 'INT', 'unsigned' => true],
            'idusuario' => ['type' => 'INT', 'unsigned' => true],
            'evento' => ['type' => 'VARCHAR', 'constraint' => 30],
            'observacion' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'datos' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'fecha' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('idhistorial', true);
        $this->forge->addKey(['idcotizacion', 'fecha']);
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('historial_cotizacion');
        $this->db->query('CREATE UNIQUE INDEX uq_pedido_cotizacion ON pedido(idcotizacion)');
        foreach (['pedido', 'ventas'] as $tabla) $this->forge->addColumn($tabla, [
            'porcentajeigv' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tipocambio' => ['type' => 'DECIMAL', 'constraint' => '8,4', 'null' => true],
            'baseimponible' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
        ]);
        $this->forge->addColumn('pedido', ['montoigv' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true]]);
    }

    public function down()
    {
        if ($this->db->table('historial_cotizacion')->countAllResults() || $this->db->table('cotizacion')->where('request_token IS NOT NULL', null, false)->countAllResults()) throw new \RuntimeException('Existen cotizaciones o historial comercial; no se retirará la estructura.');
        $this->db->query('DROP INDEX uq_pedido_cotizacion ON pedido');
        $this->forge->dropTable('historial_cotizacion');
        $this->forge->dropTable('cotizacion_series');
        foreach (['uq_cotizacion_request_token', 'ix_cotizacion_estado_fecha', 'ix_cotizacion_cliente_fecha'] as $indice) $this->db->query('DROP INDEX ' . $indice . ' ON cotizacion');
        $this->forge->dropColumn('detcotizacion', ['precio_origen', 'nombre_snapshot', 'descripcion', 'config_snapshot']);
        $this->forge->dropColumn('cotizacion', ['moneda_origen', 'precios_incluyen_igv', 'condiciones', 'cliente_snapshot', 'request_token', 'revision', 'updated_at', 'fecha_emision', 'fecha_conversion']);
        $this->forge->dropColumn('estadocotizacion', 'codigo');
        foreach (['pedido', 'ventas'] as $tabla) $this->forge->dropColumn($tabla, ['porcentajeigv', 'tipocambio', 'baseimponible']);
        $this->forge->dropColumn('pedido', 'montoigv');
    }
}
