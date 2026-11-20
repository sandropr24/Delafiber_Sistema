<?php

use App\Services\CajaService;
use App\Services\VentaEstadoInicial;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class AutorizacionLocalVentaTest extends CIUnitTestCase
{
    public function testEstadoInicialSoloPermitePendienteYConfirmada(): void
    {
        $this->assertTrue(VentaEstadoInicial::permitido('Pendiente'));
        $this->assertTrue(VentaEstadoInicial::permitido('Confirmada'));
        foreach (['En preparación', 'Entregada', 'Cancelada', null] as $estado) {
            $this->assertFalse(VentaEstadoInicial::permitido($estado));
        }
    }

    public function testAdministradorYVendedorRequierenLocalAsignado(): void
    {
        if (!extension_loaded('sqlite3')) $this->markTestSkipped('SQLite3 requerido.');
        $db = Database::connect(['database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => ''], false);
        try {
            $db->query('CREATE TABLE roles (idrol INTEGER PRIMARY KEY, rol TEXT)');
            $db->query('CREATE TABLE usuarios (idusuario INTEGER PRIMARY KEY, idrol INTEGER, estado INTEGER)');
            $db->query('CREATE TABLE locales (idlocal INTEGER PRIMARY KEY)');
            $db->query('CREATE TABLE usuario_local (idusuario INTEGER, idlocal INTEGER)');
            $db->table('roles')->insertBatch([['idrol'=>1,'rol'=>'Administrador'],['idrol'=>2,'rol'=>'Vendedor']]);
            $db->table('usuarios')->insertBatch([['idusuario'=>1,'idrol'=>1,'estado'=>1],['idusuario'=>2,'idrol'=>2,'estado'=>1]]);
            $db->table('locales')->insert(['idlocal'=>5]);
            $caja = new CajaService($db);
            foreach ([1,2] as $idUsuario) {
                try {
                    $caja->validarLocalAsignado(5, $idUsuario);
                    $this->fail('Debe rechazar usuario sin local asignado.');
                } catch (InvalidArgumentException $e) {
                    $this->assertStringContainsString('no está asignado', $e->getMessage());
                }
                $db->table('usuario_local')->insert(['idusuario'=>$idUsuario,'idlocal'=>5]);
                $caja->validarLocalAsignado(5, $idUsuario);
                $this->assertTrue(true);
            }
        } finally { $db->close(); }
    }
}
