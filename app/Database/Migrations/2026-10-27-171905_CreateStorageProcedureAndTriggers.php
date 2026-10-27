<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStorageProcedureAndTriggers extends Migration
{
    public function up()
    {
        // Procedimiento almacenado para correlativos
        $this->db->query("
            CREATE PROCEDURE sp_siguiente_correlativo(
              IN  p_tipo   VARCHAR(20),
              IN  p_prefijo VARCHAR(15),
              IN  p_anio   SMALLINT,
              OUT p_numero INT
            )
            BEGIN
              UPDATE correlativos
                 SET ultimonumero = LAST_INSERT_ID(ultimonumero + 1)
               WHERE tipodocumento = p_tipo AND prefijo = p_prefijo AND anio = p_anio;

              IF ROW_COUNT() = 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Correlativo no configurado';
              END IF;

              SET p_numero = LAST_INSERT_ID();
            END
        ");

        // Trigger para historial de precios
        $this->db->query("
            CREATE TRIGGER trg_productos_precio
            AFTER UPDATE ON productos
            FOR EACH ROW
            BEGIN
              IF OLD.precioventa <> NEW.precioventa THEN
                INSERT INTO preciosventa_hist (idproducto, precioanterior, precionuevo)
                VALUES (NEW.idproducto, OLD.precioventa, NEW.precioventa);
              END IF;
            END
        ");
    }

    public function down()
    {
        $this->db->query("DROP PROCEDURE IF EXISTS sp_siguiente_correlativo");
        $this->db->query("DROP TRIGGER IF EXISTS trg_productos_precio");
    }
}
