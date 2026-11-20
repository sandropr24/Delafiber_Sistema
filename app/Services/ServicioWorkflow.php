<?php

namespace App\Services;

use RuntimeException;

class ServicioWorkflow
{
    public const PENDIENTE = 'PENDIENTE';
    public const PROGRAMADO = 'PROGRAMADO';
    public const EN_PROCESO = 'EN_PROCESO';
    public const FINALIZADO = 'FINALIZADO';
    public const CANCELADO = 'CANCELADO';
    public const ESTADOS = [self::PENDIENTE, self::PROGRAMADO, self::EN_PROCESO, self::FINALIZADO, self::CANCELADO];
    public const ETIQUETAS = [
        self::PENDIENTE => 'Pendiente',
        self::PROGRAMADO => 'Programado',
        self::EN_PROCESO => 'En proceso',
        self::FINALIZADO => 'Finalizado',
        self::CANCELADO => 'Cancelado',
    ];

    private const TRANSICIONES = [
        self::PENDIENTE => [self::CANCELADO],
        self::PROGRAMADO => [self::EN_PROCESO, self::CANCELADO],
        self::EN_PROCESO => [self::FINALIZADO],
    ];

    public function validarTransicion(string $actual, string $nuevo, string $nota): void
    {
        if (!in_array($nuevo, self::TRANSICIONES[$actual] ?? [], true)) {
            throw new RuntimeException('Este cambio de estado no está permitido.');
        }
        if ($nuevo === self::FINALIZADO && mb_strlen($nota) < 5) {
            throw new RuntimeException('Describe el trabajo realizado para finalizar.');
        }
        if ($nuevo === self::CANCELADO && mb_strlen($nota) < 5) {
            throw new RuntimeException('Indica el motivo de cancelación.');
        }
    }
}
