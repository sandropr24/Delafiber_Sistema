<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReservadoToSeries extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE series MODIFY estado ENUM('EN_STOCK','RESERVADO','VENDIDO','EN_GARANTIA','BAJA') NOT NULL DEFAULT 'EN_STOCK'");
    }

    public function down()
    {
        $reservadas = $this->db->table('series')->where('estado', 'RESERVADO')->countAllResults();
        if ($reservadas > 0) {
            throw new \Exception("No se puede revertir la migración porque hay series con estado 'RESERVADO'.");
        }

        $this->db->query("ALTER TABLE series MODIFY estado ENUM('EN_STOCK','VENDIDO','EN_GARANTIA','BAJA') NOT NULL DEFAULT 'EN_STOCK'");
    }
}
