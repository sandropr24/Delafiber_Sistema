<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViews extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE VIEW vw_ventas_saldo AS
            SELECT v.idventa, v.tipodocumento, v.serie, v.numero, v.fecha, v.idcliente, v.total,
                   COALESCE(p.pagado,0) AS pagado,
                   v.total - COALESCE(p.pagado,0) AS saldo
            FROM ventas v
            JOIN estadoventa ev ON ev.idestado = v.idestado AND ev.estado = 'Emitida'
            LEFT JOIN (SELECT idventa, SUM(monto) AS pagado FROM pagoventa GROUP BY idventa) p
                   ON p.idventa = v.idventa
            WHERE v.formapago <> 'CREDITO_INTERNET'
            HAVING saldo > 0
        ");

        $this->db->query("
            CREATE VIEW vw_cuotas_vencidas AS
            SELECT c.idcuota, c.idventa, v.serie, v.numero, v.idcliente, c.nrocuota,
                   c.fechavencimiento, c.monto - c.montopagado AS pendiente,
                   DATEDIFF(CURDATE(), c.fechavencimiento) AS diasvencida
            FROM cuotas c
            JOIN ventas v ON v.idventa = c.idventa
            WHERE c.estado <> 'PAGADA' AND c.fechavencimiento < CURDATE()
        ");

        $this->db->query("
            CREATE VIEW vw_creditos_internet_pendientes AS
            SELECT ci.idcredito, ci.idventa, v.serie, v.numero, ci.idpersona, ci.codigocliente,
                   pe.numerodoc, ci.monto, ci.nrocuotas, ci.fecharegistro
            FROM creditosinternet ci
            JOIN ventas v    ON v.idventa = ci.idventa
            JOIN personas pe ON pe.idpersona = ci.idpersona
            WHERE ci.estado = 'PENDIENTE'
        ");

        $this->db->query("
            CREATE VIEW vw_entregas_pendientes_tecnico AS
            SELECT e.idtecnico, CONCAT(pe.apellidos,' ',pe.nombres) AS tecnico,
                   e.identrega, e.idventa, v.serie, v.numero, e.direccion, e.referencia,
                   e.telefono, e.estado, e.cobrocontraentrega
            FROM entregas e
            JOIN ventas v    ON v.idventa = e.idventa
            JOIN usuarios u  ON u.idusuario = e.idtecnico
            JOIN personas pe ON pe.idpersona = u.idpersona
            WHERE e.tipoentrega = 'DOMICILIO' AND e.estado <> 'ENTREGADO'
        ");

        $this->db->query("
            CREATE VIEW vw_series_vendidas AS
            SELECT s.idserie, s.numeroserie, pr.nombre AS producto,
                   v.idventa, v.serie, v.numero, v.fecha AS fechaventa,
                   pe.tipodoc, pe.numerodoc, COALESCE(pe.razonsocial, CONCAT(pe.apellidos,' ',pe.nombres)) AS cliente,
                   dv.garantiameses,
                   DATE_ADD(v.fecha, INTERVAL dv.garantiameses MONTH) AS garantiahasta
            FROM series s
            JOIN detventa dv  ON dv.iddetventa = s.iddetventa
            JOIN ventas v     ON v.idventa = dv.idventa
            JOIN personas pe  ON pe.idpersona = v.idcliente
            JOIN productos pr ON pr.idproducto = s.idproducto
        ");

        $this->db->query("
            CREATE VIEW vw_reporte_ventas AS
            SELECT v.idventa, DATE(v.fecha) AS fecha, l.nombre AS linea, c.canal,
                   v.idvendedor, e.idtecnico, dv.idproducto, pr.nombre AS producto,
                   dv.cantidad, dv.total AS importe,
                   ROUND(dv.cantidad * dv.costounitario, 2) AS costo
            FROM ventas v
            JOIN estadoventa ev ON ev.idestado = v.idestado AND ev.estado = 'Emitida'
            JOIN lineas l       ON l.idlinea = v.idlinea
            JOIN canalesventa c ON c.idcanal = v.idcanal
            JOIN detventa dv    ON dv.idventa = v.idventa
            JOIN productos pr   ON pr.idproducto = dv.idproducto
            LEFT JOIN entregas e ON e.idventa = v.idventa
        ");

        $this->db->query("
            CREATE VIEW vw_arqueo_esperado AS
            SELECT a.idapertura, tp.idtipopago, tp.tipopago,
                   COALESCE(SUM(p.monto),0) + IF(tp.tipopago = 'Efectivo', a.montoinicial, 0) AS montoesperado
            FROM aperturacaja a
            CROSS JOIN tipospago tp
            LEFT JOIN pagoventa p ON p.idapertura = a.idapertura AND p.idtipopago = tp.idtipopago
            GROUP BY a.idapertura, tp.idtipopago, tp.tipopago, a.montoinicial
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS vw_arqueo_esperado");
        $this->db->query("DROP VIEW IF EXISTS vw_reporte_ventas");
        $this->db->query("DROP VIEW IF EXISTS vw_series_vendidas");
        $this->db->query("DROP VIEW IF EXISTS vw_entregas_pendientes_tecnico");
        $this->db->query("DROP VIEW IF EXISTS vw_creditos_internet_pendientes");
        $this->db->query("DROP VIEW IF EXISTS vw_cuotas_vencidas");
        $this->db->query("DROP VIEW IF EXISTS vw_ventas_saldo");
    }
}
