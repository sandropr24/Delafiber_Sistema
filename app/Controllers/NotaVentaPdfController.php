<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\VentaModel;
use App\Models\DetVentaModel;
use App\Models\PagoVentaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class NotaVentaPdfController extends BaseController
{
    public function generar(int $idventa)
    {
        if ($idventa <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (!class_exists(Dompdf::class)) {
            return redirect()->to(base_url('ventas'))
                ->with('error', 'Falta instalar Dompdf. Ejecuta: composer require dompdf/dompdf');
        }

        $venta = model(VentaModel::class)
            ->select('ventas.*, CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, "")) AS cliente, personas.numerodoc, locales.nombrelocal AS local')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
            ->where('ventas.idventa', $idventa)
            ->first();

        if (!$venta) {
            throw PageNotFoundException::forPageNotFound('La venta no existe.');
        }

        $detalles = model(DetVentaModel::class)
            ->select('detventa.*, productos.nombre AS producto')
            ->join('productos', 'productos.idproducto = detventa.idproducto', 'left')
            ->where('detventa.idventa', $idventa)
            ->findAll();

        $pagos = model(PagoVentaModel::class)
            ->select('pagoventa.*, tipospago.tipopago')
            ->join('tipospago', 'tipospago.idtipopago = pagoventa.idtipopago', 'left')
            ->where('pagoventa.idventa', $idventa)
            ->findAll();

        $logo = null;
        $rutaLogo = FCPATH . 'img/Delafiber.png';
        if (is_file($rutaLogo) && is_readable($rutaLogo)) {
            $tipo = mime_content_type($rutaLogo) ?: 'image/png';
            $logo = 'data:' . $tipo . ';base64,' . base64_encode(file_get_contents($rutaLogo));
        }


        $rutaCss = FCPATH . 'css/pdf.css';
        if (!is_file($rutaCss) || !is_readable($rutaCss)) {
            log_message('error', 'CSS de nota de venta no encontrado: ' . $rutaCss);
            return $this->response->setStatusCode(500)->setBody('No se encontró el archivo CSS de la nota de venta. Verifica public/css/pdf.css');
        }
        $estilosPdf = file_get_contents($rutaCss);
        if ($estilosPdf === false || trim($estilosPdf) === '') {
            return $this->response->setStatusCode(500)->setBody('El CSS de la nota de venta está vacío o no se pudo leer.');
        }

        $html = view('ventas/pdf_nota', compact('venta', 'detalles', 'pagos', 'logo', 'estilosPdf'));
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $archivo = 'nota_venta_' . $idventa . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $archivo . '"')
            ->setBody($pdf->output());
    }
}
