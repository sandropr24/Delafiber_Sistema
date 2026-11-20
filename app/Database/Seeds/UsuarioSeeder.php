<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $nombre = trim((string) env('seed.adminUser', ''));
        $documento = trim((string) env('seed.adminDoc', ''));
        if ($nombre === '' || strlen($nombre) > 50 || $documento === '') {
            throw new \RuntimeException('Define seed.adminUser y seed.adminDoc antes de ejecutar UsuarioSeeder.');
        }
        if ($this->db->table('usuarios')->where('nombreusuario', $nombre)->countAllResults() > 0) {
            // No cambiar contraseña, rol, estado ni locales de una cuenta existente.
            return;
        }
        $persona = $this->db->table('personas')->where('numerodoc', $documento)->get()->getRowArray();
        $rol = $this->db->table('roles')->where('rol', 'Administrador')->get()->getRowArray();
        if (!$persona || !$rol) {
            throw new \RuntimeException('Ejecute SeguridadSeeder y PersonaSeeder antes de crear el administrador inicial.');
        }
        $claveInicial = trim((string) env('seed.adminPassword', ''));
        if (strlen($claveInicial) < 12 || strlen($claveInicial) > 72) {
            throw new \RuntimeException('Define seed.adminPassword con una contraseña de 12 a 72 caracteres antes de crear el administrador.');
        }
        if (!$this->db->tableExists('usuario_local')) {
            throw new \RuntimeException('Ejecute todas las migraciones antes de crear el administrador inicial.');
        }
        $locales = $this->db->table('locales')->select('idlocal')->orderBy('idlocal')->get()->getResultArray();
        if (!$locales) {
            throw new \RuntimeException('Configure al menos un local antes de crear el administrador inicial.');
        }
        if (!$this->db->transBegin()) {
            throw new \RuntimeException('No se pudo iniciar la creación del administrador.');
        }
        try {
            $usuario = [
                'idpersona' => $persona['idpersona'], 'idrol' => $rol['idrol'],
                'nombreusuario' => $nombre, 'claveacceso' => password_hash($claveInicial, PASSWORD_BCRYPT),
                'estado' => 1,
            ];
            if (!$this->db->table('usuarios')->insert($usuario)) {
                throw new \RuntimeException('No se pudo crear el administrador inicial.');
            }
            $idUsuario = (int) $this->db->insertID();
            foreach ($locales as $local) {
                if (!$this->db->table('usuario_local')->insert(['idusuario' => $idUsuario, 'idlocal' => (int) $local['idlocal']])) {
                    throw new \RuntimeException('No se pudieron asignar los locales al administrador inicial.');
                }
            }
            if (!$this->db->transStatus() || !$this->db->transCommit()) {
                throw new \RuntimeException('No se pudo confirmar el administrador y sus locales.');
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
