<?php

use App\Controllers\Admin\CajaController;
use App\Services\VoucherStorage;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use PHPUnit\Framework\Attributes\DataProvider;

final class CajaVoucherControllerTest extends CIUnitTestCase
{
    private array $conexiones;
    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conexiones = Database::getConnections();
        $this->directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consulta-voucher-' . bin2hex(random_bytes(8));
        mkdir($this->directorio, 0700);
        session()->set('idusuario', 7);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, $this->conexiones);
        foreach (glob($this->directorio . '/*') ?: [] as $archivo) unlink($archivo);
        rmdir($this->directorio);
        session()->remove('idusuario');
        parent::tearDown();
    }

    #[DataProvider('accesos')]
    public function testArchivoPrivadoSoloParaAdministradorOUsuarioQueCobro(string $rol, int $responsable, bool $permitido): void
    {
        $nombre = str_repeat('a', 48) . '.png';
        $storage = new VoucherStorage($this->directorio);
        file_put_contents($storage->ruta($nombre), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $db = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods(['table', 'query', 'connect'])->getMock();
        $db->expects($this->never())->method('connect');
        $resultado = $this->createMock(ResultInterface::class);
        $resultado->method('getRowArray')->willReturn(['idusuario' => 7, 'rol' => $rol]);
        $db->method('query')->willReturn($resultado);
        $pago = $this->createMock(ResultInterface::class);
        $pago->method('getRowArray')->willReturn(['voucher' => $nombre, 'idusuariocobro' => $responsable]);
        $builder = $this->getMockBuilder(BaseBuilder::class)->disableOriginalConstructor()->onlyMethods(['select', 'where', 'get'])->getMock();
        $builder->method('select')->willReturnSelf();
        $builder->method('where')->willReturnSelf();
        $builder->method('get')->willReturn($pago);
        $db->method('table')->willReturn($builder);
        (new ReflectionProperty(Database::class, 'instances'))->setValue(null, ['tests' => $db]);
        $controller = $this->getMockBuilder(CajaController::class)->onlyMethods(['almacenVouchers'])->getMock();
        $controller->expects($permitido ? $this->once() : $this->never())->method('almacenVouchers')->willReturn($storage);
        $controller->initController($this->createMock(IncomingRequest::class), service('response'), service('logger'));
        if (!$permitido) $this->expectException(PageNotFoundException::class);
        $response = $controller->voucher(42);
        $response->buildHeaders();
        $this->assertStringStartsWith('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('inline', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertStringNotContainsString($this->directorio, $response->getHeaderLine('Content-Disposition'));
    }

    public static function accesos(): array
    {
        return [['Vendedor', 7, true], ['Administrador', 99, true], ['Vendedor', 99, false]];
    }
}
