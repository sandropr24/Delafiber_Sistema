<?php

use App\Models\PersonaModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PersonaModelTest extends CIUnitTestCase
{
    private PersonaModel $personas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personas = new PersonaModel();
    }

    private function natural(array $cambios = []): array
    {
        return array_replace([
            'tipodoc' => 'DNI', 'numerodoc' => '01234567',
            'nombres' => 'Ana María', 'apellidos' => 'Pérez',
        ], $cambios);
    }

    private function empresa(array $cambios = []): array
    {
        return array_replace([
            'tipodoc' => 'RUC', 'numerodoc' => '20123456789',
            'razonsocial' => 'Redes 123 SAC',
        ], $cambios);
    }

    private function validar(array $entrada, bool $exigirApellidos = true): bool
    {
        $datos = $this->personas->datosCliente($entrada);
        return service('validation')->reset()
            ->setRules($this->personas->reglasCliente((string) $datos['tipodoc'], $exigirApellidos))
            ->run($datos);
    }

    public function testNormalizaRucSinExigirNombresNiApellidos(): void
    {
        $datos = $this->personas->datosCliente($this->empresa([
            'razonsocial' => '  Redes 123 SAC  ', 'nombres' => 'Ignorar', 'apellidos' => 'Ignorar',
        ]));
        $this->assertSame('JURIDICA', $datos['tipopersona']);
        $this->assertSame('Redes 123 SAC', $datos['razonsocial']);
        $this->assertNull($datos['nombres']);
        $this->assertNull($datos['apellidos']);
        $this->assertTrue($this->validar($datos));
    }

    public function testAdmiteFormularioAnteriorYRegistroRapidoConRuc(): void
    {
        $datos = $this->personas->datosCliente([
            'tipodoc' => 'RUC', 'numerodoc' => '20123456789', 'nombres' => 'Empresa 2026 SAC',
        ]);
        $this->assertSame('Empresa 2026 SAC', $datos['razonsocial']);
        $this->assertNull($datos['nombres']);
        $this->assertTrue($this->validar($datos, false));
    }

    public function testNormalizaPersonaNaturalYDescartaCamposNoEditables(): void
    {
        $datos = $this->personas->datosCliente($this->natural([
            'nombres' => '  Ana María  ', 'razonsocial' => 'Empresa anterior',
            'tipopersona' => 'JURIDICA', 'idpersona' => 999, 'estado' => 0,
        ]));
        $this->assertSame('NATURAL', $datos['tipopersona']);
        $this->assertSame('Ana María', $datos['nombres']);
        $this->assertSame('01234567', $datos['numerodoc']);
        $this->assertNull($datos['razonsocial']);
        $this->assertArrayNotHasKey('idpersona', $datos);
        $this->assertArrayNotHasKey('estado', $datos);
        $this->assertTrue($this->validar($datos));
    }

    public function testApellidosObligatoriosEnDirectorioPeroOpcionalesEnRegistroRapido(): void
    {
        $entrada = $this->natural(['apellidos' => '']);
        $this->assertFalse($this->validar($entrada));
        $this->assertTrue($this->validar($entrada, false));
    }

    public function testRechazaClientesSinNombreORazonSocial(): void
    {
        $this->assertFalse($this->validar($this->natural(['nombres' => ' '])));
        $this->assertFalse($this->validar($this->empresa(['razonsocial' => ' '])));
    }

    #[DataProvider('documentos')]
    public function testValidaDocumentoSegunSuTipo(string $tipo, string $numero, bool $valido): void
    {
        $entrada = $tipo === 'RUC' ? $this->empresa() : $this->natural();
        $this->assertSame($valido, $this->validar(array_replace($entrada, [
            'tipodoc' => $tipo, 'numerodoc' => $numero,
        ])));
    }

    public static function documentos(): array
    {
        return [
            'DNI válido' => ['DNI', '01234567', true],
            'DNI corto' => ['DNI', '1234567', false],
            'DNI con letras' => ['DNI', 'A1234567', false],
            'RUC válido' => ['RUC', '20123456789', true],
            'RUC corto' => ['RUC', '2012345678', false],
            'RUC largo' => ['RUC', '201234567890', false],
            'RUC con letras' => ['RUC', 'A0123456789', false],
            'CE alfanumérico' => ['CE', 'CE1234567', true],
            'Pasaporte' => ['PASAPORTE', 'AB1234567', true],
            'Extranjero largo' => ['PASAPORTE', str_repeat('A', 21), false],
            'Tipo desconocido' => ['OTRO', '12345678', false],
        ];
    }

    #[DataProvider('camposFueraDeLimite')]
    public function testRespetaLimitesDeLaTabla(string $campo, string $valor, bool $juridica): void
    {
        $entrada = $juridica ? $this->empresa() : $this->natural();
        $this->assertFalse($this->validar(array_replace($entrada, [$campo => $valor])));
    }

    public static function camposFueraDeLimite(): array
    {
        return [
            ['nombres', str_repeat('A', 101), false],
            ['apellidos', str_repeat('A', 101), false],
            ['razonsocial', str_repeat('A', 151), true],
            ['direccion', str_repeat('A', 151), true],
            ['telefono', str_repeat('1', 21), false],
            ['email', str_repeat('a', 89) . '@example.com', false],
            ['email', 'correo-invalido', true],
        ];
    }

    public function testAdmiteLimitesMaximosSinTruncar(): void
    {
        $entrada = $this->empresa([
            'razonsocial' => str_repeat('Á', 150), 'direccion' => str_repeat('D', 150),
            'telefono' => str_repeat('1', 20),
        ]);
        $this->assertTrue($this->validar($entrada));
        $this->assertSame($entrada['razonsocial'], $this->personas->datosCliente($entrada)['razonsocial']);
    }

    public function testRechazaValoresDeFormularioNoEscalares(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->personas->datosCliente($this->empresa(['razonsocial' => ['invalida']]));
    }

    public function testMuestraRazonSocialYNombresDeRegistrosAnteriores(): void
    {
        $this->assertSame('Redes 123 SAC', PersonaModel::nombreCliente($this->empresa()));
        $this->assertSame('Ana María Pérez', PersonaModel::nombreCliente($this->natural()));
        $this->assertSame('Empresa anterior', PersonaModel::nombreCliente([
            'tipodoc' => 'RUC', 'nombres' => 'Empresa anterior', 'razonsocial' => null,
        ]));
    }

    public function testModeloConservaCamposJuridicosAlInsertar(): void
    {
        // Solo se sustituye la escritura SQL; se ejecutan la validación y la
        // protección de campos reales de CodeIgniter, sin una base de datos.
        $modelo = $this->getMockBuilder(PersonaModel::class)->onlyMethods(['doInsert'])->getMock();
        $datos = $modelo->datosCliente($this->empresa()) + ['estado' => 1];
        $modelo->expects($this->once())->method('doInsert')->with($datos)->willReturn(true);
        $this->assertTrue($modelo->insert($datos, false));
    }

    public function testModeloSigueAdmitiendoPersonasDeUsuarios(): void
    {
        $modelo = $this->getMockBuilder(PersonaModel::class)->onlyMethods(['doInsert'])->getMock();
        $datos = $this->natural() + ['estado' => 1];
        $modelo->expects($this->once())->method('doInsert')->with($datos)->willReturn(true);
        $this->assertTrue($modelo->insert($datos, false));
    }

    public function testEditarEmpresaNoRequiereReactivarNiCambiarSuIdentificador(): void
    {
        $modelo = $this->getMockBuilder(PersonaModel::class)->onlyMethods(['doUpdate'])->getMock();
        $datos = $modelo->datosCliente($this->empresa(['razonsocial' => 'Empresa editada SAC']));
        $modelo->expects($this->once())->method('doUpdate')->with([7], $datos)->willReturn(true);
        $this->assertTrue($modelo->update(7, $datos));
        $this->assertArrayNotHasKey('estado', $datos);
    }

    public function testModeloPermiteCambiarSoloElEstado(): void
    {
        $modelo = $this->getMockBuilder(PersonaModel::class)->onlyMethods(['doUpdate'])->getMock();
        $modelo->expects($this->once())->method('doUpdate')->with([7], ['estado' => 0])->willReturn(true);
        $this->assertTrue($modelo->update(7, ['estado' => 0]));
    }

    public function testModeloRechazaInsercionSinNombresNiRazonSocial(): void
    {
        $modelo = $this->getMockBuilder(PersonaModel::class)->onlyMethods(['doInsert'])->getMock();
        $modelo->expects($this->never())->method('doInsert');
        $this->assertFalse($modelo->insert([
            'tipodoc' => 'RUC', 'numerodoc' => '20123456789',
            'nombres' => null, 'razonsocial' => null,
        ], false));
    }
}
