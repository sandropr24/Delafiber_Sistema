<?php

use App\Services\ProductoConfiguracionService;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProductoConfiguracionServiceTest extends CIUnitTestCase
{
    private function producto(int $id = 7, array $cambios = []): array
    {
        return array_replace([
            'idproducto' => $id, 'nombre' => 'Artículo de prueba', 'tipo' => 'PRODUCTO',
            'controlastock' => 1, 'controlaserie' => 0, 'idunidad' => 1, 'estado' => 1,
        ], $cambios);
    }

    /**
     * Sustituye solo el transporte SQL. Verifica las consultas, sus parámetros
     * y su orden sin conectarse a la base de datos de trabajo.
     */
    private function servicio(array $pasos, string $driver = 'MySQLi'): ProductoConfiguracionService
    {
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['query'])->getMock();
        $db->DBDriver = $driver;
        $indice = 0;
        $db->expects($this->exactly(count($pasos)))->method('query')
            ->willReturnCallback(function (string $sql, $parametros) use ($pasos, &$indice) {
                [$patron, $esperados, $filas] = $pasos[$indice++];
                $this->assertMatchesRegularExpression($patron, $sql);
                $this->assertSame($esperados, $parametros);
                $resultado = $this->createMock(ResultInterface::class);
                $resultado->method('getRowArray')->willReturn($filas[0] ?? null);
                $resultado->method('getResultArray')->willReturn($filas);
                return $resultado;
            });
        return new ProductoConfiguracionService($db);
    }

    private function bloqueo(int $id, ?array $producto): array
    {
        return ['/^SELECT \* FROM productos WHERE idproducto = \? FOR UPDATE$/', [$id], $producto ? [$producto] : []];
    }

    private function sinPendientes(): array
    {
        return [
            ['/FROM reservas_stock.*RESERVADO/s', [7], []],
            ['/FROM series.*RESERVADO/s', [7], []],
            ['/FROM detventa.*fechaanulacion IS NULL.*ENTREGADO/s', [7], []],
            ['/FROM venta_combo_componentes.*fechaanulacion IS NULL.*ENTREGADO/s', [7], []],
        ];
    }

    public function testPermiteDatosDescriptivosSinConsultarNiModificarReservas(): void
    {
        $actual = $this->producto();
        $servicio = $this->servicio([$this->bloqueo(7, $actual)]);
        $this->assertSame($actual, $servicio->validarCambio(7, [
            'nombre' => 'Otro nombre', 'descripcion' => 'Otra descripción', 'imagen' => 'nueva.jpg',
            'precioventa' => '150.00', 'estado' => 0,
        ]));
    }

    public function testNoConfundeCadenasDeFormularioConCambiosDeConfiguracion(): void
    {
        $actual = $this->producto();
        $servicio = $this->servicio([$this->bloqueo(7, $actual)]);
        $this->assertSame($actual, $servicio->validarCambio(7, [
            'tipo' => 'PRODUCTO', 'controlastock' => '1', 'controlaserie' => '0',
        ]));
    }

    #[DataProvider('cambiosCriticos')]
    public function testBloqueaCambiosCriticosSiHayUnaReserva(array $cambios): void
    {
        $servicio = $this->servicio([
            $this->bloqueo(7, $this->producto()),
            ['/FROM reservas_stock.*RESERVADO/s', [7], [['idreserva' => 1]]],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reservas de inventario pendientes');
        $servicio->validarCambio(7, $cambios);
    }

    public static function cambiosCriticos(): array
    {
        return [
            'producto a servicio' => [['tipo' => 'SERVICIO', 'controlastock' => 0]],
            'producto a combo' => [['tipo' => 'COMBO', 'controlastock' => 0]],
            'desactivar stock' => [['controlastock' => 0]],
            'activar series' => [['controlaserie' => 1]],
        ];
    }

    public function testBloqueaDesactivarSeriesReservadasAunqueNoHayaReservaDeStock(): void
    {
        $servicio = $this->servicio([
            $this->bloqueo(7, $this->producto(7, ['controlaserie' => 1])),
            ['/FROM reservas_stock/', [7], []],
            ['/FROM series.*RESERVADO/s', [7], [['idserie' => 2]]],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('números de serie reservados');
        $servicio->validarCambio(7, ['controlaserie' => 0]);
    }

    public function testBloqueaActivarStockDeUnArticuloVendidoSinStockYNoEntregado(): void
    {
        $servicio = $this->servicio([
            $this->bloqueo(7, $this->producto(7, ['controlastock' => 0])),
            ['/FROM reservas_stock/', [7], []],
            ['/FROM series/', [7], []],
            ['/FROM detventa.*fechaanulacion IS NULL.*ENTREGADO/s', [7], [['idventa' => 3]]],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ventas con entrega o resolución comercial pendiente');
        $servicio->validarCambio(7, ['controlastock' => 1]);
    }

    public function testConsultaLosComponentesHistoricosDeLaVentaNoElComboActual(): void
    {
        $pendientes = $this->sinPendientes();
        $pendientes[3][2] = [['idventa' => 3]];
        $servicio = $this->servicio([$this->bloqueo(7, $this->producto()), ...$pendientes]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ventas de combos');
        $servicio->validarCambio(7, ['controlastock' => 0]);
    }

    public function testPermiteCambiarConfiguracionSinOperacionesPendientes(): void
    {
        $actual = $this->producto();
        $servicio = $this->servicio([$this->bloqueo(7, $actual), ...$this->sinPendientes()]);
        $this->assertSame($actual, $servicio->validarCambio(7, ['controlastock' => 0]));
    }

    public function testComparaConLaConfiguracionActualBajoBloqueo(): void
    {
        // El formulario pudo abrirse antes de que otro administrador activara
        // las series; el servicio debe comparar contra el dato bloqueado.
        $servicio = $this->servicio([
            $this->bloqueo(7, $this->producto(7, ['controlaserie' => 1])),
            ['/FROM reservas_stock/', [7], [['idreserva' => 1]]],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $servicio->validarCambio(7, ['controlaserie' => 0]);
    }

    public function testRechazaProductoEliminadoAntesDeGuardar(): void
    {
        $servicio = $this->servicio([$this->bloqueo(7, null)]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ya no existe');
        $servicio->validarCambio(7, ['nombre' => 'Nuevo nombre']);
    }

    public function testVentaBloqueaEnOrdenLosArticulosYComponentesYDevuelveDatosActuales(): void
    {
        $servicio = $this->servicio([
            ['/SELECT DISTINCT idproducto FROM combo_detalle/', [2, 20], [['idproducto' => 9], ['idproducto' => 2]]],
            $this->bloqueo(2, $this->producto(2)),
            $this->bloqueo(9, $this->producto(9, ['idunidad' => 2, 'controlaserie' => 1])),
            $this->bloqueo(20, $this->producto(20, ['tipo' => 'COMBO', 'controlastock' => 0])),
            ['/FROM combo_detalle.*ORDER BY idproducto FOR UPDATE$/', [20], [
                ['idproducto' => 2, 'cantidad' => '1.00'], ['idproducto' => 9, 'cantidad' => '2.00'],
            ]],
            ['/FROM unidadesmedida WHERE idunidad IN/', [1, 2], [
                ['idunidad' => 1, 'abreviatura' => 'UND'], ['idunidad' => 2, 'abreviatura' => 'M'],
            ]],
        ]);
        $configuracion = $servicio->bloquearParaVenta([['idproducto' => 20], ['idproducto' => 2]]);
        $this->assertSame([2, 9, 20], array_keys($configuracion['productos']));
        $this->assertSame(1, $configuracion['productos'][9]['controlaserie']);
        $this->assertSame('M', $configuracion['productos'][9]['unidad']);
        $this->assertCount(2, $configuracion['componentes'][20]);
    }

    public function testVentaAbortaSiApareceUnComponenteNoBloqueado(): void
    {
        $servicio = $this->servicio([
            ['/SELECT DISTINCT/', [20], [['idproducto' => 7]]],
            $this->bloqueo(7, $this->producto()),
            $this->bloqueo(20, $this->producto(20, ['tipo' => 'COMBO'])),
            ['/FROM combo_detalle.*FOR UPDATE$/', [20], [['idproducto' => 8, 'cantidad' => '1.00']]],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('configuración del combo cambió');
        $servicio->bloquearParaVenta([['idproducto' => 20]]);
    }

    public function testVentaNoBloqueaDosVecesUnArticuloRepetido(): void
    {
        $servicio = $this->servicio([
            ['/SELECT DISTINCT/', [7], []],
            $this->bloqueo(7, $this->producto()),
            ['/FROM unidadesmedida/', [1], [['idunidad' => 1, 'abreviatura' => 'UND']]],
        ]);
        $this->assertCount(1, $servicio->bloquearParaVenta([['idproducto' => 7], ['idproducto' => '7']])['productos']);
    }

    #[DataProvider('detallesInvalidos')]
    public function testVentaRechazaArticulosInvalidosSinConsultarLaBase(array $detalles): void
    {
        $servicio = $this->servicio([]);
        $this->expectException(InvalidArgumentException::class);
        $servicio->bloquearParaVenta($detalles);
    }

    public static function detallesInvalidos(): array
    {
        return [
            [[]], [[null]], [[[]]], [[['idproducto' => 0]]],
            [[['idproducto' => -1]]], [[['idproducto' => 'invalido']]],
            [[['idproducto' => [7]]]],
        ];
    }

    public function testPermiteEjecutarLasConsultasConSQLiteSinForUpdate(): void
    {
        $actual = $this->producto();
        $servicio = $this->servicio([
            ['/^SELECT \* FROM productos WHERE idproducto = \?$/', [7], [$actual]],
        ], 'SQLite3');
        $this->assertSame($actual, $servicio->validarCambio(7, ['nombre' => 'Nombre editado']));
    }
}
