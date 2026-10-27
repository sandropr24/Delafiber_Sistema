<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexes extends Migration
{
    public function up()
    {
        $this->db->query("CREATE INDEX ix_ventas_fecha ON ventas(fecha)");
        $this->db->query("CREATE INDEX ix_pagoventa_fecha ON pagoventa(fecha)");
        $this->db->query("CREATE INDEX ix_cuotas_venc ON cuotas(fechavencimiento, estado)");
        $this->db->query("CREATE INDEX ix_entregas_estado ON entregas(idtecnico, estado)");
        $this->db->query("CREATE INDEX ix_movimientos_kdx ON movimientos(idkardex, fecha)");
    }

    public function down()
    {
        $this->db->query("DROP INDEX ix_ventas_fecha ON ventas");
        $this->db->query("DROP INDEX ix_pagoventa_fecha ON pagoventa");
        $this->db->query("DROP INDEX ix_cuotas_venc ON cuotas");
        $this->db->query("DROP INDEX ix_entregas_estado ON entregas");
        $this->db->query("DROP INDEX ix_movimientos_kdx ON movimientos");
    }
}
