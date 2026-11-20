<?php

namespace App\Libraries;

use InvalidArgumentException;

final class ArqueoCaja
{
    public static function calcular(array $metodos, array $pagos, $inicial, array $movimientos = []): array
    {
        $filas = [];
        foreach ($metodos as $metodo) {
            $id = (int) $metodo['idtipopago'];
            $filas[$id] = [
                'idtipopago' => $id, 'tipopago' => $metodo['tipopago'],
                'centimos' => CobroVenta::esEfectivo($metodo['tipopago']) ? CobroVenta::centimos($inicial) : 0,
            ];
        }
        foreach ($pagos as $pago) {
            $id = (int) $pago['idtipopago'];
            if (!isset($filas[$id])) throw new InvalidArgumentException('Hay un pago con medio desconocido.');
            // Se usa el monto aplicado: el recibido incluye dinero que vuelve
            // al cliente. Una venta anulada con reembolso pendiente sigue en caja.
            $filas[$id]['centimos'] += CobroVenta::centimos($pago['monto']);
        }
        foreach ($movimientos as $movimiento) {
            $id = (int) ($movimiento['idtipopago'] ?? 0);
            if (!isset($filas[$id])) throw new InvalidArgumentException('Hay un movimiento con medio desconocido.');
            $tipo = $movimiento['tipo'] ?? '';
            if (!in_array($tipo, ['INGRESO', 'EGRESO'], true)) throw new InvalidArgumentException('Hay un movimiento de caja inválido.');
            $centimos = CobroVenta::centimos($movimiento['monto'] ?? '');
            if ($centimos <= 0) throw new InvalidArgumentException('El movimiento de caja debe ser positivo.');
            $filas[$id]['centimos'] += $tipo === 'INGRESO' ? $centimos : -$centimos;
            if ($filas[$id]['centimos'] < 0) throw new InvalidArgumentException('Un egreso no puede superar el saldo esperado del medio de pago.');
        }
        foreach ($filas as &$fila) {
            if ($fila['centimos'] > 9999999999) throw new InvalidArgumentException('El total del medio de pago supera el rango de caja.');
            $fila['montoesperado'] = number_format($fila['centimos'] / 100, 2, '.', '');
        }
        unset($fila);
        return $filas;
    }

    public static function cierre(array $esperados, array $contados, string $observacion): array
    {
        $observacion = trim($observacion);
        if (mb_strlen($observacion) > 150) throw new InvalidArgumentException('La observación admite hasta 150 caracteres.');
        $filas = [];
        foreach ($esperados as $id => $esperado) {
            $contado = CobroVenta::centimos($contados[$id] ?? '');
            $diferencia = $contado - $esperado['centimos'];
            if ($diferencia !== 0 && mb_strlen($observacion) < 5) {
                throw new InvalidArgumentException('Explica las diferencias de arqueo (mínimo 5 caracteres).');
            }
            $filas[] = [
                'idtipopago' => $id, 'montoesperado' => $esperado['montoesperado'],
                'montocontado' => number_format($contado / 100, 2, '.', ''),
                'observacion' => $observacion ?: null,
            ];
        }
        return $filas;
    }
}
