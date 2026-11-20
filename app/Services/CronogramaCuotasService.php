<?php

namespace App\Services;

use App\Libraries\CobroVenta;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class CronogramaCuotasService
{
    public function generar($total, $inicial, $numero, string $frecuencia, string $primeraFecha, string $fechaVenta): array
    {
        $totalCentimos = CobroVenta::centimos($total);
        $inicialCentimos = CobroVenta::centimos($inicial);
        if ($totalCentimos <= 0 || $inicialCentimos < 0 || $inicialCentimos >= $totalCentimos) {
            throw new InvalidArgumentException('El pago inicial debe ser menor que el total de la venta.');
        }
        if (!is_scalar($numero) || !ctype_digit((string) $numero) || (int) $numero < 1 || (int) $numero > 120) {
            throw new InvalidArgumentException('El número de cuotas debe estar entre 1 y 120.');
        }
        $numero = (int) $numero;
        $frecuencia = strtoupper(trim($frecuencia));
        if (!in_array($frecuencia, ['SEMANAL', 'QUINCENAL', 'MENSUAL'], true)) {
            throw new InvalidArgumentException('Selecciona una frecuencia de cuotas válida.');
        }
        $primera = $this->fecha($primeraFecha, 'primera fecha de vencimiento');
        $venta = $this->fecha($fechaVenta, 'fecha de venta');
        if ($primera < $venta) throw new InvalidArgumentException('La primera cuota no puede vencer antes de la venta.');

        $saldo = $totalCentimos - $inicialCentimos;
        if ($saldo < $numero) throw new InvalidArgumentException('El saldo no permite generar cuotas de al menos S/ 0.01.');
        $base = intdiv($saldo, $numero);
        $resto = $saldo % $numero;
        $cuotas = [];
        for ($indice = 0; $indice < $numero; $indice++) {
            $monto = $base + ($indice < $resto ? 1 : 0);
            $cuotas[] = [
                'nrocuota' => $indice + 1,
                'fechavencimiento' => $this->vencimiento($primera, $frecuencia, $indice)->format('Y-m-d'),
                'monto' => number_format($monto / 100, 2, '.', ''),
                'montopagado' => '0.00',
                'estado' => 'PENDIENTE',
            ];
        }
        return $cuotas;
    }

    private function fecha(string $valor, string $campo): DateTimeImmutable
    {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', trim($valor), new DateTimeZone('UTC'));
        $errores = DateTimeImmutable::getLastErrors();
        if (!$fecha || ($errores !== false && ($errores['warning_count'] || $errores['error_count'])) || $fecha->format('Y-m-d') !== trim($valor)) {
            throw new InvalidArgumentException('La ' . $campo . ' no es válida.');
        }
        return $fecha;
    }

    private function vencimiento(DateTimeImmutable $primera, string $frecuencia, int $indice): DateTimeImmutable
    {
        if ($frecuencia === 'SEMANAL') return $primera->modify('+' . ($indice * 7) . ' days');
        if ($frecuencia === 'QUINCENAL') return $primera->modify('+' . ($indice * 15) . ' days');
        $inicioMes = $primera->modify('first day of this month')->modify('+' . $indice . ' months');
        $dia = min((int) $primera->format('d'), (int) $inicioMes->format('t'));
        return $inicioMes->setDate((int) $inicioMes->format('Y'), (int) $inicioMes->format('m'), $dia);
    }
}
