<?php

use App\Services\TiendaCarritoService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class TiendaCarritoAgregarTest extends CIUnitTestCase
{
    private function servicio(): TiendaCarritoService
    {
        $filas = [
            ['idproducto' => 1, 'tipo' => 'PRODUCTO', 'nombre' => 'Router', 'precioventa' => '120.50', 'imagen' => null, 'controlaserie' => 1, 'unidad' => 'UND'],
            ['idproducto' => 2, 'tipo' => 'PRODUCTO', 'nombre' => 'Cable', 'precioventa' => '2.50', 'imagen' => null, 'controlaserie' => 0, 'unidad' => 'M'],
            ['idproducto' => 3, 'tipo' => 'SERVICIO', 'nombre' => 'Instalación', 'precioventa' => '80.00', 'imagen' => null, 'controlaserie' => 0, 'unidad' => 'UND'],
            ['idproducto' => 4, 'tipo' => 'PRODUCTO', 'nombre' => 'Equipo seriado', 'precioventa' => '10.00', 'imagen' => null, 'controlaserie' => 1, 'unidad' => 'M'],
        ];
        $ids = [];
        $resultado = $this->createMock(ResultInterface::class);
        // La captura por referencia conserva los identificadores de whereIn.
        $resultado->method('getResultArray')->willReturnCallback(static function () use ($filas, &$ids) {
            return array_values(array_filter($filas, static fn($fila) => in_array($fila['idproducto'], $ids, true)));
        });
        $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()
            ->onlyMethods(['select', 'join', 'where', 'whereIn', 'get'])->getMock();
        foreach (['select', 'join', 'where'] as $metodo) $builder->method($metodo)->willReturnSelf();
        $builder->method('whereIn')->willReturnCallback(static function ($campo, $valores) use (&$ids, $builder) {
            $ids = $valores;
            return $builder;
        });
        $builder->method('get')->willReturn($resultado);
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['table', 'query', 'connect'])->getMock();
        $db->method('table')->with('productos p')->willReturn($builder);
        $db->expects($this->never())->method('query');
        $db->expects($this->never())->method('connect');
        return new TiendaCarritoService($db);
    }

    public function testAgregadosSucesivosAcumulanCantidadYRecalculanElPrecioDelServidor(): void
    {
        $servicio = $this->servicio();
        $primero = $servicio->agregar([], 1, '1');
        $segundo = $servicio->agregar($primero['carrito'], 1, '1');
        $this->assertSame([1 => '2.00'], $segundo['carrito']);
        $this->assertSame('241.00', $segundo['resumen']['total']);
        $this->assertSame(1, $segundo['resumen']['cantidad']);
        $this->assertSame('1.00', $segundo['cantidad_agregada']);
    }

    public function testMetrosAdmiteDecimalesYElContadorRepresentaArticulosDistintos(): void
    {
        $resultado = $this->servicio()->agregar([1 => '2.00'], 2, '1.50');
        $this->assertSame([1 => '2.00', 2 => '1.50'], $resultado['carrito']);
        $this->assertSame('244.75', $resultado['resumen']['total']);
        $this->assertSame(2, $resultado['resumen']['cantidad']);
    }

    public function testRetiraSoloLosArticulosNoDisponiblesTrasUnAgregadoValido(): void
    {
        $resultado = $this->servicio()->agregar([99 => '1.00'], 3, '1');
        $this->assertSame([3 => '1.00'], $resultado['carrito']);
        $this->assertSame(1, $resultado['retirados']);
        $this->assertSame('80.00', $resultado['resumen']['total']);
    }

    #[DataProvider('agregadosInvalidos')]
    public function testUnErrorNoModificaElCarritoOriginal(int $id, $cantidad, array $carrito = []): void
    {
        $original = $carrito;
        try {
            $this->servicio()->agregar($carrito, $id, $cantidad);
            $this->fail('Debió rechazar el agregado.');
        } catch (InvalidArgumentException) {
            $this->assertSame($original, $carrito);
        }
    }

    public static function agregadosInvalidos(): array
    {
        return [
            'id inválido' => [0, '1'],
            'artículo oculto' => [99, '1', [1 => '1.00']],
            'cero' => [1, '0'],
            'negativo' => [1, '-1'],
            'array' => [1, ['1']],
            'fracción de equipo' => [1, '0.50'],
            'fracción de servicio' => [3, '0.50'],
            'fracción con serie' => [4, '0.50'],
            'exceso acumulado' => [1, '1', [1 => '9999.00']],
            'demasiados decimales' => [2, '0.001'],
        ];
    }
}
