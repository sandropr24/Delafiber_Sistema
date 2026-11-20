<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class GarantiaWorkflow
{
    public const ETIQUETAS = [
        'RECIBIDO' => 'Recibido',
        'ENVIADO_PROVEEDOR' => 'Enviado a proveedor',
        'REPARADO' => 'Reparado',
        'REEMPLAZADO' => 'Reemplazado',
        'ENTREGADO_CLIENTE' => 'Entregado al cliente',
    ];

    public function siguientes(string $estado): array
    {
        return match ($estado) {
            'RECIBIDO' => ['ENVIADO_PROVEEDOR', 'REPARADO', 'REEMPLAZADO'],
            'ENVIADO_PROVEEDOR' => ['REPARADO', 'REEMPLAZADO'],
            'REPARADO', 'REEMPLAZADO' => ['ENTREGADO_CLIENTE'],
            default => [],
        };
    }

    public function validarTransicion(string $actual, string $nuevo, string $nota): void
    {
        if (!in_array($nuevo, $this->siguientes($actual), true)) {
            throw new InvalidArgumentException('El caso cambió o la transición no está permitida. Recarga el detalle.');
        }
        $this->texto($nota, 'observación', 5);
    }

    public function texto($valor, string $campo, int $minimo = 0): string
    {
        if (!is_scalar($valor) && $valor !== null) throw new InvalidArgumentException('La ' . $campo . ' no es válida.');
        $valor = trim((string) $valor);
        if (mb_strlen($valor) < $minimo || mb_strlen($valor) > 255) {
            throw new InvalidArgumentException('La ' . $campo . ' debe tener entre ' . $minimo . ' y 255 caracteres.');
        }
        return $valor;
    }

    public function vencimiento(string $fechaVenta, int $meses): string
    {
        if ($meses < 1 || $meses > 120) throw new InvalidArgumentException('El plazo de garantía debe ser de 1 a 120 meses.');
        $texto = substr($fechaVenta, 0, 10);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        if (!$fecha || $fecha->format('Y-m-d') !== $texto) throw new InvalidArgumentException('La fecha de venta no es válida.');
        $mes = $fecha->modify('first day of this month')->modify('+' . $meses . ' months');
        return $mes->setDate((int) $mes->format('Y'), (int) $mes->format('m'), min((int) $fecha->format('d'), (int) $mes->format('t')))->format('Y-m-d');
    }

    public function validarVigencia(string $fechaVenta, int $meses, string $hoy): string
    {
        $vencimiento = $this->vencimiento($fechaVenta, $meses);
        if (substr($fechaVenta, 0, 10) > $hoy) throw new InvalidArgumentException('La venta tiene una fecha futura.');
        if ($vencimiento < $hoy) throw new InvalidArgumentException('La garantía venció el ' . $vencimiento . '. No se registró el caso.');
        return $vencimiento;
    }
}
