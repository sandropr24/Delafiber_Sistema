<?php

use App\Services\GarantiaConsultaService;
use App\Services\GarantiaService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class GarantiaServiceTest extends CIUnitTestCase
{
    private function entorno(): array
    {
        $estado = (object) [
            'rol' => 'Administrador', 'activo' => true, 'entregada' => true, 'duplicado' => false,
            'fallar' => '', 'commit' => 0, 'rollback' => 0, 'casos' => [], 'historial' => [], 'snapshot' => [],
            'venta' => ['idventa' => 10, 'fecha' => date('Y-m-d') . ' 10:00:00', 'fechaanulacion' => null],
            'articulo' => ['iddetventa' => 20, 'idcomponenteventa' => null, 'idproducto' => 30, 'producto' => 'Cámara vendida', 'tipo' => 'PRODUCTO', 'controlaserie' => 1, 'garantiameses' => 12],
            'serie' => ['idserie' => 40, 'iddetventa' => 20, 'idproducto' => 30, 'numeroserie' => 'CAM-001', 'estado' => 'VENDIDO'],
        ];
        $resultado = function (array $filas): ResultInterface {
            $r = $this->createMock(ResultInterface::class);
            $r->method('getRowArray')->willReturn($filas[0] ?? null);
            $r->method('getResultArray')->willReturn($filas);
            return $r;
        };
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([
            'query', 'table', 'connect', 'transBegin', 'transCommit', 'transRollback', 'transStatus', 'insertID',
        ])->getMock();
        $db->DBDriver = 'MySQLi';
        $db->expects($this->never())->method('connect');
        $db->method('transBegin')->willReturnCallback(function () use ($estado) {
            $estado->snapshot = [$estado->casos, $estado->historial];
            return true;
        });
        $db->method('transStatus')->willReturn(true);
        $db->method('transCommit')->willReturnCallback(function () use ($estado) { $estado->commit++; return true; });
        $db->method('transRollback')->willReturnCallback(function () use ($estado) {
            [$estado->casos, $estado->historial] = $estado->snapshot;
            $estado->rollback++;
            return true;
        });
        $db->method('insertID')->willReturn(1);
        $db->method('query')->willReturnCallback(function ($sql, $params = []) use ($estado, $resultado) {
            $this->assertStringEndsWith('FOR UPDATE', $sql);
            if (str_starts_with($sql, 'SELECT u.idusuario')) return $resultado($estado->activo ? [['idusuario' => $params[0], 'rol' => $estado->rol]] : []);
            if (str_starts_with($sql, 'SELECT v.* FROM ventas')) return $resultado($estado->venta ? [$estado->venta] : []);
            if (str_starts_with($sql, 'SELECT * FROM series')) return $resultado($estado->serie ? [$estado->serie] : []);
            if (str_starts_with($sql, 'SELECT * FROM garantias')) return $resultado(isset($estado->casos[$params[0]]) ? [$estado->casos[$params[0]]] : []);
            throw new LogicException('Consulta inesperada: ' . $sql);
        });
        $db->method('table')->willReturnCallback(function ($tabla) use ($estado, $resultado) {
            $this->assertContains($tabla, ['garantias', 'garantia_historial', 'entregas', 'series', 'proveedores']);
            $filtros = [];
            $b = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods([
                'where', 'orWhere', 'groupStart', 'groupEnd', 'get', 'countAllResults', 'insert', 'update', 'delete',
            ])->getMock();
            foreach (['orWhere', 'groupStart', 'groupEnd'] as $metodo) $b->method($metodo)->willReturnSelf();
            $b->method('where')->willReturnCallback(function ($campo, $valor) use (&$filtros, $b) { $filtros[$campo] = $valor; return $b; });
            $b->expects($this->never())->method('delete');
            $b->method('get')->willReturnCallback(function () use ($tabla, &$filtros, $estado, $resultado) {
                $this->assertSame('garantias', $tabla);
                return $resultado(array_values(array_filter($estado->casos, fn($c) => $c['request_token'] === $filtros['request_token'])));
            });
            $b->method('countAllResults')->willReturnCallback(function () use ($tabla, $estado, &$filtros) {
                return match ($tabla) {
                    'entregas' => $estado->entregada ? 1 : 0,
                    'series' => $estado->serie ? 1 : 0,
                    'garantias' => $estado->duplicado ? 1 : 0,
                    'proveedores' => ($filtros['idproveedor'] ?? 0) === 11 ? 1 : 0,
                    default => throw new LogicException('Conteo no previsto: ' . $tabla),
                };
            });
            $b->method('insert')->willReturnCallback(function ($fila) use ($tabla, $estado) {
                $this->assertContains($tabla, ['garantias', 'garantia_historial']);
                if ($estado->fallar === $tabla) return false;
                if ($tabla === 'garantias') $estado->casos[1] = $fila + ['idgarantia' => 1];
                else $estado->historial[] = $fila;
                return true;
            });
            $b->method('update')->willReturnCallback(function ($fila) use ($tabla, $estado, &$filtros) {
                $this->assertSame('garantias', $tabla);
                $estado->casos[$filtros['idgarantia']] = array_merge($estado->casos[$filtros['idgarantia']], $fila);
                return true;
            });
            return $b;
        });
        $consultas = $this->getMockBuilder(GarantiaConsultaService::class)->disableOriginalConstructor()->onlyMethods(['articulo'])->getMock();
        $consultas->method('articulo')->willReturnCallback(fn($detalle, $componente) => $detalle === 20 && $componente === $estado->articulo['idcomponenteventa'] ? $estado->articulo : null);
        $datos = ['iddetventa' => 20, 'idserie' => 40, 'idcomponenteventa' => null, 'falla' => 'No enciende el equipo.', 'observacion' => 'Equipo recibido con accesorios.', 'request_token' => str_repeat('a', 64)];
        return [new GarantiaService($db, $consultas), $estado, $datos];
    }

    public function testRecepcionGuardaEquipoPlazoYEventoSinModificarInventario(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $this->assertSame(1, $s->registrar($datos, 7));
        $this->assertSame('CAM-001', $e->casos[1]['serie_snapshot']);
        $this->assertSame(12, $e->casos[1]['garantiameses']);
        $this->assertSame('RECIBIDO', $e->historial[0]['estado']);
        $this->assertSame(7, $e->historial[0]['idusuario']);
        $this->assertSame(1, $e->commit);
    }

    public function testComponenteUsaSuPropioPlazoYNoElDelCombo(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $e->articulo['idcomponenteventa'] = $datos['idcomponenteventa'] = 55;
        $e->articulo['garantiameses'] = 6;
        $s->registrar($datos, 7);
        $this->assertSame(55, $e->casos[1]['idcomponenteventa']);
        $this->assertSame(6, $e->casos[1]['garantiameses']);
    }

    public function testAdministradorAcreditaPlazoHistoricoSinReescribirVenta(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $e->articulo['garantiameses'] = null;
        $datos['meses_verificados'] = '6';
        $datos['observacion'] = 'Verificado en comprobante original NV01-01.';
        $s->registrar($datos, 7);
        $this->assertSame(1, $e->casos[1]['plazo_verificado']);
        $this->assertSame(6, $e->casos[1]['garantiameses']);
        $this->assertNull($e->articulo['garantiameses']);
    }

    public function testReintentoDelMismoFormularioNoDuplicaCasoNiHistorial(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $s->registrar($datos, 7);
        $e->duplicado = true;
        $this->assertSame(1, $s->registrar($datos, 7));
        $this->assertCount(1, $e->casos);
        $this->assertCount(1, $e->historial);
        $datos['idserie'] = 41;
        $this->expectException(InvalidArgumentException::class);
        $s->registrar($datos, 7);
    }

    public static function rechazos(): array
    {
        return array_map(static fn($caso) => [$caso], [
            'vendedor', 'inactivo', 'anulada', 'pendiente', 'serie_ajena', 'serie_reservada', 'serie_omitida',
            'duplicado', 'vencida', 'sin_garantia', 'historico_operador', 'historico_sin_evidencia',
            'componente_ajeno', 'servicio', 'token', 'falla_array', 'plazo_manipulado',
        ]);
    }

    #[DataProvider('rechazos')]
    public function testRecepcionRechazaDatosYAccesosInvalidos(string $caso): void
    {
        [$s, $e, $datos] = $this->entorno();
        switch ($caso) {
            case 'vendedor': $e->rol = 'Vendedor'; break;
            case 'inactivo': $e->activo = false; break;
            case 'anulada': $e->venta['fechaanulacion'] = date('Y-m-d H:i:s'); break;
            case 'pendiente': $e->entregada = false; break;
            case 'serie_ajena': $e->serie['idproducto'] = 99; break;
            case 'serie_reservada': $e->serie['estado'] = 'RESERVADO'; break;
            case 'serie_omitida': $datos['idserie'] = ''; break;
            case 'duplicado': $e->duplicado = true; break;
            case 'vencida': $e->venta['fecha'] = '2000-01-01'; break;
            case 'sin_garantia': $e->articulo['garantiameses'] = 0; break;
            case 'historico_operador': $e->rol = 'Operador'; $e->articulo['garantiameses'] = null; $datos['meses_verificados'] = 12; break;
            case 'historico_sin_evidencia': $e->articulo['garantiameses'] = null; $datos['meses_verificados'] = 12; $datos['observacion'] = ''; break;
            case 'componente_ajeno': $datos['idcomponenteventa'] = 999; break;
            case 'servicio': $e->articulo['tipo'] = 'SERVICIO'; break;
            case 'token': $datos['request_token'] = 'invalido'; break;
            case 'falla_array': $datos['falla'] = []; break;
            case 'plazo_manipulado': $e->articulo['garantiameses'] = 0; $datos['meses_verificados'] = 12; break;
        }
        try {
            $s->registrar($datos, 7);
            $this->fail('La recepción debía rechazarse.');
        } catch (InvalidArgumentException) {
            $this->assertSame([], $e->casos);
            $this->assertSame([], $e->historial);
            $this->assertSame(0, $e->commit);
        }
    }

    public function testArticuloSinSerieTambienAdmiteRecepcion(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $e->articulo['controlaserie'] = 0;
        $e->serie = null;
        $datos['idserie'] = null;
        $s->registrar($datos, 7);
        $this->assertNull($e->casos[1]['idserie']);
    }

    public function testErrorEnHistorialRevierteLaRecepcion(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $e->fallar = 'garantia_historial';
        try { $s->registrar($datos, 7); $this->fail('Debía revertirse.'); }
        catch (RuntimeException) {
            $this->assertSame([], $e->casos);
            $this->assertSame(1, $e->rollback);
        }
    }

    public function testSeguimientoConservaResponsablesProveedorYEventos(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $s->registrar($datos, 7);
        $e->rol = 'Operador';
        $s->cambiarEstado(1, 'RECIBIDO', 'ENVIADO_PROVEEDOR', 'Se envía para diagnóstico.', 11, 8);
        $s->cambiarEstado(1, 'ENVIADO_PROVEEDOR', 'REPARADO', 'Proveedor reparó la placa.', null, 8);
        $s->cambiarEstado(1, 'REPARADO', 'ENTREGADO_CLIENTE', 'Cliente recibe conforme.', null, 7);
        $this->assertSame('ENTREGADO_CLIENTE', $e->casos[1]['estado']);
        $this->assertSame([null, 11, 11, 11], array_column($e->historial, 'idproveedor'));
        $this->assertSame([7, 8, 8, 7], array_column($e->historial, 'idusuario'));
        $this->assertSame('Equipo recibido con accesorios.', $e->casos[1]['observacion']);
        $this->expectException(InvalidArgumentException::class);
        $s->cambiarEstado(1, 'ENTREGADO_CLIENTE', 'RECIBIDO', 'Intento de reapertura.', null, 7);
    }

    public function testRechazaPantallaDesactualizadaYProveedorInexistente(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $s->registrar($datos, 7);
        foreach ([['ENVIADO_PROVEEDOR', 'REPARADO', null], ['RECIBIDO', 'ENVIADO_PROVEEDOR', 999], ['RECIBIDO', 'ENVIADO_PROVEEDOR', null]] as [$actual, $nuevo, $proveedor]) {
            try { $s->cambiarEstado(1, $actual, $nuevo, 'Cambio de prueba.', $proveedor, 7); $this->fail('Debía rechazarse.'); }
            catch (InvalidArgumentException) {
                $this->assertSame('RECIBIDO', $e->casos[1]['estado']);
                $this->assertCount(1, $e->historial);
            }
        }
    }

    public function testErrorEnEventoRevierteElCambioDeEstado(): void
    {
        [$s, $e, $datos] = $this->entorno();
        $s->registrar($datos, 7);
        $e->fallar = 'garantia_historial';
        try { $s->cambiarEstado(1, 'RECIBIDO', 'REEMPLAZADO', 'Equipo de reemplazo serie NUEVA.', null, 7); $this->fail('Debía revertirse.'); }
        catch (RuntimeException) {
            $this->assertSame('RECIBIDO', $e->casos[1]['estado']);
            $this->assertCount(1, $e->historial);
        }
    }
}
