<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ReporteCajaService;
use InvalidArgumentException;

class ReporteCajaController extends BaseController
{
    public function index()
    {
        $servicio = new ReporteCajaService();
        $entrada = $this->request->getGet();
        try {
            $reporte = $servicio->consultar(is_array($entrada) ? $entrada : [], (int) session('idusuario'));
        } catch (InvalidArgumentException $e) {
            return redirect()->to(base_url('admin/caja/reportes'))->with('error', $e->getMessage());
        }
        return view('caja/reportes', ['titulo' => 'Reportes de caja'] + $reporte + $servicio->catalogos((int) session('idusuario')));
    }
}
