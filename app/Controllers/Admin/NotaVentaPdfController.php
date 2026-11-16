<?php

namespace App\Controllers\Admin;

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
            return redirect()->to(base_url('admin/ventas'))
                ->with('error', 'Falta instalar Dompdf. Ejecuta: composer require dompdf/dompdf');
        }

        $venta = model(VentaModel::class)
            ->select('ventas.*,
                IF(personas.tipopersona = "NATURAL", TRIM(CONCAT(COALESCE(personas.nombres, ""), " ", COALESCE(personas.apellidos, ""))), COALESCE(personas.razonsocial, "")) AS cliente,
                personas.tipodoc, personas.numerodoc, personas.direccion AS cliente_direccion,
                locales.nombrelocal AS local')
            ->join('personas', 'personas.idpersona = ventas.idcliente', 'left')
            ->join('locales', 'locales.idlocal = ventas.idlocal', 'left')
            ->where('ventas.idventa', $idventa)
            ->first();

        if (!$venta) {
            throw PageNotFoundException::forPageNotFound('La venta no existe.');
        }

        if (!empty($venta['fechaanulacion'])) {
            return redirect()->to('admin/ventas/detalles/' . $idventa)
                ->with('error', 'La nota de venta fue anulada. Consulta su historial comercial.');
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
        // La copia de impresión JPEG permite generar el PDF incluso sin PHP GD.
        $rutaLogo = FCPATH . 'img/Delafiber-pdf.jpg';
        if (!is_readable($rutaLogo) && function_exists('imagecreatefrompng')) {
            $rutaLogo = FCPATH . 'img/Delafiber.png';
        }
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

        $empresa = array_column(
            db_connect()->table('configuracion')
                ->whereIn('clave', ['empresa_ruc', 'empresa_direccion', 'empresa_telefono', 'empresa_email'])
                ->get()->getResultArray(),
            'valor',
            'clave'
        );

        $html = view('ventas/pdf_nota', compact('venta', 'detalles', 'pagos', 'logo', 'estilosPdf', 'empresa'));
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $fuente = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 112,
            $canvas->get_height() - 29,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente,
            7,
            [0.44, 0.42, 0.49]
        );

        $archivo = 'nota_venta_' . $idventa . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Content-Disposition', 'inline; filename="' . $archivo . '"')
            ->setBody($pdf->output());
    }
}
