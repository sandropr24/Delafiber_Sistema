<?php

use CodeIgniter\Test\CIUnitTestCase;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @internal
 */
final class NotaVentaPdfTest extends CIUnitTestCase
{
    private function datosNota(): array
    {
        return [
            'venta' => [
                'serie' => 'NV01',
                'numero' => '00000124',
                'cliente' => 'Cliente de prueba',
                'tipodoc' => 'DNI',
                'numerodoc' => '12345678',
                'fecha' => '2026-11-16 10:30:00',
                'local' => 'Almacén principal',
                'subtotal' => 25,
                'total' => 25,
            ],
            'detalles' => [
                ['producto' => 'Cable de red', 'cantidad' => 5, 'precioventa' => 5],
            ],
            'pagos' => [],
            'logo' => null,
            'estilosPdf' => file_get_contents(FCPATH . 'css/pdf.css'),
        ];
    }

    private function renderizar(array $datos): string
    {
        return view('ventas/pdf_nota', $datos, ['saveData' => false]);
    }

    public function testMuestraNumeracionCompletaYEscapaLosDatos(): void
    {
        $datos = $this->datosNota();
        $datos['venta']['cliente'] = '<script>cliente</script>';
        $datos['detalles'][0]['producto'] = 'Cable & <especial>';
        $datos['empresa'] = ['empresa_direccion' => '<Dirección de prueba>'];

        $html = $this->renderizar($datos);

        $this->assertStringContainsString('NV01-00000124', $html);
        $this->assertStringContainsString('&lt;script&gt;cliente&lt;/script&gt;', $html);
        $this->assertStringContainsString('Cable &amp; &lt;especial&gt;', $html);
        $this->assertStringContainsString('&lt;Dirección de prueba&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Este documento no sustituye una boleta o factura.', $html);
    }

    public function testConservaLosImportesRegistradosYElDescuento(): void
    {
        $datos = $this->datosNota();
        $datos['venta'] = array_replace($datos['venta'], [
            'descuento' => 2.5,
            'tipodescuento' => 'PORCENTAJE',
            'valordescuento' => 10,
            'total' => 22.5,
        ]);

        $html = $this->renderizar($datos);

        $this->assertStringContainsString('Descuento (10.00%)', $html);
        $this->assertStringContainsString('− S/ 2.50', $html);
        $this->assertStringContainsString('S/ 22.50', $html);
        $this->assertStringContainsString('S/ 25.00', $html);
    }

    public function testRecibidoYVueltoSoloSeMuestranCuandoHayDatos(): void
    {
        $datos = $this->datosNota();
        $datos['pagos'] = [['tipopago' => 'Transferencia', 'monto' => 25]];
        $html = $this->renderizar($datos);
        $this->assertStringNotContainsString('>Recibido<', $html);
        $this->assertStringNotContainsString('>Vuelto<', $html);

        $datos['pagos'][] = [
            'tipopago' => 'Efectivo', 'monto' => 20,
            'montorecibido' => 50, 'vuelto' => 30,
        ];
        $html = $this->renderizar($datos);
        $this->assertStringContainsString('>Recibido<', $html);
        $this->assertStringContainsString('>Vuelto<', $html);
        $this->assertStringContainsString('S/ 50.00', $html);
        $this->assertStringContainsString('S/ 30.00', $html);
    }

    public function testGeneraPdfConLogoSinRequerirGdYConVariasPaginas(): void
    {
        $datos = $this->datosNota();
        $datos['logo'] = 'data:image/jpeg;base64,' . base64_encode(
            file_get_contents(FCPATH . 'img/Delafiber-pdf.jpg')
        );
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('tempDir', WRITEPATH . 'cache');
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml($this->renderizar($datos), 'UTF-8');
        $pdf->render();
        $this->assertSame(1, $pdf->getCanvas()->get_page_count());
        $this->assertStringStartsWith('%PDF-', $pdf->output());

        $datos['detalles'] = array_fill(0, 60, $datos['detalles'][0]);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml($this->renderizar($datos), 'UTF-8');
        $pdf->render();
        $this->assertGreaterThan(1, $pdf->getCanvas()->get_page_count());
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }

    public function testAdmiteDatosOpcionalesAusentesYSubtotalAnterior(): void
    {
        $datos = $this->datosNota();
        $datos['venta'] = ['numero' => '001', 'total' => 25];

        $html = $this->renderizar($datos);

        $this->assertStringContainsString('DELAFIBER', $html);
        $this->assertStringContainsString('S/ 25.00', $html);
        $this->assertStringNotContainsString('Detalle de pagos', $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}
