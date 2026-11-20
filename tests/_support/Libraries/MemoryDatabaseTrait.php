<?php

namespace Tests\Support\Libraries;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;

/** Transporte SQL simulado para reglas de identidad; nunca abre una conexión. */
trait MemoryDatabaseTrait
{
    protected function memoryDatabase(array $tablas): array
    {
        $estado = (object) ['tablas' => $tablas, 'id' => 0, 'snapshot' => null, 'consultas' => []];
        $resultado = function (array $filas) {
            $r = $this->createMock(ResultInterface::class);
            $r->method('getRowArray')->willReturn($filas[0] ?? null);
            $r->method('getResultArray')->willReturn($filas);
            return $r;
        };
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'table', 'query', 'connect', 'insertID', 'transBegin', 'transCommit', 'transRollback', 'transStatus',
        ])->getMock();
        $db->DBDriver = 'MySQLi';
        $db->expects($this->never())->method('connect');
        $db->method('insertID')->willReturnCallback(fn() => $estado->id);
        $db->method('transBegin')->willReturnCallback(function () use ($estado) { $estado->snapshot = $estado->tablas; return true; });
        $db->method('transCommit')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->method('transRollback')->willReturnCallback(function () use ($estado) {
            if ($estado->snapshot !== null) $estado->tablas = $estado->snapshot;
            return true;
        });
        $db->method('query')->willReturnCallback(function ($sql, $params = []) use ($estado, $resultado) {
            $estado->consultas[] = [$sql, $params];
            if (str_contains($sql, 'verificacion_token_hash = ?')) {
                return $resultado(array_values(array_filter($estado->tablas['cliente_cuentas'], fn($c) => ($c['verificacion_token_hash'] ?? null) === $params[0])));
            }
            throw new \LogicException('SQL no previsto por la prueba.');
        });
        $db->method('table')->willReturnCallback(function ($tabla) use ($estado, $resultado) {
            $nombre = explode(' ', $tabla)[0];
            if (!array_key_exists($nombre, $estado->tablas)) throw new \LogicException('Tabla no prevista: ' . $nombre);
            $f = (object) ['where' => [], 'in' => []];
            $b = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                'select', 'join', 'where', 'whereIn', 'orderBy', 'get', 'countAllResults', 'insert', 'update',
            ])->getMock();
            foreach (['select', 'join', 'orderBy'] as $m) $b->method($m)->willReturnSelf();
            $b->method('where')->willReturnCallback(function ($key, $valor = null) use ($b, $f) {
                $f->where[preg_replace('/^[a-z]+\./', '', $key)] = $valor;
                return $b;
            });
            $b->method('whereIn')->willReturnCallback(function ($key, $valores) use ($b, $f) {
                $f->in[preg_replace('/^[a-z]+\./', '', $key)] = $valores;
                return $b;
            });
            $coincide = static function ($fila) use ($f) {
                foreach ($f->where as $k => $v) {
                    if (str_ends_with($k, ' IS NOT NULL')) {
                        if (($fila[substr($k, 0, -12)] ?? null) === null) return false;
                    } elseif (($fila[$k] ?? null) != $v) return false;
                }
                foreach ($f->in as $k => $v) if (!in_array($fila[$k] ?? null, $v)) return false;
                return true;
            };
            $filas = function () use ($estado, $nombre, $coincide) {
                $filas = $estado->tablas[$nombre];
                if ($nombre === 'pedido') {
                    foreach ($filas as &$fila) foreach ($estado->tablas['pedido_web'] ?? [] as $pw) if ($pw['idpedido'] === $fila['idpedido']) $fila += $pw;
                    unset($fila);
                }
                return array_values(array_filter($filas, $coincide));
            };
            $b->method('get')->willReturnCallback(fn() => $resultado($filas()));
            $b->method('countAllResults')->willReturnCallback(fn() => count($filas()));
            $b->method('insert')->willReturnCallback(function ($datos) use ($estado, $nombre) {
                $clave = ['personas' => 'idpersona', 'cliente_cuentas' => 'idcliente_cuenta', 'pedido' => 'idpedido',
                    'pedido_web' => 'idpedido_web', 'detpedido' => 'iddetpedido', 'historial_pedido' => 'idhistorial'][$nombre] ?? 'id';
                $estado->id = max([0, ...array_column($estado->tablas[$nombre], $clave)]) + 1;
                $datos[$clave] = $estado->id;
                $estado->tablas[$nombre][] = $datos;
                return true;
            });
            $b->method('update')->willReturnCallback(function ($datos) use ($estado, $nombre, $coincide) {
                foreach ($estado->tablas[$nombre] as &$fila) if ($coincide($fila)) $fila = array_replace($fila, $datos);
                unset($fila);
                return true;
            });
            return $b;
        });
        return [$db, $estado];
    }
}
