<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use RuntimeException;

class PedidoWebService
{
    public function __construct(
        private ?BaseConnection $db = null,
        private ?PedidoWebCorreoService $correos = null,
        private ?PedidoWebWhatsAppService $whatsApp = null
    )
    {
        $this->db ??= db_connect();
        $this->correos ??= new PedidoWebCorreoService($this->db);
        $this->whatsApp ??= new PedidoWebWhatsAppService();
    }

    public function crear(array $datos, array $carrito, ?array $cuenta = null): array
    {
        $resumen = (new TiendaCarritoService($this->db))->resolver($carrito);
        if (!$resumen['items'] || $resumen['no_disponibles']) {
            throw new InvalidArgumentException('El carrito contiene articulos no disponibles. Revisalo antes de continuar.');
        }
        $modalidad = (string) ($datos['modalidad'] ?? '');
        if (!in_array($modalidad, ['RECOJO', 'DOMICILIO'], true)) {
            throw new InvalidArgumentException('Selecciona una modalidad de entrega valida.');
        }
        $nombre = mb_substr(trim((string) ($datos['nombre'] ?? '')), 0, 150);
        $email = mb_strtolower(mb_substr(trim((string) ($datos['email'] ?? '')), 0, 100));
        $telefono = mb_substr(trim((string) ($datos['telefono'] ?? '')), 0, 20);
        $tipodoc = (string) ($datos['tipodoc'] ?? '');
        $documento = mb_substr(trim((string) ($datos['numerodoc'] ?? '')), 0, 20);
        $direccion = mb_substr(trim((string) ($datos['direccion'] ?? '')), 0, 200);
        $observaciones = mb_substr(trim((string) ($datos['observaciones'] ?? '')), 0, 500);
        if (mb_strlen($nombre) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9+() -]{6,20}$/D', $telefono)) {
            throw new InvalidArgumentException('Completa nombre, correo y telefono validos.');
        }
        if (!in_array($tipodoc, ['DNI', 'RUC', 'CE', 'PASAPORTE'], true) || mb_strlen($documento) < 5) {
            throw new InvalidArgumentException('Completa un documento valido.');
        }
        if ($modalidad === 'DOMICILIO' && mb_strlen($direccion) < 8) {
            throw new InvalidArgumentException('Indica la direccion de entrega.');
        }
        $requestToken = (string) ($datos['request_token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $requestToken)) {
            throw new InvalidArgumentException('La solicitud de compra no es valida. Recarga el checkout.');
        }

        $this->db->transBegin();
        try {
            $idPersona = $this->persona($cuenta);
            $local = $this->db->table('locales')->select('idlocal')->orderBy("CASE WHEN tipolocal = 'TIENDA' THEN 0 ELSE 1 END", '', false)->orderBy('idlocal')->get(1)->getRowArray();
            $estado = $this->db->table('estadopedido')->select('idestado')->where('codigo', 'PENDIENTE')->get()->getRowArray();
            if (!$local || !$estado) {
                throw new RuntimeException('No existe un local o estado pendiente configurado.');
            }
            $ahora = date('Y-m-d H:i:s');
            $this->db->table('pedido')->insert([
                'tipopedido' => 'TIENDA_WEB', 'idestado' => $estado['idestado'], 'fecha' => $ahora,
                'idlocal' => $local['idlocal'], 'idusuario' => null, 'idpersona' => $idPersona,
                'subtotal' => $resumen['total'], 'descuento' => 0, 'total' => $resumen['total'],
                'tipodescuento' => 'NINGUNO', 'valordescuento' => 0,
                'observaciones' => $observaciones ?: null, 'request_token' => $requestToken,
                'updated_at' => $ahora, 'revision' => 1,
            ]);
            $idPedido = (int) $this->db->insertID();
            $codigo = 'PED-' . str_pad((string) $idPedido, 6, '0', STR_PAD_LEFT);
            $this->db->table('pedido')->where('idpedido', $idPedido)->update(['codigo' => $codigo]);
            foreach ($resumen['items'] as $item) {
                $this->db->table('detpedido')->insert([
                    'idpedido' => $idPedido, 'idproducto' => $item['idproducto'], 'cantidad' => $item['cantidad'],
                    'preciounitario' => $item['precioventa'], 'nombre_snapshot' => $item['nombre'],
                    'config_snapshot' => json_encode(['tipo' => $item['tipo'], 'origen' => 'TIENDA_WEB'], JSON_THROW_ON_ERROR),
                ]);
            }
            $token = bin2hex(random_bytes(32));
            $this->db->table('pedido_web')->insert([
                'idpedido' => $idPedido, 'idcliente_cuenta' => $cuenta['idcliente_cuenta'] ?? null,
                'modalidad' => $modalidad, 'direccion_entrega' => $modalidad === 'DOMICILIO' ? $direccion : null,
                'nombre_contacto' => $nombre, 'email_contacto' => $email, 'telefono_contacto' => $telefono,
                'token_seguimiento_hash' => hash('sha256', $token), 'creado_en' => $ahora,
            ]);
            $this->db->table('historial_pedido')->insert([
                'idpedido' => $idPedido, 'idusuario' => null, 'evento' => 'CREACION_WEB',
                'estado_anterior' => null, 'estado_nuevo' => 'PENDIENTE', 'motivo' => 'Pedido creado en Tienda web.', 'fecha' => $ahora,
            ]);
            if (!$this->db->transStatus() || !$this->db->transCommit()) {
                throw new RuntimeException('No se pudo confirmar el pedido.');
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
        $correoEnviado = $this->correos->recibido($idPedido, $token);
        $whatsAppEnviado = $this->whatsApp->nuevo([
            'codigo' => $codigo,
            'cliente' => $nombre,
            'total' => $resumen['total'],
            'telefono' => $telefono,
            'modalidad' => $modalidad,
        ]);
        return [
            'idpedido' => $idPedido,
            'codigo' => $codigo,
            'token' => $token,
            'correo_enviado' => $correoEnviado,
            'whatsapp_enviado' => $whatsAppEnviado,
        ];
    }

    private function persona(?array $cuenta): ?int
    {
        // La cuenta viene exclusivamente de cuentaActual(), no del formulario.
        return $cuenta && !empty($cuenta['idpersona']) ? (int) $cuenta['idpersona'] : null;
    }
}
