<?php

use App\Services\VoucherStorage;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class VoucherStorageTest extends CIUnitTestCase
{
    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voucher-test-' . bin2hex(random_bytes(8));
        mkdir($this->directorio, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directorio . '/*') ?: [] as $archivo) {
            if (is_file($archivo)) unlink($archivo);
        }
        rmdir($this->directorio);
        parent::tearDown();
    }

    private function foto(string $contenido): UploadedFile
    {
        $ruta = $this->directorio . '/entrada';
        file_put_contents($ruta, $contenido);
        $foto = $this->getMockBuilder(UploadedFile::class)->disableOriginalConstructor()->onlyMethods(['isValid', 'hasMoved', 'getTempName', 'move'])->getMock();
        $foto->method('isValid')->willReturn(true);
        $foto->method('hasMoved')->willReturn(false);
        $foto->method('getTempName')->willReturn($ruta);
        $foto->method('move')->willReturnCallback(fn($destino, $nombre) => copy($ruta, $destino . '/' . $nombre));
        return $foto;
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    }

    public function testGuardaConNombreAleatorioYSePuedeDescartarTrasRollback(): void
    {
        $storage = new VoucherStorage($this->directorio);
        $nombre = $storage->guardar($this->foto($this->png()));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}\.png$/', $nombre);
        $this->assertFileExists($storage->ruta($nombre));
        if (PHP_OS_FAMILY !== 'Windows') $this->assertSame(0600, fileperms($storage->ruta($nombre)) & 0777);
        $storage->descartar([$nombre]);
        $this->assertFileDoesNotExist($storage->ruta($nombre));
    }

    public function testNoConfiaEnLaExtensionDelClienteNiAceptaHtml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new VoucherStorage($this->directorio))->guardar($this->foto('<html><script>alert(1)</script></html>'));
    }

    public function testRechazaSvgAunqueSeaUnaImagen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new VoucherStorage($this->directorio))->guardar($this->foto('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'));
    }

    public function testRechazaArchivoMayorAlLimiteReal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new VoucherStorage($this->directorio))->guardar($this->foto($this->png() . str_repeat('x', VoucherStorage::MAX_BYTES)));
    }

    public function testExigeUnaSubidaValida(): void
    {
        $foto = $this->createMock(UploadedFile::class);
        $foto->method('isValid')->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        (new VoucherStorage($this->directorio))->validar($foto);
    }

    #[DataProvider('nombresInvalidos')]
    public function testNoResuelveRutasFueraDelDirectorio(string $nombre): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new VoucherStorage($this->directorio))->ruta($nombre);
    }

    public static function nombresInvalidos(): array
    {
        return [['../foto.jpg'], ['/etc/passwd'], ['https://example.com/foto.jpg'], ['foto.svg'], [str_repeat('a', 48) . '.php']];
    }
}
