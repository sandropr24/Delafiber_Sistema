<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LocalModel;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class LocalController extends BaseController
{
    protected LocalModel $localModel;

    public function __construct()
    {
        $this->localModel = model(LocalModel::class);
    }

    public function index(): string
    {
        return view('locales/index', [
            'titulo'  => 'Sedes y Locales',
            'locales' => $this->localModel
                ->orderBy('nombrelocal', 'ASC')
                ->findAll(),
        ]);
    }

    public function guardar(): RedirectResponse
    {
        $reglas = [
            'nombrelocal' => 'required|min_length[3]|max_length[80]',
            'direccion'   => 'required|max_length[150]',
            'tipolocal'   => 'required|in_list[TIENDA,ALMACEN,OFICINA]',
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $id = $this->request->getPost('idlocal');

        if ($id !== null && $id !== '' &&
            (!ctype_digit((string) $id) || (int) $id <= 0)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El identificador del local no es válido.');
        }

        $esEdicion = $id !== null && $id !== '';

        if ($esEdicion && !$this->localModel->find((int) $id)) {
            return redirect()->to(base_url('admin/locales'))
                ->with('error', 'El local no existe.');
        }

        $datos = [
            'nombrelocal' => trim((string) $this->request->getPost('nombrelocal')),
            'direccion'   => trim((string) $this->request->getPost('direccion')),
            'tipolocal'   => trim((string) $this->request->getPost('tipolocal')),
        ];

        if ($esEdicion) {
            $datos['idlocal'] = (int) $id;
        }

        $db = db_connect();

        $db->transBegin();

        try {
            if (!$this->localModel->save($datos)) {
                $db->transRollback();

                return redirect()->back()
                    ->withInput()
                    ->with('errors', $this->localModel->errors());
            }

            if (!$esEdicion && $db->tableExists('usuario_local')) {
                $idLocal = (int) $this->localModel->getInsertID();

                if ($idLocal <= 0) {
                    throw new \RuntimeException(
                        'No se pudo obtener el identificador del local.'
                    );
                }

                $administradores = $db->table('usuarios u')
                    ->select('u.idusuario')
                    ->join('roles r', 'r.idrol = u.idrol')
                    ->where('r.rol', 'Administrador')
                    ->get()
                    ->getResultArray();

                foreach ($administradores as $usuario) {
                    $insertado = $db->table('usuario_local')->insert([
                        'idusuario' => (int) $usuario['idusuario'],
                        'idlocal'   => $idLocal,
                    ]);

                    if (!$insertado) {
                        throw new \RuntimeException(
                            'No se pudo asignar el local a los administradores.'
                        );
                    }
                }
            }

            if (!$db->transStatus() || !$db->transCommit()) {
                throw new \RuntimeException(
                    'No se pudo confirmar el registro del local.'
                );
            }
        } catch (Throwable $e) {
            $db->transRollback();

            log_message(
                'error',
                'Error al guardar local: ' . $e->getMessage()
            );

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo guardar el local.');
        }

        $mensaje = $esEdicion
            ? 'Local actualizado correctamente.'
            : 'Local registrado correctamente.';

        return redirect()->to(base_url('admin/locales'))
            ->with('mensaje', $mensaje);
    }

    public function eliminar($id = null): RedirectResponse
    {
        if (
            $id === null ||
            !ctype_digit((string) $id) ||
            (int) $id <= 0 ||
            !$this->localModel->find((int) $id)
        ) {
            return redirect()->to(base_url('admin/locales'))
                ->with('error', 'El local no existe.');
        }

        $db = db_connect();

        $enKardex = $db->table('kardex')
            ->where('idlocal', (int) $id)
            ->countAllResults();

        if ($enKardex > 0) {
            return redirect()->to(base_url('admin/locales'))
                ->with('error', 'No se puede eliminar: el local tiene movimientos de inventario registrados.');
        }

        try {
            if (!$this->localModel->delete((int) $id)) {
                return redirect()->to(base_url('admin/locales'))
                    ->with('error', 'No se pudo eliminar el local.');
            }

            return redirect()->to(base_url('admin/locales'))
                ->with('mensaje', 'Local eliminado correctamente.');
        } catch (Throwable $e) {
            log_message(
                'error',
                'Error al eliminar local: ' . $e->getMessage()
            );

            return redirect()->to(base_url('admin/locales'))
                ->with('error', 'Ocurrió un error al intentar eliminar el local.');
        }
    }
}
