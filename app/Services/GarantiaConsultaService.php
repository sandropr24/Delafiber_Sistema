<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class GarantiaConsultaService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    private function cliente(): string
    {
        return $this->db->DBDriver === 'SQLite3'
            ? "COALESCE(NULLIF(pe.razonsocial, ''), TRIM(COALESCE(pe.nombres, '') || ' ' || COALESCE(pe.apellidos, '')))"
            : "COALESCE(NULLIF(pe.razonsocial, ''), TRIM(CONCAT(COALESCE(pe.nombres, ''), ' ', COALESCE(pe.apellidos, ''))))";
    }

    private function comprobante(): string
    {
        return $this->db->DBDriver === 'SQLite3' ? "v.serie || '-' || v.numero" : "CONCAT(v.serie, '-', v.numero)";
    }

    private function articulosSql(): string
    {
        // El plazo de un combo antiguo es desconocido, nunca el del catálogo vigente.
        return "SELECT d.iddetventa, d.idventa, d.idproducto, d.cantidad,
                COALESCE(d.nombre_snapshot, p.nombre) AS producto,
                COALESCE(d.tipo_snapshot, p.tipo) AS tipo,
                COALESCE(d.controlaserie_snapshot, p.controlaserie) AS controlaserie,
                CASE WHEN d.tipo_snapshot IS NULL AND d.garantiameses = 0 THEN NULL ELSE d.garantiameses END AS garantiameses,
                NULL AS idcomponenteventa
            FROM detventa d JOIN productos p ON p.idproducto = d.idproducto
            WHERE NOT EXISTS (SELECT 1 FROM venta_combo_componentes vc WHERE vc.iddetventa = d.iddetventa)
            UNION ALL
            SELECT d.iddetventa, d.idventa, vc.idproducto, vc.cantidad,
                COALESCE(vc.nombre_snapshot, p.nombre), COALESCE(vc.tipo_snapshot, p.tipo),
                COALESCE(vc.controlaserie_snapshot, p.controlaserie), vc.garantiameses, vc.idcomponenteventa
            FROM venta_combo_componentes vc
            JOIN detventa d ON d.iddetventa = vc.iddetventa
            JOIN productos p ON p.idproducto = vc.idproducto";
    }

    public function articulo(int $detalle, ?int $componente): ?array
    {
        $sql = 'SELECT i.* FROM (' . $this->articulosSql() . ') i WHERE i.iddetventa = ? AND i.idcomponenteventa ';
        $params = [$detalle];
        if ($componente) { $sql .= '= ?'; $params[] = $componente; }
        else $sql .= 'IS NULL';
        return $this->db->query($sql, $params)->getRowArray();
    }

    public function buscarVentas(string $buscar, int $venta = 0): array
    {
        if ($buscar === '' && $venta < 1) return [];
        // La subconsulta UNION ALL contiene un CASE: Query Builder no debe
        // volver a escapar sus identificadores ni alterar esa expresion.
        // Los valores externos se pasan exclusivamente como parametros.
        $sql = 'SELECT i.*, v.serie, v.numero, v.fecha, v.fechaanulacion, pe.numerodoc, '
            . $this->cliente() . " AS cliente,
                s.idserie, s.numeroserie, s.estado AS estadoserie,
                (SELECT COUNT(*) FROM entregas e WHERE e.idventa = v.idventa AND e.estado = 'ENTREGADO') AS entregada
            FROM (" . $this->articulosSql() . ") i
            JOIN ventas v ON v.idventa = i.idventa
            JOIN personas pe ON pe.idpersona = v.idcliente
            LEFT JOIN series s ON s.iddetventa = i.iddetventa AND s.idproducto = i.idproducto
            WHERE i.tipo = ?";
        $params = ['PRODUCTO'];
        if ($venta > 0) {
            $sql .= ' AND v.idventa = ?';
            $params[] = $venta;
        }
        if ($buscar !== '') {
            // escapeLikeString protege los comodines introducidos por el usuario.
            $patron = '%' . $this->db->escapeLikeString($buscar) . '%';
            $sql .= " AND (s.numeroserie LIKE ? ESCAPE '!'
                OR pe.numerodoc LIKE ? ESCAPE '!'
                OR v.numero LIKE ? ESCAPE '!'
                OR " . $this->comprobante() . " LIKE ? ESCAPE '!')";
            array_push($params, $patron, $patron, $patron, $patron);
        }
        $sql .= ' ORDER BY v.fecha DESC, i.iddetventa ASC, s.idserie ASC LIMIT 100';
        $filas = $this->db->query($sql, $params)->getResultArray();
        foreach ($filas as &$fila) {
            $fila['vencimiento'] = null;
            $fila['aviso'] = '';
            if ($fila['garantiameses'] !== null && (int) $fila['garantiameses'] > 0) {
                $fila['vencimiento'] = (new GarantiaWorkflow())->vencimiento($fila['fecha'], (int) $fila['garantiameses']);
            }
            if ($fila['fechaanulacion'] !== null) $fila['aviso'] = 'Venta anulada';
            elseif (!(int) $fila['entregada']) $fila['aviso'] = 'Entrega aún no confirmada';
            elseif ($fila['idserie'] && $fila['estadoserie'] !== 'VENDIDO') $fila['aviso'] = 'Serie no disponible para recepción';
            elseif (!$fila['idserie'] && (int) $fila['controlaserie']) $fila['aviso'] = 'Falta la serie vendida';
            elseif ($fila['garantiameses'] === null) $fila['aviso'] = 'Plazo histórico: requiere verificación de administrador';
            elseif ((int) $fila['garantiameses'] === 0) $fila['aviso'] = 'Sin garantía';
            elseif ($fila['vencimiento'] < date('Y-m-d')) $fila['aviso'] = 'Garantía vencida';
        }
        unset($fila);
        return $filas;
    }

    private function casos()
    {
        return $this->db->table('garantias g')
            ->select('g.*, v.idventa, v.serie, v.numero, v.fecha AS fechaventa, pe.numerodoc, ' . $this->cliente() . ' AS cliente,
                COALESCE(g.producto_snapshot, p.nombre) AS producto, COALESCE(g.serie_snapshot, s.numeroserie) AS numeroserie,
                pr.razonsocial AS proveedor, u.nombreusuario AS responsable', false)
            ->join('detventa d', 'd.iddetventa = g.iddetventa')->join('ventas v', 'v.idventa = d.idventa')
            ->join('personas pe', 'pe.idpersona = v.idcliente')->join('productos p', 'p.idproducto = d.idproducto')
            ->join('series s', 's.idserie = g.idserie', 'left')->join('proveedores pr', 'pr.idproveedor = g.idproveedor', 'left')
            ->join('usuarios u', 'u.idusuario = g.idusuario');
    }

    public function listar(string $buscar, string $estado, int $pagina): array
    {
        $b = $this->casos();
        if ($buscar !== '') {
            $b->groupStart()->like('pe.numerodoc', $buscar)->orLike('v.numero', $buscar)
                ->orLike($this->comprobante(), $buscar)->orLike('g.producto_snapshot', $buscar)
                ->orLike('g.serie_snapshot', $buscar)->orLike('s.numeroserie', $buscar)->groupEnd();
        }
        if (isset(GarantiaWorkflow::ETIQUETAS[$estado])) $b->where('g.estado', $estado);
        $total = (clone $b)->countAllResults();
        $paginas = max(1, (int) ceil($total / 25));
        $pagina = min(max(1, $pagina), $paginas);
        return [
            'items' => $b->orderBy('g.idgarantia', 'DESC')->limit(25, ($pagina - 1) * 25)->get()->getResultArray(),
            'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas,
        ];
    }

    public function obtener(int $id): ?array
    {
        return $this->casos()->where('g.idgarantia', $id)->get()->getRowArray();
    }

    public function historial(int $id): array
    {
        return $this->db->table('garantia_historial h')->select('h.*, u.nombreusuario, p.razonsocial AS proveedor')
            ->join('usuarios u', 'u.idusuario = h.idusuario')->join('proveedores p', 'p.idproveedor = h.idproveedor', 'left')
            ->where('h.idgarantia', $id)->orderBy('h.idhistorial', 'ASC')->get()->getResultArray();
    }
}
