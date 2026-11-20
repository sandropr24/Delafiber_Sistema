<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServicioAgendaIndex extends Migration
{
    public function up()
    {
        $this->db->query('CREATE INDEX ix_ordenes_servicio_estado_inicio ON ordenes_servicio(estado, fecha_inicio)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX ix_ordenes_servicio_estado_inicio ON ordenes_servicio');
    }
}
