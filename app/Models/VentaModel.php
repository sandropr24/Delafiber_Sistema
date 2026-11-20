<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\VentaService;
use App\Services\VoucherStorage;

class VentaModel extends Model
{
    protected $table            = 'ventas';
    protected $primaryKey       = 'idventa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'idcliente',
        'idlocal',
        'idlinea',
        'idcanal',
        'idvendedor',
        'idestado',
        'formapago',
        'montoinicial',
        'nrocuotas',
        'frecuenciacuotas',
        'idpedido',
        'idcajero',
        'tipodocumento',
        'serie',
        'cdr',
        'numero',
        'estafacturado',
        'fecha',
        'subtotal',
        'descuento',
        'tipodescuento',
        'valordescuento',
        'motivodescuento',
        'total'
    ];

    protected function almacenVouchers(): VoucherStorage
    {
        return new VoucherStorage();
    }

    /**
     * Compatibilidad hacia atrás: la lógica transaccional vive en VentaService.
     * Se conserva este método porque los llamadores y pruebas lo invocan desde
     * el modelo y necesitan seguir controlando el almacén de vouchers.
     */
    public function registrarVenta($dataVenta, $detalles, $pagos, $entrega = null, array $archivosVoucher = [])
    {
        return (new VentaService($this->db, $this->almacenVouchers()))
            ->registrar($dataVenta, $detalles, $pagos, $entrega, $archivosVoucher);
    }
}
