<?php

use App\Services\PedidoWhatsAppTransportador;
use App\Services\PedidoWebWhatsAppService;
use CodeIgniter\Test\CIUnitTestCase;

final class TransportadorWhatsAppFake implements PedidoWhatsAppTransportador
{
    public array $mensajes = [];
    public bool $fallar = false;

    public function enviar(string $destino, array $datos): void
    {
        if ($this->fallar) throw new RuntimeException('Fallo de WhatsApp simulado.');
        $this->mensajes[] = compact('destino', 'datos');
    }
}

final class PedidoWebWhatsAppServiceTest extends CIUnitTestCase
{
    public function testNotificaNuevoPedidoAlNumeroConfigurado(): void
    {
        $transportador = new TransportadorWhatsAppFake();
        $servicio = new PedidoWebWhatsAppService($transportador, '+51 999 888 777');

        $this->assertTrue($servicio->nuevo([
            'codigo' => 'PED-000123',
            'cliente' => 'Ana Cliente',
            'total' => 241,
            'telefono' => '999111222',
            'modalidad' => 'DOMICILIO',
        ]));
        $this->assertSame('51999888777', $transportador->mensajes[0]['destino']);
        $this->assertSame('PED-000123', $transportador->mensajes[0]['datos']['codigo']);
        $this->assertSame('S/ 241.00', $transportador->mensajes[0]['datos']['total']);
    }

    public function testOmiteNotificacionSinDestinatarioYAbsorbeFallos(): void
    {
        $transportador = new TransportadorWhatsAppFake();
        $this->assertNull((new PedidoWebWhatsAppService($transportador, ''))->nuevo([]));
        $transportador->fallar = true;
        $this->assertFalse((new PedidoWebWhatsAppService($transportador, '51999888777'))->nuevo(['codigo' => 'PED-1']));
    }
}
