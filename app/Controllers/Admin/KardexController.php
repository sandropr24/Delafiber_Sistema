<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KardexModel;
use App\Models\MovimientoModel;
use App\Models\LocalModel;

class KardexController extends BaseController
{
    protected $kardexModel;
    protected $movimientoModel;
    protected $localModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->kardexModel     = model(KardexModel::class);
        $this->movimientoModel = model(MovimientoModel::class);
        $this->localModel      = model(LocalModel::class);
    }

    public function index()
    {
        $data = [
            'titulo'  => 'Control de Inventario - Saldos Actuales',
            'stock'   => $this->kardexModel->obtenerStockGeneral(),
            'locales' => $this->localModel->orderBy('nombrelocal', 'ASC')->findAll(),
        ];

        return view('kardex/index', $data);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('admin/kardex'));
        }

        $q       = $this->request->getGet('q');
        $idlocal = $this->request->getGet('idlocal');

        $stock = $this->kardexModel->buscarStockFiltrado($q, $idlocal);

        return $this->response->setJSON($stock);
    }

    public function historial($idkardex)
    {
        $kardexInfo = $this->kardexModel->select('kardex.*, productos.nombre as producto, locales.nombrelocal as local, unidadesmedida.abreviatura as unidad')
            ->join('productos', 'productos.idproducto = kardex.idproducto')
            ->join('unidadesmedida', 'unidadesmedida.idunidad = productos.idunidad', 'left')
            ->join('locales', 'locales.idlocal = kardex.idlocal')
            ->where('kardex.idkardex', $idkardex)
            ->first();

        if (!$kardexInfo) {
            return redirect()->to(base_url('admin/kardex'))->with('error', 'Registro de kardex no encontrado.');
        }

        $data = [
            'titulo'      => 'Kardex Detallado: ' . $kardexInfo['producto'],
            'kardex'      => $kardexInfo,
            'movimientos' => $this->movimientoModel->obtenerHistorialPorKardex($idkardex)
        ];

        return view('kardex/historial', $data);
    }
}
