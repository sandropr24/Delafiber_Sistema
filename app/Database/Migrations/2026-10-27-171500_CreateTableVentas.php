<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableVentas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idventa'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idcliente'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idlocal'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idlinea'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idcanal'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipodocumento' => [
                'type'       => 'ENUM',
                'constraint' => ['NOTA_VENTA', 'BOLETA', 'FACTURA'],
                'default'    => 'NOTA_VENTA',
            ],
            'serie'            => ['type' => 'VARCHAR', 'constraint' => 10],
            'numero'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'idcajero'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idvendedor'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idestado'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idcotizacion'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'unique' => true],
            'idpedido'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'fecha'            => ['type' => 'DATETIME'],
            'formapago'        => [
                'type'       => 'ENUM',
                'constraint' => ['CONTADO', 'CUOTAS', 'ADELANTO_SALDO', 'CREDITO_INTERNET'],
                'default'    => 'CONTADO',
            ],
            'montoinicial'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'nrocuotas'        => ['type' => 'SMALLINT', 'constraint' => 6, 'null' => true],
            'frecuenciacuotas' => [
                'type'       => 'ENUM',
                'constraint' => ['SEMANAL', 'QUINCENAL', 'MENSUAL'],
                'null'       => true,
            ],
            'subtotal'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'montoigv'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'descuento'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'total'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'fechaanulacion'  => ['type' => 'DATETIME', 'null' => true],
            'idusuarioanula'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'motivoanulacion' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'estafacturado'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'cdr'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('idventa', true);
        $this->forge->addUniqueKey(['tipodocumento', 'serie', 'numero']);
        $this->forge->addForeignKey('idcliente', 'personas', 'idpersona', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlocal', 'locales', 'idlocal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idlinea', 'lineas', 'idlinea', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcanal', 'canalesventa', 'idcanal', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcajero', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idvendedor', 'usuarios', 'idusuario', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idestado', 'estadoventa', 'idestado', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idcotizacion', 'cotizacion', 'idcotizacion', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idpedido', 'pedido', 'idpedido', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('idusuarioanula', 'usuarios', 'idusuario', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('ventas');
    }

    public function down()
    {
        $this->forge->dropTable('ventas', true);
    }
}
