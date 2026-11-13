<?php
namespace App\Models;

use App\Libraries\ImportesCotizacion;
use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

class CotizacionModel extends Model
{
    protected $table = 'cotizacion';
    protected $primaryKey = 'idcotizacion';
    protected $returnType = 'array';

    public function rol(int $usuario): string
    {
        $rol = $this->db->table('usuarios u')->select('r.rol')->join('roles r', 'r.idrol = u.idrol')->where('u.idusuario', $usuario)->where('u.estado', 1)->get()->getRow('rol');
        if (!in_array($rol, ['Administrador', 'Vendedor', 'Operador'], true)) throw new InvalidArgumentException('No tienes permiso para gestionar cotizaciones.');
        return $rol;
    }

    private function estadoId(string $codigo): int
    {
        $id = (int) $this->db->table('estadocotizacion')->where('codigo', $codigo)->get()->getRow('idestado');
        if (!$id) throw new RuntimeException('No están configurados los estados de cotización.');
        return $id;
    }

    public function obtener(int $id): ?array
    {
        $fila = $this->db->table('cotizacion c')->select("c.*, e.codigo AS estado_codigo, e.estado, l.nombre AS linea, lo.nombrelocal, u.nombreusuario, COALESCE(NULLIF(p.razonsocial, ''), CONCAT_WS(' ', p.nombres, p.apellidos)) AS cliente, pe.idpedido, v.idventa", false)
            ->join('estadocotizacion e', 'e.idestado = c.idestado')->join('lineas l', 'l.idlinea = c.idlinea')->join('locales lo', 'lo.idlocal = c.idlocal')->join('usuarios u', 'u.idusuario = c.idusuario')->join('personas p', 'p.idpersona = c.idpersona')->join('pedido pe', 'pe.idcotizacion = c.idcotizacion', 'left')->join('ventas v', 'v.idcotizacion = c.idcotizacion', 'left')->where('c.idcotizacion', $id)->get()->getRowArray();
        if ($fila) $fila['vencida'] = $fila['fechavencimiento'] < date('Y-m-d') && in_array($fila['estado_codigo'], ['BORRADOR', 'EMITIDA', 'ACEPTADA'], true);
        return $fila;
    }

    public function detalles(int $id): array
    {
        return $this->db->table('detcotizacion d')->select('d.*, COALESCE(d.nombre_snapshot, p.nombre) AS nombre')->join('productos p', 'p.idproducto = d.idproducto')->where('d.idcotizacion', $id)->orderBy('d.iddetcotizacion')->get()->getResultArray();
    }

    private function bloquear(int $id): array
    {
        $fila = $this->db->query('SELECT * FROM cotizacion WHERE idcotizacion = ? FOR UPDATE', [$id])->getRowArray();
        if (!$fila) throw new InvalidArgumentException('La cotización no existe.');
        $fila['estado_codigo'] = $this->db->table('estadocotizacion')->where('idestado', $fila['idestado'])->get()->getRow('codigo');
        return $fila;
    }

    private function numero(int $linea): string
    {
        $serie = $this->db->query('SELECT * FROM cotizacion_series WHERE idlinea = ? FOR UPDATE', [$linea])->getRowArray();
        if (!$serie) throw new InvalidArgumentException('Configura el prefijo de cotización para esta línea.');
        $anio = (int) date('Y'); $prefijo = $serie['prefijo'];
        $this->db->query("INSERT INTO correlativos (tipodocumento, prefijo, anio, ultimonumero) VALUES ('COTIZACION', ?, ?, ?) ON DUPLICATE KEY UPDATE idcorrelativo = idcorrelativo", [$prefijo, $anio, (int) $serie['numero_inicial'] - 1]);
        $contador = $this->db->query("SELECT * FROM correlativos WHERE tipodocumento = 'COTIZACION' AND prefijo = ? AND anio = ? FOR UPDATE", [$prefijo, $anio])->getRowArray();
        $cabecera = 'COT-' . $anio . '-' . $prefijo . '-';
        $ultimo = (int) $this->db->table('cotizacion')->select("MAX(CAST(SUBSTRING_INDEX(numero, '-', -1) AS UNSIGNED)) AS ultimo", false)->like('numero', $cabecera, 'after')->get()->getRow('ultimo');
        $siguiente = max((int) $contador['ultimonumero'] + 1, (int) $serie['numero_inicial'], $ultimo + 1);
        if ($siguiente > 999999999) throw new InvalidArgumentException('Se agotó el rango de numeración de esta serie.');
        $this->db->table('correlativos')->where('idcorrelativo', $contador['idcorrelativo'])->update(['ultimonumero' => $siguiente]);
        return $cabecera . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    private function evento(int $id, int $usuario, string $evento, string $texto, ?array $datos = null): void
    {
        if (!$this->db->table('historial_cotizacion')->insert(['idcotizacion' => $id, 'idusuario' => $usuario, 'evento' => $evento, 'observacion' => mb_substr($texto, 0, 300), 'datos' => $datos === null ? null : json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'fecha' => date('Y-m-d H:i:s')])) throw new RuntimeException('No se pudo registrar el historial.');
    }

    public function guardarCotizacion(array $datos, array $detalles, int $usuario, int $id = 0): int
    {
        $rol = $this->rol($usuario);
        $token = (string) ($datos['request_token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new InvalidArgumentException('Recarga el formulario antes de guardar.');
        $vencimiento = (string) ($datos['fechavencimiento'] ?? '');
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $vencimiento);
        if (!$fecha || $fecha->format('Y-m-d') !== $vencimiento || $vencimiento < date('Y-m-d')) throw new InvalidArgumentException('La vigencia debe ser hoy o una fecha posterior.');
        if (mb_strlen((string) ($datos['observacion'] ?? '')) > 255 || mb_strlen((string) ($datos['condiciones'] ?? '')) > 4000) throw new InvalidArgumentException('Revisa la extensión de observaciones y condiciones.');
        $this->db->transBegin();
        try {
            $this->db->query('SELECT idusuario FROM usuarios WHERE idusuario = ? FOR UPDATE', [$usuario]);
            if (!$id && ($previa = $this->db->table('cotizacion')->where('request_token', $token)->get()->getRowArray())) {
                if ((int) $previa['idusuario'] !== $usuario) throw new InvalidArgumentException('La solicitud pertenece a otro usuario.');
                $this->db->transCommit(); return (int) $previa['idcotizacion'];
            }
            $actual = $id ? $this->bloquear($id) : null;
            if ($actual && (!in_array($actual['estado_codigo'], ['BORRADOR', 'EMITIDA'], true) || (int) ($datos['revision'] ?? 0) !== (int) $actual['revision'])) throw new InvalidArgumentException('La cotización cambió o ya no admite edición. Recarga la página.');
            $linea = (int) ($datos['idlinea'] ?? 0);
            if (!$this->db->table('lineas')->where('idlinea', $linea)->where('estado', 1)->countAllResults()) throw new InvalidArgumentException('Selecciona una línea activa.');
            if ($actual && (int) $actual['idlinea'] !== $linea) throw new InvalidArgumentException('La línea de numeración no puede cambiar. Crea otra cotización para otra línea.');
            if (!$this->db->table('locales')->where('idlocal', (int) ($datos['idlocal'] ?? 0))->countAllResults()) throw new InvalidArgumentException('Selecciona un local válido.');
            $cliente = $this->db->table('personas p')->where('p.idpersona', (int) ($datos['idpersona'] ?? 0))->where('p.estado', 1)->whereNotIn('p.idpersona', static fn($b) => $b->select('idpersona')->from('usuarios'))->get()->getRowArray();
            if (!$cliente) throw new InvalidArgumentException('Selecciona un cliente activo.');
            $incluye = ($datos['precios_incluyen_igv'] ?? '1') === '1' || ($datos['precios_incluyen_igv'] ?? null) === 1;
            $calculo = ImportesCotizacion::calcular($detalles, (string) ($datos['moneda_origen'] ?? 'PEN'), $datos['tipocambio'] ?? '', $datos['porcentajeigv'] ?? '', $incluye);
            $validadas = array_column(model(PedidoModel::class)->validarArticulos($calculo['detalles'], $rol), null, 'idproducto');
            $snapshot = ['nombre' => trim((string) ($cliente['razonsocial'] ?: ($cliente['nombres'] . ' ' . ($cliente['apellidos'] ?? '')))), 'numerodoc' => $cliente['numerodoc'], 'tipodoc' => $cliente['tipodoc'], 'direccion' => $cliente['direccion'], 'telefono' => $cliente['telefono'], 'email' => $cliente['email']];
            $guardar = ['idpersona' => $cliente['idpersona'], 'idlocal' => (int) $datos['idlocal'], 'idlinea' => $linea, 'fechavencimiento' => $vencimiento, 'tipocambio' => $calculo['tipocambio'], 'porcentajeigv' => $calculo['porcentajeigv'], 'subtotal' => $calculo['subtotal'], 'montoigv' => $calculo['montoigv'], 'total' => $calculo['total'], 'moneda_origen' => $datos['moneda_origen'] ?? 'PEN', 'precios_incluyen_igv' => $incluye ? 1 : 0, 'observacion' => trim((string) ($datos['observacion'] ?? '')) ?: null, 'condiciones' => trim((string) ($datos['condiciones'] ?? '')) ?: null, 'cliente_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'updated_at' => date('Y-m-d H:i:s'), 'idestado' => $this->estadoId('BORRADOR')];
            if ($id) {
                $antes = ['cabecera' => $actual, 'detalles' => $this->detalles($id)];
                unset($antes['cabecera']['request_token']);
                $guardar['revision'] = (int) $actual['revision'] + 1;
                $this->db->table('cotizacion')->where('idcotizacion', $id)->update($guardar);
                $this->db->table('detcotizacion')->where('idcotizacion', $id)->delete();
            } else {
                $guardar += ['numero' => $this->numero($linea), 'idusuario' => $usuario, 'fecha' => date('Y-m-d H:i:s'), 'request_token' => $token];
                $this->db->table('cotizacion')->insert($guardar); $id = (int) $this->db->insertID(); $antes = null;
            }
            foreach ($calculo['detalles'] as $d) {
                if (mb_strlen($d['descripcion']) > 1000) throw new InvalidArgumentException('Una descripción supera 1000 caracteres.');
                $articulo = $validadas[$d['idproducto']];
                $this->db->table('detcotizacion')->insert(['idcotizacion' => $id, 'idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'preciounitario' => $d['precioventa'], 'precio_origen' => $d['precio_origen'], 'nombre_snapshot' => $articulo['nombre_snapshot'], 'descripcion' => $d['descripcion'] ?: $articulo['nombre_snapshot'], 'config_snapshot' => $articulo['config_snapshot']]);
            }
            $this->evento($id, $usuario, $actual ? 'EDICION' : 'CREACION', $actual ? 'Cotización revisada; vuelve a emitirla para convertirla.' : 'Cotización creada sin cobro ni reserva.', $antes);
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo guardar la cotización.');
            $this->db->transCommit(); return $id;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    private function vigente(array $cotizacion): void
    {
        if ($cotizacion['fechavencimiento'] < date('Y-m-d')) throw new InvalidArgumentException('La cotización venció. Actualiza su vigencia y vuelve a emitirla.');
    }

    public function cambiarEstado(int $id, string $nuevo, int $usuario): void
    {
        $this->rol($usuario); $this->db->transBegin();
        try {
            $c = $this->bloquear($id);
            $transiciones = ['BORRADOR' => ['EMITIDA', 'RECHAZADA'], 'EMITIDA' => ['ACEPTADA', 'RECHAZADA'], 'ACEPTADA' => ['RECHAZADA']];
            if (!in_array($nuevo, $transiciones[$c['estado_codigo']] ?? [], true)) throw new InvalidArgumentException('Este cambio de estado no está permitido.');
            if ($nuevo !== 'RECHAZADA') $this->vigente($c);
            $datos = ['idestado' => $this->estadoId($nuevo), 'updated_at' => date('Y-m-d H:i:s'), 'revision' => (int) $c['revision'] + 1];
            if ($nuevo === 'EMITIDA') $datos['fecha_emision'] = date('Y-m-d H:i:s');
            $this->db->table('cotizacion')->where('idcotizacion', $id)->update($datos);
            $this->evento($id, $usuario, $nuevo, 'Estado: ' . $nuevo . '.');
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo actualizar la cotización.');
            $this->db->transCommit();
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    public function validarParaPedido(int $id): array
    {
        $c = $this->bloquear($id); $this->vigente($c);
        if (!in_array($c['estado_codigo'], ['EMITIDA', 'ACEPTADA'], true) || $this->db->table('pedido')->where('idcotizacion', $id)->countAllResults() || $this->db->table('ventas')->where('idcotizacion', $id)->countAllResults()) throw new InvalidArgumentException('La cotización ya fue convertida o todavía no está emitida.');
        return $c;
    }

    public function convertirAPedido(int $id, int $usuario): int
    {
        model(PedidoModel::class)->usuarioAutorizado($usuario); $this->db->transBegin();
        try {
            $this->db->query('SELECT idusuario FROM usuarios WHERE idusuario = ? FOR UPDATE', [$usuario]);
            $c = $this->validarParaPedido($id);
            $detalles = array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $this->detalles($id));
            $pedido = model(PedidoModel::class)->guardarPedido(['idpersona' => $c['idpersona'], 'idlocal' => $c['idlocal'], 'request_token' => bin2hex(random_bytes(32)), 'observaciones' => 'Origen: ' . $c['numero'], 'tipodescuento' => 'NINGUNO', 'valordescuento' => '0'], $detalles, $usuario, 0, $id);
            $this->db->table('cotizacion')->where('idcotizacion', $id)->update(['idestado' => $this->estadoId('A_PEDIDO'), 'fecha_conversion' => date('Y-m-d H:i:s')]);
            $this->evento($id, $usuario, 'CONVERSION_PEDIDO', 'Pedido N.º ' . $pedido . '.', ['idpedido' => $pedido]);
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo convertir la cotización.');
            $this->db->transCommit(); return $pedido;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    public function validarParaVenta(array $venta, ?array $pedido): array
    {
        model(PedidoModel::class)->usuarioAutorizado((int) $venta['idvendedor']);
        $id = (int) $venta['idcotizacion']; $c = $this->bloquear($id);
        if ($this->db->table('ventas')->where('idcotizacion', $id)->countAllResults()) throw new InvalidArgumentException('La cotización ya tiene una venta.');
        if ($pedido) {
            if ((int) ($pedido['idcotizacion'] ?? 0) !== $id || $c['estado_codigo'] !== 'A_PEDIDO') throw new InvalidArgumentException('La cotización no corresponde al pedido.');
        } else {
            $this->vigente($c);
            if (!in_array($c['estado_codigo'], ['EMITIDA', 'ACEPTADA'], true) || $this->db->table('pedido')->where('idcotizacion', $id)->countAllResults()) throw new InvalidArgumentException('Esta cotización ya fue convertida o no está emitida.');
        }
        return $c;
    }

    public function marcarVenta(int $id, int $venta, int $usuario): void
    {
        $final = $this->db->table('ventas')->select('idventa, idcliente, idlocal, total, subtotal, descuento, baseimponible, montoigv')->where('idventa', $venta)->get()->getRowArray();
        $lineas = $this->db->table('detventa')->select('idproducto, cantidad, precioventa')->where('idventa', $venta)->get()->getResultArray();
        $this->db->table('cotizacion')->where('idcotizacion', $id)->update(['idestado' => $this->estadoId('A_VENTA'), 'fecha_conversion' => date('Y-m-d H:i:s')]);
        $this->evento($id, $usuario, 'CONVERSION_VENTA', 'Venta N.º ' . $venta . ' por S/ ' . $final['total'] . '.', ['venta' => $final, 'detalles' => $lineas]);
    }

    public function configurarSerie(int $linea, string $prefijo, int $inicial, int $usuario, string $nombreLinea = ''): void
    {
        if ($this->rol($usuario) !== 'Administrador') throw new InvalidArgumentException('Solo un administrador puede configurar la numeración.');
        if (!preg_match('/^[A-Z0-9]{1,15}$/D', $prefijo) || $inicial < 1 || $inicial > 999999999 || ($linea > 0 && !$this->db->table('lineas')->where('idlinea', $linea)->countAllResults()) || ($linea <= 0 && (mb_strlen(trim($nombreLinea)) < 2 || mb_strlen($nombreLinea) > 60))) throw new InvalidArgumentException('Revisa la línea, el prefijo y el número inicial.');
        $this->db->transBegin();
        try {
            if ($linea <= 0) {
                if ($this->db->table('lineas')->where('prefijo', $prefijo)->countAllResults()) throw new InvalidArgumentException('Ya existe una línea con ese prefijo. Selecciónala en la lista.');
                $this->db->table('lineas')->insert(['prefijo' => $prefijo, 'nombre' => trim($nombreLinea), 'estado' => 1]);
                $linea = (int) $this->db->insertID();
            }
            $actual = $this->db->query('SELECT * FROM cotizacion_series WHERE idlinea = ? FOR UPDATE', [$linea])->getRowArray();
            if ($actual && $actual['prefijo'] !== $prefijo && $this->db->table('cotizacion')->where('idlinea', $linea)->countAllResults()) throw new InvalidArgumentException('Esta línea ya emitió números; conserva su prefijo.');
            if ($this->db->table('cotizacion_series')->where('prefijo', $prefijo)->where('idlinea !=', $linea)->countAllResults()) throw new InvalidArgumentException('Ese prefijo ya pertenece a otra línea.');
            if ($actual) $this->db->table('cotizacion_series')->where('idserie', $actual['idserie'])->update(['prefijo' => $prefijo, 'numero_inicial' => $inicial]);
            else $this->db->table('cotizacion_series')->insert(['idlinea' => $linea, 'prefijo' => $prefijo, 'numero_inicial' => $inicial]);
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo configurar la numeración.');
            $this->db->transCommit();
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }
}
