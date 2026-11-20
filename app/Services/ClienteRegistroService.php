<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use RuntimeException;

class ClienteRegistroService
{
    public const MENSAJE = 'Si los datos son válidos, te enviamos un correo para continuar.';

    public function __construct(private ?BaseConnection $db = null, private ?PedidoCorreoTransportador $correo = null)
    {
        $this->db ??= db_connect();
        $this->correo ??= new CiEmailTransportador();
    }

    public function registrar(array $datos): bool
    {
        $texto = static fn($v) => is_string($v) ? trim($v) : '';
        $email = mb_strtolower($texto($datos['email'] ?? ''));
        $nombre = $texto($datos['nombre'] ?? '');
        $clave = is_string($datos['clave'] ?? null) ? $datos['clave'] : '';
        $tipo = $texto($datos['tipodoc'] ?? '');
        $documento = $texto($datos['numerodoc'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || strlen($clave) < 8 || strlen($clave) > 72
            || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150 || !in_array($tipo, ['DNI', 'RUC', 'CE', 'PASAPORTE'], true)
            || mb_strlen($documento) < 5 || mb_strlen($documento) > 20) return false;

        $token = bin2hex(random_bytes(32));
        $this->db->transBegin();
        try {
            $cuenta = $this->db->table('cliente_cuentas')->where('email', $email)->get()->getRowArray();
            if ($cuenta) {
                // Reenvío a la dirección original, sin sobrescribir clave, persona ni cuentas desactivadas.
                if ($cuenta['email_verificado_en'] !== null
                    || (!(int) $cuenta['activo'] && empty($cuenta['verificacion_token_hash']))) {
                    $this->db->transRollback();
                    return false;
                }
                $id = (int) $cuenta['idcliente_cuenta'];
            } else {
                // Saber un documento existente no autoriza apropiarse de esa persona.
                if ($this->db->table('personas')->where('tipodoc', $tipo)->where('numerodoc', $documento)->countAllResults()) {
                    $this->db->transRollback();
                    return false;
                }
                $juridica = $tipo === 'RUC';
                if (!$this->db->table('personas')->insert([
                    'tipopersona' => $juridica ? 'JURIDICA' : 'NATURAL', 'nombres' => $juridica ? null : $nombre,
                    'razonsocial' => $juridica ? $nombre : null, 'tipodoc' => $tipo, 'numerodoc' => $documento, 'email' => $email, 'estado' => 1,
                ])) throw new RuntimeException('Registro no disponible.');
                $idPersona = (int) $this->db->insertID();
                if (!$this->db->table('cliente_cuentas')->insert([
                    'idpersona' => $idPersona, 'email' => $email, 'clave_hash' => password_hash($clave, PASSWORD_DEFAULT),
                    'activo' => 0, 'email_verificado_en' => null, 'creado_en' => date('Y-m-d H:i:s'),
                ])) throw new RuntimeException('Registro no disponible.');
                $id = (int) $this->db->insertID();
            }
            if (!$this->db->table('cliente_cuentas')->where('idcliente_cuenta', $id)->update([
                'verificacion_token_hash' => hash('sha256', $token), 'verificacion_expira_en' => date('Y-m-d H:i:s', time() + 86400),
            ]) || !$this->db->transStatus() || !$this->db->transCommit()) throw new RuntimeException('Registro no disponible.');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            // No registrar SQL, contraseña, correo ni token de verificación.
            log_message('error', 'No se pudo preparar el registro de cliente.');
            return false;
        }
        try {
            $this->correo->enviar($email, 'Verifica tu correo en DELAFIBER',
                "Confirma tu correo mediante este enlace privado (válido por 24 horas):\n"
                . base_url('cliente/verificar/' . $token) . "\nSi no solicitaste esta cuenta, ignora este mensaje.");
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar la verificación de cliente.');
            return false;
        }
    }

    public function verificar(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $this->db->transBegin();
        try {
            $sql = 'SELECT * FROM cliente_cuentas WHERE verificacion_token_hash = ?';
            if ($this->db->DBDriver === 'MySQLi') $sql .= ' FOR UPDATE';
            $cuenta = $this->db->query($sql, [hash('sha256', $token)])->getRowArray();
            if (!$cuenta || $cuenta['email_verificado_en'] !== null || empty($cuenta['verificacion_expira_en'])
                || $cuenta['verificacion_expira_en'] <= date('Y-m-d H:i:s')) {
                $this->db->transRollback();
                return false;
            }
            if (!$this->db->table('cliente_cuentas')->where('idcliente_cuenta', $cuenta['idcliente_cuenta'])->update([
                'activo' => 1, 'email_verificado_en' => date('Y-m-d H:i:s'),
                'verificacion_token_hash' => null, 'verificacion_expira_en' => null,
            ]) || !$this->db->transStatus() || !$this->db->transCommit()) throw new RuntimeException('Verificación no disponible.');
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'No se pudo verificar la cuenta de cliente.');
            return false;
        }
    }
}
