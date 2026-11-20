<?php

use App\Libraries\EntregaPedidoWeb;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class EntregaPedidoWebTest extends CIUnitTestCase
{
    private function web(array $cambios = []): array
    {
        return array_replace([
            'modalidad' => 'DOMICILIO', 'direccion_entrega' => 'Avenida Principal 123',
            'nombre_contacto' => 'Ana Cliente', 'email_contacto' => 'ana@example.com',
            'telefono_contacto' => '999 999 999',
        ], $cambios);
    }

    private function entrega(array $cambios = []): array
    {
        return array_replace([
            'tipoentrega' => 'DOMICILIO', 'direccion' => 'Avenida Principal 123',
            'telefono' => '999999999', 'referencia' => '', 'idtecnico' => null,
        ], $cambios);
    }

    public function testProponeDomicilioSinCambiarLaSolicitudOriginal(): void
    {
        $original = $this->web();
        $copia = $original;
        $propuesta = EntregaPedidoWeb::preparar($original);
        $this->assertSame('DOMICILIO', $propuesta['campos']['tipoentrega']);
        $this->assertSame('Avenida Principal 123', $propuesta['campos']['direccion_entrega']);
        $this->assertSame('999999999', $propuesta['campos']['telefono_entrega']);
        $this->assertSame([], $propuesta['avisos']);
        $this->assertSame($copia, $original);
        $this->assertArrayNotHasKey('pagos', $propuesta['campos']);
        $this->assertArrayNotHasKey('total', $propuesta['campos']);
        $this->assertArrayNotHasKey('estado', $propuesta['campos']);
    }

    public function testMapeaRecojoSinCopiarDireccionNiTelefonoAUnaEntregaInexistente(): void
    {
        $propuesta = EntregaPedidoWeb::preparar($this->web(['modalidad' => 'RECOJO']));
        $this->assertSame('RECOJO_TIENDA', $propuesta['campos']['tipoentrega']);
        $this->assertSame('', $propuesta['campos']['direccion_entrega']);
        $this->assertSame('', $propuesta['campos']['telefono_entrega']);
        $this->assertSame([], $propuesta['avisos']);
    }

    public function testPedidosAdministrativosNoRecibenCamposWeb(): void
    {
        $this->assertSame(['campos' => [], 'avisos' => []], EntregaPedidoWeb::preparar(null));
        $this->assertSame([], EntregaPedidoWeb::cambios(null, $this->entrega()));
    }

    #[DataProvider('telefonos')]
    public function testNormalizaSoloTelefonosInequivocos(string $original, string $propuesto, bool $aviso): void
    {
        $resultado = EntregaPedidoWeb::preparar($this->web(['telefono_contacto' => $original]));
        $this->assertSame($propuesto, $resultado['campos']['telefono_entrega']);
        $this->assertSame($aviso, count($resultado['avisos']) > 0);
    }

    public static function telefonos(): array
    {
        return [
            ['999999999', '999999999', false],
            ['999 999 999', '999999999', false],
            ['(999) 999-999', '999999999', false],
            ['+51 999 999 999', '999999999', false],
            ['51999999999', '51999999999', true],
            ['+54 999 999 999', '+54 999 999 999', true],
            ['999999999 anexo 2', '999999999 anexo 2', true],
            ['', '', true],
        ];
    }

    public function testConservaUnaDireccionLargaYAdvierteEnLugarDeRecortarla(): void
    {
        $direccion = str_repeat('Á', 200);
        $propuesta = EntregaPedidoWeb::preparar($this->web(['direccion_entrega' => $direccion]));
        $this->assertSame($direccion, $propuesta['campos']['direccion_entrega']);
        $this->assertStringContainsString('150 caracteres', implode(' ', $propuesta['avisos']));
        $this->expectException(InvalidArgumentException::class);
        EntregaPedidoWeb::normalizarVenta($this->entrega(['direccion' => $direccion]));
    }

    public function testModalidadDesconocidaNoSeConvierteSilenciosamenteEnRecojo(): void
    {
        $propuesta = EntregaPedidoWeb::preparar($this->web(['modalidad' => 'OTRA']));
        $this->assertSame('', $propuesta['campos']['tipoentrega']);
        $this->assertNotEmpty($propuesta['avisos']);
    }

    public function testVendedorPuedeAjustarLaEntregaSinModificarElOriginal(): void
    {
        $original = $this->web();
        $entrega = EntregaPedidoWeb::normalizarVenta($this->entrega([
            'direccion' => '  Otra avenida 456  ', 'telefono' => '988888888', 'referencia' => 'Puerta azul',
        ]));
        $this->assertSame('Otra avenida 456', $entrega['direccion']);
        $this->assertSame('988888888', $entrega['telefono']);
        $this->assertSame('Puerta azul', $entrega['referencia']);
        $this->assertSame('Avenida Principal 123', $original['direccion_entrega']);
        $cambios = EntregaPedidoWeb::cambios($original, $entrega);
        $this->assertSame(['Dirección de entrega', 'Teléfono de entrega'], array_column($cambios, 'campo'));
        $this->assertSame('Avenida Principal 123', $cambios[0]['antes']);
        $this->assertSame('Otra avenida 456', $cambios[0]['despues']);
    }

    public function testSoloCambiarElFormatoDelTelefonoNoGeneraUnAjusteFalso(): void
    {
        $this->assertSame([], EntregaPedidoWeb::cambios($this->web(), $this->entrega()));
    }

    public function testCambiarADomicilioORecojoQuedaReflejadoEnLosAjustes(): void
    {
        $pickup = EntregaPedidoWeb::normalizarVenta($this->entrega(['tipoentrega' => 'RECOJO_TIENDA']));
        $this->assertNull($pickup['direccion']);
        $this->assertNull($pickup['telefono']);
        $this->assertCount(3, EntregaPedidoWeb::cambios($this->web(), $pickup));
        $cambios = EntregaPedidoWeb::cambios($this->web(['modalidad' => 'RECOJO']), $this->entrega());
        $this->assertSame('Modalidad de entrega', $cambios[0]['campo']);
        $this->assertSame('Entrega a domicilio', $cambios[0]['despues']);
    }

    public function testRecojoDescartaCamposOcultosSinUsarlosParaLaVenta(): void
    {
        $datos = EntregaPedidoWeb::normalizarVenta([
            'tipoentrega' => 'RECOJO_TIENDA', 'direccion' => str_repeat('A', 200),
            'telefono' => '+54 123456789', 'referencia' => 'No usar', 'idtecnico' => 7,
            'estado' => 'ENTREGADO', 'cobrocontraentrega' => 1,
        ]);
        $this->assertSame([
            'tipoentrega' => 'RECOJO_TIENDA', 'direccion' => null,
            'referencia' => null, 'telefono' => null, 'idtecnico' => null,
        ], $datos);
    }

    #[DataProvider('entregasInvalidas')]
    public function testValidaLaEntregaFinalEnElServidor(array $cambios): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntregaPedidoWeb::normalizarVenta($this->entrega($cambios));
    }

    public static function entregasInvalidas(): array
    {
        return [
            [['tipoentrega' => '']], [['tipoentrega' => 'OTRA']],
            [['direccion' => ' ']], [['direccion' => str_repeat('A', 151)]],
            [['referencia' => str_repeat('A', 151)]],
            [['telefono' => '99999999']], [['telefono' => '+51 999999999']],
            [['direccion' => ['invalida']]],
        ];
    }

    public function testAdmiteLimitesDeLaTablaDeEntregas(): void
    {
        $entrega = $this->entrega(['direccion' => str_repeat('Á', 150), 'referencia' => str_repeat('R', 150)]);
        $this->assertEquals($entrega, EntregaPedidoWeb::normalizarVenta($entrega));
    }

    public function testVistaEscapaElContactoYLasIndicacionesYNoExponeElToken(): void
    {
        $html = view('pedidos/_solicitud_web', [
            'datosWeb' => $this->web([
                'nombre_contacto' => '<script>contacto</script>',
                'email_contacto' => 'correo<&>@example.com',
                'telefono_contacto' => '<telefono>',
                'direccion_entrega' => 'Avenida <especial>',
                'token_seguimiento_hash' => 'HASH_PRIVADO_NO_PUBLICAR',
            ]),
            'observacionesPedido' => "<img src=x onerror=alert(1)>\nLlamar antes",
        ], ['saveData' => false]);
        $this->assertStringContainsString('&lt;script&gt;contacto&lt;/script&gt;', $html);
        $this->assertStringContainsString('Avenida &lt;especial&gt;', $html);
        $this->assertStringContainsString('&lt;telefono&gt;', $html);
        $this->assertStringContainsString('Llamar antes', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringNotContainsString('HASH_PRIVADO_NO_PUBLICAR', $html);
    }

    public function testNoMuestraUnaSolicitudWebEnPedidosAdministrativos(): void
    {
        $html = view('pedidos/_solicitud_web', ['datosWeb' => null], ['saveData' => false]);
        $this->assertStringNotContainsString('<section', $html);
        $this->assertStringNotContainsString('Solicitud original', $html);
    }
}
