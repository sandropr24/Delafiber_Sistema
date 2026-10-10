<?php

namespace App\Controllers;

use App\Models\KardexModel;
use App\Models\MovimientoModel;

class KardexController extends BaseController
{
    protected $kardexModel;
    protected $movimientoModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->kardexModel     = new KardexModel();
        $this->movimientoModel = new MovimientoModel();
    }

    public function index()
    {
        $data = [
            'titulo' => 'Control de Inventario - Saldos Actuales',
            'stock'  => $this->kardexModel->obtenerStockGeneral()
        ];

        return view('kardex/index', $data);
    }

    public function historial($idkardex)
    {
        $kardexInfo = $this->kardexModel->select('kardex.*, productos.descripcion as producto, locales.nombrelocal as local')
                                        ->join('productos', 'productos.idproducto = kardex.idproducto')
                                        ->join('locales', 'locales.idlocal = kardex.idlocal')
                                        ->where('kardex.idkardex', $idkardex)
                                        ->first();

        if (!$kardexInfo) {
            return redirect()->to(base_url('kardex'))->with('error', 'Registro de kardex no encontrado.');
        }

        $data = [
            'titulo'      => 'Kardex Detallado: ' . $kardexInfo['producto'],
            'kardex'      => $kardexInfo,
            'movimientos' => $this->movimientoModel->obtenerHistorialPorKardex($idkardex)
        ];

        return view('kardex/historial', $data);
    }
}