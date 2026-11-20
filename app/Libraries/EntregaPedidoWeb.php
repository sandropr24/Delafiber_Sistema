<?php

namespace App\Libraries;

use InvalidArgumentException;

final class EntregaPedidoWeb
{
    public static function preparar(?array $web): array
    {
        if ($web === null) {
            return ['campos' => [], 'avisos' => []];
        }

        $tipo = match ($web['modalidad'] ?? '') {
            'RECOJO' => 'RECOJO_TIENDA',
            'DOMICILIO' => 'DOMICILIO',
            default => '',
        };
        $domicilio = $tipo === 'DOMICILIO';
        $direccion = $domicilio ? trim((string) ($web['direccion_entrega'] ?? '')) : '';
        $telefono = $domicilio ? self::telefonoPropuesto((string) ($web['telefono_contacto'] ?? '')) : '';
        $avisos = [];
        if ($tipo === '') {
            $avisos[] = 'Revise la modalidad de entrega del pedido web antes de registrar la venta.';
        }
        if ($domicilio && ($direccion === '' || mb_strlen($direccion) > 150)) {
            $avisos[] = 'Revise la dirección del pedido web: la venta requiere una dirección de hasta 150 caracteres. La solicitud original se conserva completa.';
        }
        if ($domicilio && !preg_match('/^[0-9]{9}$/D', $telefono)) {
            $avisos[] = 'Revise el teléfono del pedido web: la entrega de la venta requiere 9 dígitos.';
        }

        return [
            'campos' => [
                'tipoentrega' => $tipo, 'direccion_entrega' => $direccion,
                'telefono_entrega' => $telefono, 'referencia_entrega' => '',
            ],
            'avisos' => $avisos,
        ];
    }

    private static function telefonoPropuesto(string $telefono): string
    {
        $telefono = trim($telefono);
        if (preg_match('/^[0-9+() -]+$/D', $telefono)) {
            $compacto = str_replace([' ', '(', ')', '-'], '', $telefono);
            if (preg_match('/^[0-9]{9}$/D', $compacto)) {
                return $compacto;
            }
            if (preg_match('/^\+51([0-9]{9})$/D', $compacto, $partes)) {
                return $partes[1];
            }
        }
        // No adivinar ni recortar números de otro país o con formato ambiguo.
        return $telefono;
    }

    public static function normalizarVenta(array $entrega): array
    {
        $tipo = $entrega['tipoentrega'] ?? '';
        if (!in_array($tipo, ['RECOJO_TIENDA', 'DOMICILIO'], true)) {
            throw new InvalidArgumentException('Seleccione una modalidad de entrega válida.');
        }
        if ($tipo === 'RECOJO_TIENDA') {
            return ['tipoentrega' => $tipo, 'direccion' => null, 'referencia' => null, 'telefono' => null, 'idtecnico' => null];
        }

        $datos = ['tipoentrega' => $tipo];
        foreach (['direccion', 'referencia', 'telefono'] as $campo) {
            $valor = $entrega[$campo] ?? '';
            if (!is_scalar($valor)) {
                throw new InvalidArgumentException('Los datos de entrega no son válidos.');
            }
            $datos[$campo] = trim((string) $valor);
        }
        if ($datos['direccion'] === '' || mb_strlen($datos['direccion']) > 150) {
            throw new InvalidArgumentException('La dirección de entrega es obligatoria y no puede superar 150 caracteres.');
        }
        if (mb_strlen($datos['referencia']) > 150) {
            throw new InvalidArgumentException('La referencia de entrega no puede superar 150 caracteres.');
        }
        if (!preg_match('/^[0-9]{9}$/D', $datos['telefono'])) {
            throw new InvalidArgumentException('El teléfono de entrega debe tener 9 dígitos.');
        }
        $datos['idtecnico'] = $entrega['idtecnico'] ?? null;
        return $datos;
    }

    public static function cambios(?array $web, array $entrega): array
    {
        if ($web === null) {
            return [];
        }
        $original = self::preparar($web)['campos'];
        $domicilio = ($entrega['tipoentrega'] ?? '') === 'DOMICILIO';
        $etiquetas = ['RECOJO_TIENDA' => 'Recojo en tienda', 'DOMICILIO' => 'Entrega a domicilio'];
        $comparar = [
            'Modalidad de entrega' => [
                $etiquetas[$original['tipoentrega']] ?? 'Sin especificar',
                $etiquetas[$entrega['tipoentrega'] ?? ''] ?? 'Sin especificar',
            ],
            'Dirección de entrega' => [$original['direccion_entrega'], $domicilio ? trim((string) ($entrega['direccion'] ?? '')) : ''],
            'Teléfono de entrega' => [$original['telefono_entrega'], $domicilio ? trim((string) ($entrega['telefono'] ?? '')) : ''],
        ];
        $cambios = [];
        foreach ($comparar as $campo => [$antes, $despues]) {
            if ($antes !== $despues) {
                $cambios[] = compact('campo', 'antes', 'despues');
            }
        }
        return $cambios;
    }
}
