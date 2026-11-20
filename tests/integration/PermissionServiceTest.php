<?php

use App\Services\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class PermissionServiceTest extends CIUnitTestCase
{
    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect([
            'database' => ':memory:',
            'DBDriver' => 'SQLite3',
            'DBPrefix' => '',
            'foreignKeys' => true,
        ], false);
        $this->service = new PermissionService($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testDeniegaAccesoSiFaltanLasTablasDeSeguridad(): void
    {
        $this->assertFalse($this->service->can(1, 'servicios.ver'));
        $this->assertFalse($this->service->canUseLocation(1, 1));
    }

    public function testRespetaPermisosYLocalesAsignados(): void
    {
        $this->db->query('CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY, idrol INTEGER, estado INTEGER)');
        $this->db->query('CREATE TABLE permisos (idpermiso INTEGER PRIMARY KEY, clave TEXT)');
        $this->db->query('CREATE TABLE rol_permiso (idrol INTEGER, idpermiso INTEGER)');
        $this->db->query('CREATE TABLE usuario_local (idusuario INTEGER, idlocal INTEGER)');
        $this->db->table('usuarios')->insert(['idusuario' => 1, 'idrol' => 2, 'estado' => 1]);
        $this->db->table('permisos')->insert(['idpermiso' => 1, 'clave' => 'servicios.ver']);
        $this->db->table('rol_permiso')->insert(['idrol' => 2, 'idpermiso' => 1]);
        $this->db->table('usuario_local')->insert(['idusuario' => 1, 'idlocal' => 3]);

        $this->assertTrue($this->service->can(1, 'servicios.ver'));
        $this->assertFalse($this->service->can(1, 'servicios.gestionar'));
        $this->assertTrue($this->service->canUseLocation(1, 3));
        $this->assertFalse($this->service->canUseLocation(1, 4));
    }

    public function testUsuarioInactivoNoConservaPermisos(): void
    {
        $this->db->query('CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY, idrol INTEGER, estado INTEGER)');
        $this->db->query('CREATE TABLE permisos (idpermiso INTEGER PRIMARY KEY, clave TEXT)');
        $this->db->query('CREATE TABLE rol_permiso (idrol INTEGER, idpermiso INTEGER)');
        $this->db->table('usuarios')->insert(['idusuario' => 1, 'idrol' => 2, 'estado' => 0]);
        $this->db->table('permisos')->insert(['idpermiso' => 1, 'clave' => 'servicios.ver']);
        $this->db->table('rol_permiso')->insert(['idrol' => 2, 'idpermiso' => 1]);

        $this->assertFalse($this->service->can(1, 'servicios.ver'));
    }
}
