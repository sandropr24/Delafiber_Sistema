<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class PermissionService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function can(int $idUsuario, string $permiso): bool
    {
        if ($idUsuario < 1) {
            return false;
        }
        if (!$this->db->tableExists('permisos') || !$this->db->tableExists('rol_permiso')) {
            return false;
        }

        return $this->db->table('usuarios u')
            ->join('rol_permiso rp', 'rp.idrol = u.idrol')
            ->join('permisos p', 'p.idpermiso = rp.idpermiso')
            ->where('u.idusuario', $idUsuario)
            ->where('u.estado', 1)
            ->where('p.clave', $permiso)
            ->countAllResults() > 0;
    }

    public function canUseLocation(int $idUsuario, int $idLocal): bool
    {
        if ($idUsuario < 1 || $idLocal < 1 || !$this->db->tableExists('usuario_local')) {
            return false;
        }
        return $this->db->table('usuario_local')
            ->where('idusuario', $idUsuario)
            ->where('idlocal', $idLocal)
            ->countAllResults() > 0;
    }
}
