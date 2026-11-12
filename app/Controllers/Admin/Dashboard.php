<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $hoy = date('Y-m-d');
        $inicio = date('Y-m-d', strtotime('-6 days'));

        $ventasHoy = $db->table('ventas')
            ->selectSum('total', 'monto_hoy')
            ->where('fecha >=', $hoy . ' 00:00:00')
            ->where('fecha <', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00')
            ->get()->getRowArray();

        $stockCritico = $db->table('kardex')
            ->where('stockactual <= minima', null, false)
            ->where('minima >', 0)->countAllResults();

        $totalProductos = $db->table('productos')
            ->where('estado', 1)->countAllResults();

        $cotizacionesPendientes = $db->table('cotizacion')
            ->join('estadocotizacion', 'estadocotizacion.idestado = cotizacion.idestado')
            ->where('estadocotizacion.estado', 'Pendiente')->countAllResults();

        $entregasPendientes = $db->table('entregas')
            ->where('tipoentrega', 'DOMICILIO')
            ->whereIn('estado', ['PENDIENTE', 'EN_CAMINO'])
            ->countAllResults();

        $ultimasVentas = $db->table('ventas')
            ->select('ventas.*, personas.nombres, personas.apellidos, personas.razonsocial')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->orderBy('ventas.fecha', 'DESC')->limit(5)
            ->get()->getResultArray();

        $masVendidos = $db->table('detventa dv')
            ->select('p.idproducto, p.nombre, p.tipo, SUM(dv.cantidad) AS unidades, SUM(dv.cantidad * dv.precioventa) AS importe', false)
            ->join('productos p', 'p.idproducto = dv.idproducto')
            ->join('ventas v', 'v.idventa = dv.idventa')
            ->where('v.fechaanulacion', null)
            ->where('p.tipo', 'PRODUCTO')
            ->groupBy('p.idproducto, p.nombre, p.tipo')
            ->orderBy('unidades', 'DESC')->limit(5)
            ->get()->getResultArray();

        $ventasPorDia = $db->table('ventas')
            ->select('DATE(fecha) AS dia, SUM(total) AS monto', false)
            ->where('fecha >=', $inicio . ' 00:00:00')
            ->where('fecha <', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00')
            ->groupBy('DATE(fecha)')->get()->getResultArray();

        $montos = [];
        foreach ($ventasPorDia as $fila) {
            $montos[$fila['dia']] = (float) $fila['monto'];
        }
        $graficoDias = [];
        $graficoMontos = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $graficoDias[] = date('d/m', strtotime($fecha));
            $graficoMontos[] = $montos[$fecha] ?? 0;
        }

        return view('dashboard/index', [
            'titulo' => 'Panel de Control',
            'nombreUsuario' => session()->get('nombres') ?? session()->get('nombreusuario'),
            'rol' => session()->get('rol'),
            'totalVentasHoy' => (float) ($ventasHoy['monto_hoy'] ?? 0),
            'stockCritico' => $stockCritico,
            'totalProductos' => $totalProductos,
            'cotizacionesPendientes' => $cotizacionesPendientes,
            'entregasPendientes' => $entregasPendientes,
            'ultimasVentas' => $ultimasVentas,
            'masVendidos' => $masVendidos,
            'graficoDias' => $graficoDias,
            'graficoMontos' => $graficoMontos,
        ]);
    }
}
