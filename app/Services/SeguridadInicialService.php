<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class SeguridadInicialService
{
    public const PERMISOS = [
        'servicios.ver' => 'Consultar órdenes y agenda de servicios',
        'servicios.gestionar' => 'Crear, programar y actualizar órdenes',
        'inventario.consumir' => 'Registrar materiales usados en servicios',
        'inventario.anular_consumo' => 'Anular consumos de servicios',
    ];

    private const ROLES = [
        'Administrador' => ['servicios.ver', 'servicios.gestionar', 'inventario.consumir', 'inventario.anular_consumo'],
        'Operador' => ['servicios.ver', 'servicios.gestionar', 'inventario.consumir'],
        'Vendedor' => [],
    ];

    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function inicializar(): void
    {
        foreach (['roles', 'permisos', 'rol_permiso'] as $tabla) {
            if (!$this->db->tableExists($tabla)) {
                throw new RuntimeException('Ejecute todas las migraciones antes de inicializar los roles y permisos.');
            }
        }
        if (!$this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la configuración de seguridad.');
        }

        try {
            $idsPermisos = [];
            foreach (self::PERMISOS as $clave => $descripcion) {
                $permiso = $this->db->table('permisos')->select('idpermiso')->where('clave', $clave)->get()->getRowArray();
                if ($permiso) {
                    $idsPermisos[$clave] = (int) $permiso['idpermiso'];
                } else {
                    if (!$this->db->table('permisos')->insert(compact('clave', 'descripcion'))) {
                        throw new RuntimeException('No se pudo registrar el catálogo de permisos.');
                    }
                    $idsPermisos[$clave] = (int) $this->db->insertID();
                }
            }

            foreach (self::ROLES as $rol => $claves) {
                if ($this->db->table('roles')->select('idrol')->where('rol', $rol)->get()->getRowArray()) {
                    // Incluso un rol sin permisos puede ser una decisión
                    // deliberada. No reponer autorizaciones al repetir el seed.
                    continue;
                }
                if (!$this->db->table('roles')->insert(['rol' => $rol])) {
                    throw new RuntimeException('No se pudo registrar el rol inicial.');
                }
                $idRol = (int) $this->db->insertID();
                foreach ($claves as $clave) {
                    if (!$this->db->table('rol_permiso')->insert(['idrol' => $idRol, 'idpermiso' => $idsPermisos[$clave]])) {
                        throw new RuntimeException('No se pudieron asignar los permisos del nuevo rol.');
                    }
                }
            }
            if (!$this->db->transStatus() || !$this->db->transCommit()) {
                throw new RuntimeException('No se pudo confirmar la configuración de seguridad.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
