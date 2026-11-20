<?php

namespace App\Services;

use RuntimeException;

final class MetaWhatsAppTransportador implements PedidoWhatsAppTransportador
{
    public function enviar(string $destino, array $datos): void
    {
        $token = trim((string) env('whatsapp.accessToken', ''));
        $phoneNumberId = trim((string) env('whatsapp.phoneNumberId', ''));
        $plantilla = trim((string) env('whatsapp.template', ''));
        $version = trim((string) env('whatsapp.apiVersion', 'v22.0'));
        $idioma = trim((string) env('whatsapp.templateLanguage', 'es'));
        if ($token === '' || $phoneNumberId === '' || $plantilla === '') {
            throw new RuntimeException('WhatsApp Cloud no está configurado.');
        }
        if (!preg_match('/^v\d+\.\d+$/D', $version)) {
            throw new RuntimeException('La versión de WhatsApp Cloud no es válida.');
        }

        $parametros = [];
        foreach (['codigo', 'cliente', 'total', 'telefono', 'modalidad'] as $campo) {
            $parametros[] = ['type' => 'text', 'text' => (string) ($datos[$campo] ?? '')];
        }
        $respuesta = service('curlrequest')->post(
            "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages",
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $destino,
                    'type' => 'template',
                    'template' => [
                        'name' => $plantilla,
                        'language' => ['code' => $idioma],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => $parametros,
                        ]],
                    ],
                ],
                'http_errors' => false,
                'timeout' => 10,
            ]
        );
        if ($respuesta->getStatusCode() < 200 || $respuesta->getStatusCode() >= 300) {
            throw new RuntimeException('WhatsApp Cloud rechazó la notificación.');
        }
    }
}
