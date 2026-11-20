<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

class ReporteCajaService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    private function periodo(string $granularidad, string $campo): string
    {
        if ($granularidad === 'MES') {
            return $this->db->DBDriver === 'SQLite3' ? "strftime('%Y-%m', {$campo})" : "DATE_FORMAT({$campo}, '%Y-%m')";
        }
        return $this->db->DBDriver === 'SQLite3' ? "date({$campo})" : "DATE({$campo})";
    }

    public function filtros(array $entrada): array
    {
        $hoy = date('Y-m-d');
        $primero = date('Y-m-01');
        $filtros = [
            'granularidad' => strtoupper(trim((string) ($entrada['granularidad'] ?? 'DIA'))),
            'desde' => trim((string) ($entrada['desde'] ?? $primero)),
            'hasta' => trim((string) ($entrada['hasta'] ?? $hoy)),
            'idlinea' => ctype_digit((string) ($entrada['idlinea'] ?? '')) ? (int) $entrada['idlinea'] : 0,
            'idvendedor' => ctype_digit((string) ($entrada['idvendedor'] ?? '')) ? (int) $entrada['idvendedor'] : 0,
            'idtecnico' => ctype_digit((string) ($entrada['idtecnico'] ?? '')) ? (int) $entrada['idtecnico'] : 0,
            'idproducto' => ctype_digit((string) ($entrada['idproducto'] ?? '')) ? (int) $entrada['idproducto'] : 0,
        ];
        if (!in_array($filtros['granularidad'], ['DIA', 'MES'], true)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $filtros['desde'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $filtros['hasta'])
            || $filtros['desde'] > $filtros['hasta']) {
            throw new InvalidArgumentException('Selecciona un período válido.');
        }
        $filtros['hasta_exclusivo'] = date('Y-m-d', strtotime($filtros['hasta'] . ' +1 day'));
        return $filtros;
    }

    public function catalogos(int $usuario): array
    {
        $actual = (new CajaService($this->db))->usuario($usuario);
        $vendedores = $this->db->table('usuarios u')->select('u.idusuario, u.nombreusuario')
            ->join('roles r', 'r.idrol = u.idrol')->where('u.estado', 1)->whereIn('r.rol', ['Administrador', 'Vendedor']);
        if ($actual['rol'] !== 'Administrador') $vendedores->where('u.idusuario', $usuario);
        return [
            'lineas' => $this->db->table('lineas')->where('estado', 1)->orderBy('nombre')->get()->getResultArray(),
            'vendedores' => $vendedores->orderBy('u.nombreusuario')->get()->getResultArray(),
            'tecnicos' => $this->db->table('tecnicos')->where('estado', 'ACTIVO')->orderBy('apellidos, nombres')->get()->getResultArray(),
            'productos' => $this->db->table('productos')->where('estado', 1)->orderBy('nombre')->get()->getResultArray(),
            'esAdmin' => $actual['rol'] === 'Administrador',
        ];
    }

    public function consultar(array $entrada, int $usuario): array
    {
        $f = $this->filtros($entrada);
        $actual = (new CajaService($this->db))->usuario($usuario);
        if ($actual['rol'] !== 'Administrador') $f['idvendedor'] = $usuario;

        $where = ['pv.fecha >= ?', 'pv.fecha < ?'];
        $params = [$f['desde'] . ' 00:00:00', $f['hasta_exclusivo'] . ' 00:00:00'];
        foreach (['idlinea' => 'v.idlinea', 'idvendedor' => 'v.idvendedor'] as $filtro => $campo) {
            if ($f[$filtro]) { $where[] = $campo . ' = ?'; $params[] = $f[$filtro]; }
        }
        if ($f['idtecnico']) { $where[] = 'COALESCE(pv.idtecnicocobro, e.idtecnico) = ?'; $params[] = $f['idtecnico']; }
        if ($f['idproducto']) { $where[] = 'EXISTS (SELECT 1 FROM detventa dx WHERE dx.idventa = v.idventa AND dx.idproducto = ?)'; $params[] = $f['idproducto']; }
        $periodoCobro = $this->periodo($f['granularidad'], 'pv.fecha');
        $cobros = $this->db->query("SELECT {$periodoCobro} AS periodo, l.nombre AS linea,
                uv.nombreusuario AS vendedor, COALESCE(CONCAT(t.nombres, ' ', t.apellidos), 'Sin técnico') AS tecnico,
                tp.tipopago, COUNT(*) AS operaciones, SUM(pv.monto) AS monto,
                SUM(CASE WHEN v.fechaanulacion IS NULL THEN pv.monto ELSE 0 END) AS monto_activo,
                SUM(CASE WHEN v.fechaanulacion IS NOT NULL THEN pv.monto ELSE 0 END) AS monto_anulado
            FROM pagoventa pv
            JOIN ventas v ON v.idventa = pv.idventa
            JOIN lineas l ON l.idlinea = v.idlinea
            JOIN usuarios uv ON uv.idusuario = v.idvendedor
            JOIN tipospago tp ON tp.idtipopago = pv.idtipopago
            LEFT JOIN entregas e ON e.idventa = v.idventa
            LEFT JOIN tecnicos t ON t.idtecnico = COALESCE(pv.idtecnicocobro, e.idtecnico)
            WHERE " . implode(' AND ', $where) . "
            GROUP BY {$periodoCobro}, l.idlinea, l.nombre, uv.idusuario, uv.nombreusuario, t.idtecnico, t.nombres, t.apellidos, tp.idtipopago, tp.tipopago
            ORDER BY periodo DESC, l.nombre, uv.nombreusuario, tecnico, tp.tipopago", $params)->getResultArray();

        $whereVentas = ['v.fecha >= ?', 'v.fecha < ?', 'v.fechaanulacion IS NULL'];
        $paramsVentas = [$f['desde'] . ' 00:00:00', $f['hasta_exclusivo'] . ' 00:00:00'];
        foreach (['idlinea' => 'v.idlinea', 'idvendedor' => 'v.idvendedor', 'idproducto' => 'd.idproducto'] as $filtro => $campo) {
            if ($f[$filtro]) { $whereVentas[] = $campo . ' = ?'; $paramsVentas[] = $f[$filtro]; }
        }
        if ($f['idtecnico']) { $whereVentas[] = 'e.idtecnico = ?'; $paramsVentas[] = $f['idtecnico']; }
        $periodoVenta = $this->periodo($f['granularidad'], 'v.fecha');
        $productos = $this->db->query("SELECT {$periodoVenta} AS periodo, l.nombre AS linea,
                uv.nombreusuario AS vendedor, COALESCE(CONCAT(t.nombres, ' ', t.apellidos), 'Sin técnico') AS tecnico,
                p.nombre AS producto, SUM(d.cantidad) AS cantidad,
                SUM(ROUND(d.cantidad * d.precioventa, 2)) AS importe_bruto
            FROM detventa d
            JOIN ventas v ON v.idventa = d.idventa
            JOIN productos p ON p.idproducto = d.idproducto
            JOIN lineas l ON l.idlinea = v.idlinea
            JOIN usuarios uv ON uv.idusuario = v.idvendedor
            LEFT JOIN entregas e ON e.idventa = v.idventa
            LEFT JOIN tecnicos t ON t.idtecnico = e.idtecnico
            WHERE " . implode(' AND ', $whereVentas) . "
            GROUP BY {$periodoVenta}, l.idlinea, l.nombre, uv.idusuario, uv.nombreusuario, t.idtecnico, t.nombres, t.apellidos, p.idproducto, p.nombre
            ORDER BY periodo DESC, importe_bruto DESC", $paramsVentas)->getResultArray();

        $periodoMovimiento = $this->periodo($f['granularidad'], 'm.fecha');
        $whereMovimientos = ['m.fecha >= ?', 'm.fecha < ?'];
        $paramsMovimientos = [$f['desde'] . ' 00:00:00', $f['hasta_exclusivo'] . ' 00:00:00'];
        if ($actual['rol'] !== 'Administrador') { $whereMovimientos[] = 'a.idusuario = ?'; $paramsMovimientos[] = $usuario; }
        $movimientos = $this->db->query("SELECT {$periodoMovimiento} AS periodo, lo.nombrelocal, c.nombre AS caja,
                u.nombreusuario, tp.tipopago, m.tipo, m.concepto, COUNT(*) AS operaciones, SUM(m.monto) AS monto
            FROM movimientos_caja m
            JOIN aperturacaja a ON a.idapertura = m.idapertura
            JOIN cajas c ON c.idcaja = a.idcaja
            JOIN locales lo ON lo.idlocal = c.idlocal
            JOIN usuarios u ON u.idusuario = m.idusuario
            JOIN tipospago tp ON tp.idtipopago = m.idtipopago
            WHERE " . implode(' AND ', $whereMovimientos) . "
            GROUP BY {$periodoMovimiento}, lo.idlocal, lo.nombrelocal, c.idcaja, c.nombre, u.idusuario, u.nombreusuario,
                tp.idtipopago, tp.tipopago, m.tipo, m.concepto
            ORDER BY periodo DESC, lo.nombrelocal, c.nombre, m.tipo, m.concepto", $paramsMovimientos)->getResultArray();

        return ['filtros' => $f, 'cobros' => $cobros, 'productosReporte' => $productos, 'movimientosReporte' => $movimientos];
    }
}
