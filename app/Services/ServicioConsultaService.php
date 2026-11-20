<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

final class ServicioConsultaService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function listar(array $filtros = [], int $pagina = 1, int $porPagina = 25): array
    {
        $consulta = $this->consultaOrdenes();
        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
        if ($buscar !== '') {
            $consulta->groupStart()
                ->like('os.titulo', $buscar)
                ->orLike('p.nombres', $buscar)
                ->orLike('p.apellidos', $buscar)
                ->orLike('p.razonsocial', $buscar)
                ->orLike('pr.nombre', $buscar)
                ->orLike('t.nombres', $buscar)
                ->orLike('t.apellidos', $buscar);
            if (preg_match('/^(?:OS-)?0*(\d+)$/i', $buscar, $coincidencia)) {
                $consulta->orWhere('os.idorden', (int) $coincidencia[1]);
            }
            $consulta->groupEnd();
        }
        if (in_array($estado, ServicioWorkflow::ESTADOS, true)) {
            $consulta->where('os.estado', $estado);
        }
        $consulta->orderBy('os.idorden', 'DESC');
        $total = (clone $consulta)->countAllResults();
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);

        return [
            'items' => $consulta->limit($porPagina, ($pagina - 1) * $porPagina)->get()->getResultArray(),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public function agenda(array $filtros, int $limite = 500): array
    {
        $consulta = $this->consultaOrdenes()
            ->whereIn('os.estado', [ServicioWorkflow::PROGRAMADO, ServicioWorkflow::EN_PROCESO])
            ->where('os.fecha_inicio >=', $filtros['desde'] . ' 00:00:00')
            ->where('os.fecha_inicio <=', $filtros['hasta'] . ' 23:59:59');
        if (in_array($filtros['estado'] ?? '', [ServicioWorkflow::PROGRAMADO, ServicioWorkflow::EN_PROCESO], true)) {
            $consulta->where('os.estado', $filtros['estado']);
        }
        if ((int) ($filtros['tecnico'] ?? 0) > 0) {
            $consulta->where('os.idtecnico', (int) $filtros['tecnico']);
        }
        $items = $consulta->orderBy('os.fecha_inicio', 'ASC')->limit($limite + 1)->get()->getResultArray();
        $truncada = count($items) > $limite;
        if ($truncada) {
            array_pop($items);
        }
        return ['items' => $items, 'truncada' => $truncada];
    }

    public function resumenPorEstado(): array
    {
        $resumen = array_fill_keys(ServicioWorkflow::ESTADOS, 0);
        foreach ($this->db->table('ordenes_servicio')->select('estado, COUNT(*) AS cantidad')->groupBy('estado')->get()->getResultArray() as $fila) {
            if (array_key_exists($fila['estado'], $resumen)) {
                $resumen[$fila['estado']] = (int) $fila['cantidad'];
            }
        }
        return $resumen;
    }

    public function serviciosDeVenta(int $idVenta): array
    {
        $servicios = [];
        $directos = $this->db->table('detventa d')
            ->select('p.idproducto, p.nombre, p.idunidad, um.abreviatura, SUM(d.cantidad) AS cantidad', false)
            ->join('productos p', 'p.idproducto = d.idproducto')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad')
            ->where('d.idventa', $idVenta)->where('p.tipo', 'SERVICIO')
            ->groupBy(['p.idproducto', 'p.nombre', 'p.idunidad', 'um.abreviatura'])->get()->getResultArray();
        foreach ($directos as $servicio) {
            $servicios[(int) $servicio['idproducto']] = $servicio;
        }

        if ($this->db->tableExists('venta_combo_componentes')) {
            $combos = $this->db->table('venta_combo_componentes c')
                ->select('p.idproducto, p.nombre, p.idunidad, um.abreviatura, SUM(c.cantidad) AS cantidad', false)
                ->join('detventa d', 'd.iddetventa = c.iddetventa')
                ->join('productos p', 'p.idproducto = c.idproducto')
                ->join('unidadesmedida um', 'um.idunidad = p.idunidad')
                ->where('d.idventa', $idVenta)->where('p.tipo', 'SERVICIO')
                ->groupBy(['p.idproducto', 'p.nombre', 'p.idunidad', 'um.abreviatura'])->get()->getResultArray();
            foreach ($combos as $servicio) {
                $id = (int) $servicio['idproducto'];
                if (isset($servicios[$id])) {
                    $servicios[$id]['cantidad'] = $this->cantidadDecimal($this->cantidadCentimos((string) $servicios[$id]['cantidad']) + $this->cantidadCentimos((string) $servicio['cantidad']));
                } else {
                    $servicios[$id] = $servicio;
                }
            }
        }

        $programadas = $this->db->table('ordenes_servicio')->select('idservicio, SUM(cantidad_servicio) AS cantidad', false)
            ->where('idventa', $idVenta)->where('estado !=', ServicioWorkflow::CANCELADO)
            ->groupBy('idservicio')->get()->getResultArray();
        $porServicio = array_column($programadas, 'cantidad', 'idservicio');
        foreach ($servicios as $id => &$servicio) {
            $programado = $this->cantidadCentimos((string) ($porServicio[$id] ?? '0.00'), true);
            $vendido = $this->cantidadCentimos((string) $servicio['cantidad']);
            $servicio['programado'] = $this->cantidadDecimal($programado);
            $servicio['pendiente'] = $this->cantidadDecimal(max(0, $vendido - $programado));
        }
        unset($servicio);
        return array_values($servicios);
    }

    public function buscarVentas(string $buscar, int $limite = 20): array
    {
        $origenes = ["SELECT d.idventa, d.idproducto, d.cantidad FROM detventa d JOIN productos p ON p.idproducto = d.idproducto WHERE p.tipo = 'SERVICIO'"];
        if ($this->db->tableExists('venta_combo_componentes')) {
            $origenes[] = "SELECT d.idventa, c.idproducto, c.cantidad FROM detventa d JOIN venta_combo_componentes c ON c.iddetventa = d.iddetventa JOIN productos p ON p.idproducto = c.idproducto WHERE p.tipo = 'SERVICIO'";
        }
        $parametros = [];
        $condicion = '';
        $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        $comprobante = $this->db->DBDriver === 'SQLite3'
            ? "COALESCE(v.serie, '') || '-' || COALESCE(v.numero, '')"
            : "CONCAT(v.serie, '-', v.numero)";
        $cliente = $this->db->DBDriver === 'SQLite3'
            ? "COALESCE(NULLIF(p.razonsocial, ''), TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')))"
            : "COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, ''))))";
        if (ctype_digit($buscar)) {
            $condicion = " AND (v.idventa = ? OR ({$comprobante}) LIKE ? ESCAPE '!')";
            $parametros[] = (int) $buscar;
            $parametros[] = $patron;
        } else {
            $condicion = " AND (({$comprobante}) LIKE ? ESCAPE '!' OR p.nombres LIKE ? ESCAPE '!' OR p.apellidos LIKE ? ESCAPE '!' OR p.razonsocial LIKE ? ESCAPE '!' OR p.numerodoc LIKE ? ESCAPE '!')";
            $parametros = array_fill(0, 5, $patron);
        }
        $limite = min(max(1, $limite), 50);
        $sql = "SELECT DISTINCT v.idventa, v.serie, v.numero, v.fecha,
                {$cliente} AS cliente
            FROM ventas v JOIN personas p ON p.idpersona = v.idcliente
            JOIN (SELECT idventa, idproducto, SUM(cantidad) AS vendido FROM (" . implode(' UNION ALL ', $origenes) . ") origen GROUP BY idventa, idproducto) sv ON sv.idventa = v.idventa
            LEFT JOIN (SELECT idventa, idservicio, SUM(cantidad_servicio) AS programado FROM ordenes_servicio WHERE estado <> 'CANCELADO' GROUP BY idventa, idservicio) os ON os.idventa = sv.idventa AND os.idservicio = sv.idproducto
            WHERE v.fechaanulacion IS NULL AND sv.vendido > COALESCE(os.programado, 0){$condicion}
            ORDER BY v.idventa DESC LIMIT {$limite}";

        return array_map(static function (array $fila): array {
            $comprobante = trim((string) $fila['serie'] . '-' . (string) $fila['numero'], '-');
            return [
                'id' => (int) $fila['idventa'],
                'comprobante' => $comprobante,
                'cliente' => trim((string) $fila['cliente']),
                'fecha' => $fila['fecha'],
                'texto' => 'Venta N.º ' . $fila['idventa'] . ' · ' . $comprobante . ' · ' . trim((string) $fila['cliente']),
            ];
        }, $this->db->query($sql, $parametros)->getResultArray());
    }

    private function consultaOrdenes()
    {
        $cliente = $this->db->DBDriver === 'SQLite3'
            ? "COALESCE(NULLIF(p.razonsocial, ''), TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')))"
            : "COALESCE(NULLIF(p.razonsocial, ''), TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, ''))))";
        $tecnico = $this->db->DBDriver === 'SQLite3'
            ? "TRIM(COALESCE(t.nombres, '') || ' ' || COALESCE(t.apellidos, ''))"
            : "CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, ''))";
        return $this->db->table('ordenes_servicio os')
            ->select("os.*, {$cliente} AS cliente, {$tecnico} AS tecnico, pr.nombre AS servicio", false)
            ->join('personas p', 'p.idpersona = os.idcliente')
            ->join('tecnicos t', 't.idtecnico = os.idtecnico', 'left')
            ->join('productos pr', 'pr.idproducto = os.idservicio', 'left');
    }

    private function cantidadCentimos(string $cantidad, bool $permitirCero = false): int
    {
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', trim($cantidad), $partes)) {
            throw new RuntimeException('La cantidad vendida no tiene un formato válido.');
        }
        $valor = ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
        if (!$permitirCero && $valor < 1) {
            throw new RuntimeException('La cantidad vendida debe ser mayor que cero.');
        }
        return $valor;
    }

    private function cantidadDecimal(int $centimos): string
    {
        return intdiv($centimos, 100) . '.' . str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
