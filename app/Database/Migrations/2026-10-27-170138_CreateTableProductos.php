<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProductos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idproducto' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idcategoria' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo'        => [
                'type'       => 'ENUM',
                'constraint' => ['PRODUCTO', 'SERVICIO', 'COMBO'],
                'default'    => 'PRODUCTO',
            ],
            'nombre'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'descripcion'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'idmarca'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'modelo'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'codigobarras'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'unique' => true],
            'idunidad'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'controlastock' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'controlaserie' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'garantiameses' => ['type' => 'SMALLINT', 'constraint' => 6, 'default' => 0],
            'preciocosto'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'monedacosto'   => ['type' => 'ENUM', 'constraint' => ['PEN', 'USD'], 'default' => 'USD'],
            'precioventa'   => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'estado'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'imagen'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('idproducto', true);
        $this->forge->addForeignKey('idcategoria', 'categorias', 'idcategoria', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idmarca', 'marcas', 'idmarca', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('idunidad', 'unidadesmedida', 'idunidad', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('productos');

        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_costo CHECK (preciocosto >= 0)");
        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_venta CHECK (precioventa >= 0)");
        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_serie CHECK (tipo = 'PRODUCTO' OR controlaserie = 0)");
        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_stock_serv CHECK (tipo <> 'SERVICIO' OR controlastock = 0)");
        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_stock_combo CHECK (tipo <> 'COMBO' OR controlastock = 0)");
        $this->db->query("ALTER TABLE productos ADD CONSTRAINT chk_prod_marca CHECK (tipo = 'SERVICIO' OR idmarca IS NOT NULL)");
    }

    public function down()
    {
        $this->forge->dropTable('productos', true);
    }
}
