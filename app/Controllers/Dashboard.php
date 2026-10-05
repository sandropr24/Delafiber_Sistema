<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $hoy = date('Y-m-d');

        $ventasHoy = $db->table('ventas')
            ->selectSum('total', 'monto_hoy')
            ->where('DATE(fecha)', $hoy)
            ->get()
            ->getRowArray();


        $stockCritico = $db->table('kardex')
            ->where('stockactual <= minima')
            ->where('minima >', 0)
            ->countAllResults();


        $totalProductos = $db->table('productos')
            ->where('estado', 1)
            ->countAllResults();


        $cotizacionesPendientes = $db->table('cotizacion')
            ->join('estadocotizacion', 'estadocotizacion.idestadocotizacion = cotizacion.idestado')
            ->where('estadocotizacion.estadocotizacion', 'Pendiente')
            ->countAllResults();

        $ultimasVentas = $db->table('ventas')
            ->select('ventas.*, personas.nombres, personas.apellidos')
            ->join('personas', 'personas.idpersona = ventas.idcliente')
            ->orderBy('ventas.fecha', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $data = [
            'titulo'                 => 'Panel de Control',
            'nombreUsuario'          => session()->get('nombres') ?? session()->get('nombreusuario'),
            'rol'                    => session()->get('rol'),
            'totalVentasHoy'         => (float) ($ventasHoy['monto_hoy'] ?? 0.00),
            'stockCritico'           => $stockCritico,
            'totalProductos'         => $totalProductos,
            'cotizacionesPendientes' => $cotizacionesPendientes,
            'ultimasVentas'          => $ultimasVentas,
        ];

        return view('dashboard/index', $data);
    }
}
