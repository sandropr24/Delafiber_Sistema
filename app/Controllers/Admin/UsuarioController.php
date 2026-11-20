<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Models\PersonaModel;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;
use RuntimeException;

class UsuarioController extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected PersonaModel $personaModel;

    public function __construct()
    {
        $this->usuarioModel = model(UsuarioModel::class);
        $this->personaModel = model(PersonaModel::class);
    }

    public function index(): string
    {
        $db = $this->usuarioModel->builder()->db();
        $usuarios = $this->usuarioModel->obtenerUsuariosConPersona();
        $asignaciones = [];

        if ($db->tableExists('usuario_local')) {
            $filas = $db->table('usuario_local')
                ->select('idusuario, idlocal')
                ->get()
                ->getResultArray();

            foreach ($filas as $fila) {
                $asignaciones[(int) $fila['idusuario']][] =
                    (int) $fila['idlocal'];
            }
        }

        foreach ($usuarios as &$usuario) {
            $usuario['idlocales'] =
                $asignaciones[(int) $usuario['idusuario']] ?? [];
        }
        unset($usuario);

        return view('usuarios/index', [
            'titulo' => 'Usuarios y Personal',
            'usuarios' => $usuarios,
            'locales' => $db->table('locales')
                ->select('idlocal, nombrelocal')
                ->orderBy('nombrelocal')
                ->get()
                ->getResultArray(),
        ]);
    }

    public function guardar(): RedirectResponse
    {
        $idUsuarioRaw = trim(
            (string) $this->request->getPost('idusuario')
        );

        if (
            $idUsuarioRaw !== '' &&
            (!ctype_digit($idUsuarioRaw) || (int) $idUsuarioRaw <= 0)
        ) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'El usuario indicado no es válido.');
        }

        $idUsuario = $idUsuarioRaw === ''
            ? null
            : (int) $idUsuarioRaw;

        $usuarioActual = $idUsuario === null
            ? null
            : $this->usuarioModel->find($idUsuario);

        if ($idUsuario !== null && !$usuarioActual) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'El usuario no existe.');
        }

        $idPersona = $usuarioActual
            ? (int) $usuarioActual['idpersona']
            : null;

        $clave = (string) $this->request->getPost('claveacceso');

        $reglas = [
            'nombres' => 'required|max_length[100]',
            'apellidos' => 'required|max_length[100]',
            'tipodoc' => 'required|in_list[DNI,RUC,CE]',
            'numerodoc' => 'required|max_length[20]|is_unique[personas.numerodoc,idpersona,' . ($idPersona ?: 'NULL') . ']',
            'nombreusuario' => 'required|max_length[50]|is_unique[usuarios.nombreusuario,idusuario,' . ($idUsuario ?: 'NULL') . ']',
            'idrol' => 'required|is_natural_no_zero|is_not_unique[roles.idrol]',
            'email' => 'permit_empty|valid_email|max_length[100]',
            'claveacceso' => $idUsuario === null
                ? 'required|min_length[12]|max_length[72]'
                : 'permit_empty|min_length[12]|max_length[72]',
        ];

        $mensajes = [
            'numerodoc' => [
                'is_unique' => 'El número de documento ya se encuentra registrado.',
            ],
            'nombreusuario' => [
                'is_unique' => 'El nombre de usuario ya está en uso.',
            ],
            'claveacceso' => [
                'required' => 'La contraseña es obligatoria para nuevos usuarios.',
                'min_length' => 'La contraseña debe tener al menos 12 caracteres.',
                'max_length' => 'La contraseña no puede superar 72 caracteres.',
            ],
        ];

        if (!$this->validate($reglas, $mensajes)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = $this->usuarioModel->builder()->db();

        $localesRaw = $this->request->getPost('idlocales');

        if ($localesRaw === null) {
            $localesRaw = [];
        }

        if (!is_array($localesRaw)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'La selección de locales no es válida.');
        }

        $idsLocales = [];

        foreach ($localesRaw as $valor) {
            if (
                !is_scalar($valor) ||
                !ctype_digit((string) $valor) ||
                (int) $valor <= 0
            ) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Se recibió un identificador de local inválido.');
            }

            $idsLocales[] = (int) $valor;
        }

        $idsLocales = array_values(array_unique($idsLocales));

        $rol = $db->table('roles')
            ->select('rol')
            ->where('idrol', (int) $this->request->getPost('idrol'))
            ->get()
            ->getRow('rol');

        if (
            in_array($rol, ['Administrador', 'Operador'], true) &&
            empty($idsLocales)
        ) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Asigna al menos un local para operar inventario y servicios.');
        }

        if (!empty($idsLocales)) {
            $cantidad = $db->table('locales')
                ->whereIn('idlocal', $idsLocales)
                ->countAllResults();

            if ($cantidad !== count($idsLocales)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Uno de los locales seleccionados no existe.');
            }
        }

        if (!$db->tableExists('usuario_local')) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'La configuración de locales no está disponible.');
        }

        $camposPersona = [
            'nombres',
            'apellidos',
            'tipodoc',
            'numerodoc',
            'telefono',
            'email',
            'direccion',
        ];

        $datosPersona = $this->request->getPost($camposPersona);

        $datosPersona = array_map(
            static function ($valor) {
                $limpio = trim((string) $valor);
                return $limpio === '' ? null : $limpio;
            },
            $datosPersona
        );

        if ($idPersona !== null) {
            $datosPersona['idpersona'] = $idPersona;
        }

        $transaccionIniciada = false;

        try {
            if (!$db->transBegin()) {
                throw new RuntimeException(
                    'No se pudo iniciar la transacción.'
                );
            }

            $transaccionIniciada = true;

            if (!$this->personaModel->save($datosPersona)) {
                throw new RuntimeException(
                    'Error al guardar persona: ' .
                    json_encode($this->personaModel->errors())
                );
            }

            if ($idPersona === null) {
                $idPersona = (int) $this->personaModel->getInsertID();

                if ($idPersona <= 0) {
                    throw new RuntimeException(
                        'No se pudo obtener el identificador de la persona.'
                    );
                }
            }

            $datosUsuario = [
                'idpersona' => $idPersona,
                'nombreusuario' => trim(
                    (string) $this->request->getPost('nombreusuario')
                ),
                'idrol' => (int) $this->request->getPost('idrol'),
                'estado' => $usuarioActual
                    ? (int) $usuarioActual['estado']
                    : 1,
            ];

            if ($idUsuario !== null) {
                $datosUsuario['idusuario'] = $idUsuario;
            }

            if ($clave !== '') {
                $datosUsuario['claveacceso'] = $clave;
            }

            if (!$this->usuarioModel->save($datosUsuario)) {
                throw new RuntimeException(
                    'Error al guardar usuario: ' .
                    json_encode($this->usuarioModel->errors())
                );
            }

            $usuarioGuardado = $idUsuario
                ?? (int) $this->usuarioModel->getInsertID();

            if ($usuarioGuardado <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar el usuario guardado.'
                );
            }

            $eliminado = $db->table('usuario_local')
                ->where('idusuario', $usuarioGuardado)
                ->delete();

            if (!$eliminado) {
                throw new RuntimeException(
                    'No se pudieron actualizar las asignaciones de locales.'
                );
            }

            foreach ($idsLocales as $idLocal) {
                $insertado = $db->table('usuario_local')
                    ->insert([
                        'idusuario' => $usuarioGuardado,
                        'idlocal' => $idLocal,
                    ]);

                if (!$insertado) {
                    throw new RuntimeException(
                        'No se pudo asignar uno de los locales.'
                    );
                }
            }

            if (!$db->transStatus()) {
                throw new RuntimeException(
                    'La transacción registró un error.'
                );
            }

            if (!$db->transCommit()) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción.'
                );
            }

            $transaccionIniciada = false;

        } catch (Throwable $e) {
            if ($transaccionIniciada) {
                $db->transRollback();
            }

            log_message(
                'error',
                'Error al guardar usuario: ' . $e->getMessage()
            );

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo guardar el usuario. Verifica los datos e inténtalo nuevamente.');
        }

        $mensaje = $idUsuario !== null
            ? 'Usuario actualizado con éxito.'
            : 'Usuario registrado con éxito.';

        return redirect()->to(base_url('admin/usuarios'))
            ->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        if (
            $id === null ||
            !ctype_digit((string) $id) ||
            (int) $id <= 0
        ) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'El usuario indicado no es válido.');
        }

        $usuario = $this->usuarioModel->find((int) $id);

        if (!$usuario) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'El usuario no existe.');
        }

        if (
            (int) $usuario['idusuario'] ===
            (int) session()->get('idusuario')
        ) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'No puedes cambiar el estado de tu propia cuenta.');
        }

        $nuevoEstado = (int) $usuario['estado'] === 1 ? 0 : 1;

        try {
            if (!$this->usuarioModel->update(
                (int) $id,
                ['estado' => $nuevoEstado]
            )) {
                return redirect()->to(base_url('admin/usuarios'))
                    ->with('error', 'No se pudo actualizar el estado del usuario.');
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Error al cambiar estado de usuario: ' . $e->getMessage()
            );

            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'Ocurrió un error al actualizar el estado.');
        }

        return redirect()->to(base_url('admin/usuarios'))
            ->with('mensaje', 'Estado del usuario actualizado.');
    }
}
