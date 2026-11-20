<?php

use App\Models\PedidoModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PedidoDatosWebTest extends CIUnitTestCase
{
    private function resultado(array $filas): ResultInterface
    {
        $resultado = $this->createMock(ResultInterface::class);
        $resultado->method('getRowArray')->willReturn($filas[0] ?? null);
        $resultado->method('getResultArray')->willReturn($filas);
        return $resultado;
    }

    private function constructorConsulta(array $filas): BaseBuilder
    {
        $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()
            ->onlyMethods(['select', 'join', 'where', 'get', 'insert', 'update'])->getMock();
        foreach (['select', 'join', 'where'] as $metodo) {
            $builder->method($metodo)->willReturnSelf();
        }
        $builder->method('get')->willReturn($this->resultado($filas));
        return $builder;
    }

    private function web(): array
    {
        return [
            'modalidad' => 'DOMICILIO', 'direccion_entrega' => 'Avenida Original 123',
            'nombre_contacto' => 'Ana', 'email_contacto' => 'ana@example.com', 'telefono_contacto' => '999 999 999',
        ];
    }

    #[DataProvider('origenes')]
    public function testObtenerSoloConsultaDatosWebParaPedidosDeLaTienda(?string $origen): void
    {
        $pedido = $origen === null ? null : ['idpedido' => 17, 'tipopedido' => $origen];
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['query', 'table'])->getMock();
        $db->expects($this->once())->method('table')->with('pedido p')
            ->willReturn($this->constructorConsulta($pedido ? [$pedido] : []));
        if ($origen === 'TIENDA_WEB') {
            $db->expects($this->once())->method('query')->with(
                'SELECT modalidad, direccion_entrega, nombre_contacto, email_contacto, telefono_contacto FROM pedido_web WHERE idpedido = ?',
                [17]
            )->willReturn($this->resultado([$this->web()]));
        } else {
            $db->expects($this->never())->method('query');
        }
        $obtenido = (new PedidoModel($db))->obtener(17);
        if ($origen === null) {
            $this->assertNull($obtenido);
        } else {
            $this->assertSame($origen === 'TIENDA_WEB' ? $this->web() : null, $obtenido['datos_web']);
        }
    }

    public static function origenes(): array
    {
        return [['DIRECTO'], ['COTIZACION'], ['TIENDA_WEB'], [null]];
    }

    public function testLosAjustesDeEntregaSeGuardanEnElHistorialSinEscribirEnPedidoWeb(): void
    {
        $pedido = [
            'idpedido' => 17, 'idpersona' => 1, 'idlocal' => 1, 'tipopedido' => 'TIENDA_WEB',
            'tipodescuento' => 'NINGUNO', 'valordescuento' => '0', 'motivodescuento' => null,
            'subtotal' => '100.00', 'descuento' => '0.00', 'total' => '100.00',
        ];
        $venta = $pedido + ['idcliente' => 1];
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['table', 'query'])->getMock();
        $evento = null;
        $actualizacion = null;
        $db->expects($this->once())->method('query')->with(
            'SELECT modalidad, direccion_entrega, nombre_contacto, email_contacto, telefono_contacto FROM pedido_web WHERE idpedido = ?',
            [17]
        )->willReturn($this->resultado([$this->web()]));
        $db->method('table')->willReturnCallback(function (string $tabla) use ($pedido, $venta, &$evento, &$actualizacion) {
            $filas = match ($tabla) {
                'pedido' => [$pedido], 'ventas' => [$venta], 'detventa d' => [],
                'entregas' => [['tipoentrega' => 'DOMICILIO', 'direccion' => 'Dirección revisada 456', 'telefono' => '988888888']],
                'historial_pedido' => [],
                default => throw new LogicException('No debe escribir ni consultar esta tabla: ' . $tabla),
            };
            $builder = $this->constructorConsulta($filas);
            if ($tabla === 'historial_pedido') {
                $builder->expects($this->once())->method('insert')->willReturnCallback(function ($datos) use (&$evento) {
                    $evento = $datos;
                    return true;
                });
            } elseif ($tabla === 'pedido') {
                $builder->method('update')->willReturnCallback(function ($datos) use (&$actualizacion) {
                    $actualizacion = $datos;
                    return true;
                });
            }
            return $builder;
        });
        $modelo = $this->getMockBuilder(PedidoModel::class)->setConstructorArgs([$db])
            ->onlyMethods(['detalles', 'historial', 'estadoId'])->getMock();
        $modelo->method('detalles')->willReturn([]);
        $modelo->method('estadoId')->with('CONVERTIDO')->willReturn(3);
        $modelo->expects($this->once())->method('historial')->with(17, 9, 'CONVERSION', 'CONFIRMADO', 'CONVERTIDO', 'Venta N.º 22.');
        $modelo->marcarConvertido(17, 22, 9);

        $this->assertSame('AJUSTES_VENTA', $evento['evento']);
        $cambios = json_decode($evento['cambios'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Dirección de entrega', 'Teléfono de entrega'], array_column($cambios, 'campo'));
        $this->assertSame('Avenida Original 123', $cambios[0]['antes']);
        $this->assertSame('Dirección revisada 456', $cambios[0]['despues']);
        $this->assertSame(3, $actualizacion['idestado']);
        $this->assertArrayNotHasKey('direccion_entrega', $actualizacion);
        $this->assertArrayNotHasKey('telefono_contacto', $actualizacion);
    }
}
