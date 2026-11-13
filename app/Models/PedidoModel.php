<?php
namespace App\Models;

use App\Libraries\CobroVenta;
use App\Libraries\DescuentoVenta;
use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

class PedidoModel extends Model
{
    protected $table = 'pedido';
    protected $primaryKey = 'idpedido';
    protected $returnType = 'array';
    protected $allowedFields = ['idpersona', 'idlocal', 'idusuario', 'idestado', 'tipopedido', 'codigo', 'fecha', 'subtotal', 'descuento', 'total', 'tipodescuento', 'valordescuento', 'motivodescuento', 'observaciones', 'request_token', 'updated_at', 'fecha_confirmacion', 'fecha_cancelacion', 'fecha_conversion'];

    public function usuarioAutorizado(int $usuario): string
    {
        $rol = $this->db->table('usuarios u')->select('r.rol')->join('roles r', 'r.idrol = u.idrol')->where('u.idusuario', $usuario)->where('u.estado', 1)->get()->getRow('rol');
        if (!in_array($rol, ['Administrador', 'Vendedor'], true)) throw new InvalidArgumentException('No tienes permiso para gestionar pedidos.');
        return $rol;
    }

    public function estadoId(string $codigo): int
    {
        $id = (int) $this->db->table('estadopedido')->where('codigo', $codigo)->get()->getRow('idestado');
        if (!$id) throw new RuntimeException('No se ha configurado el estado del pedido.');
        return $id;
    }

    public function obtener(int $id): ?array
    {
        return $this->db->table('pedido p')->select("p.*, e.codigo AS estado_codigo, e.estado, COALESCE(NULLIF(pe.razonsocial, ''), CONCAT_WS(' ', pe.nombres, pe.apellidos)) AS cliente, pe.numerodoc, l.nombrelocal, u.nombreusuario, v.idventa, v.fechaanulacion AS venta_anulada, co.numero AS cotizacion_numero", false)
            ->join('estadopedido e', 'e.idestado = p.idestado')->join('personas pe', 'pe.idpersona = p.idpersona')->join('locales l', 'l.idlocal = p.idlocal')->join('usuarios u', 'u.idusuario = p.idusuario')->join('ventas v', 'v.idpedido = p.idpedido', 'left')->join('cotizacion co', 'co.idcotizacion = p.idcotizacion', 'left')->where('p.idpedido', $id)->get()->getRowArray();
    }

    public function detalles(int $id): array
    {
        return $this->db->table('detpedido d')->select('d.*, COALESCE(d.nombre_snapshot, p.nombre) AS nombre')->join('productos p', 'p.idproducto = d.idproducto')->where('d.idpedido', $id)->orderBy('d.iddetpedido')->get()->getResultArray();
    }

    private function producto(int $id): array
    {
        $producto = $this->db->table('productos p')->select('p.*, um.abreviatura AS unidad')->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')->where('p.idproducto', $id)->get()->getRowArray();
        if (!$producto || (int) $producto['estado'] !== 1) throw new InvalidArgumentException('Un artículo está inactivo o ya no existe. Revisa el pedido.');
        if ($producto['tipo'] === 'SERVICIO' && ($producto['controlastock'] || $producto['controlaserie'])) throw new InvalidArgumentException('Los servicios no pueden controlar stock ni series.');
        if ($producto['controlaserie'] && !$producto['controlastock']) throw new InvalidArgumentException('Un artículo con serie debe controlar stock.');
        return $producto;
    }

    private function cantidad(array $producto, $cantidad): string
    {
        if (!is_scalar($cantidad) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $cantidad) || (float) $cantidad <= 0) throw new InvalidArgumentException('Ingresa cantidades positivas con hasta dos decimales.');
        if (($producto['tipo'] !== 'PRODUCTO' || strtoupper($producto['unidad'] ?? '') !== 'M' || $producto['controlaserie']) && floor((float) $cantidad) != (float) $cantidad) throw new InvalidArgumentException($producto['nombre'] . ' requiere unidades enteras.');
        return number_format((float) $cantidad, 2, '.', '');
    }

    private function configuracion(array $producto, string $cantidad): string
    {
        $config = ['tipo' => $producto['tipo'], 'unidad' => $producto['unidad'], 'stock' => (int) $producto['controlastock'], 'serie' => (int) $producto['controlaserie'], 'componentes' => []];
        if ($producto['tipo'] === 'COMBO') {
            $filas = $this->db->table('combo_detalle')->where('idcombo', $producto['idproducto'])->orderBy('idproducto')->get()->getResultArray();
            if (!$filas) throw new InvalidArgumentException('El combo no tiene componentes.');
            foreach ($filas as $fila) {
                $componente = $this->producto((int) $fila['idproducto']);
                if ($componente['tipo'] === 'COMBO') throw new InvalidArgumentException('Un combo no puede contener otro combo.');
                $this->cantidad($componente, number_format(round((float) $cantidad * (float) $fila['cantidad'], 2), 2, '.', ''));
                $config['componentes'][] = ['id' => (int) $fila['idproducto'], 'cantidad' => number_format((float) $fila['cantidad'], 2, '.', ''), 'tipo' => $componente['tipo'], 'unidad' => $componente['unidad'], 'stock' => (int) $componente['controlastock'], 'serie' => (int) $componente['controlaserie']];
            }
        }
        return json_encode($config, JSON_THROW_ON_ERROR);
    }

    private function preparar(array $detalles, string $rol): array
    {
        if (!$detalles || count($detalles) > 200) throw new InvalidArgumentException('Agrega entre 1 y 200 artículos.');
        $filas = [];
        foreach ($detalles as $detalle) {
            if (!is_array($detalle)) throw new InvalidArgumentException('Detalle de pedido inválido.');
            $id = (int) ($detalle['idproducto'] ?? 0);
            if (isset($filas[$id])) throw new InvalidArgumentException('Agrupa la cantidad del artículo repetido en una sola fila.');
            $producto = $this->producto($id);
            $cantidad = $this->cantidad($producto, $detalle['cantidad'] ?? '');
            $precio = CobroVenta::centimos($detalle['precioventa'] ?? '');
            if ($precio <= 0) throw new InvalidArgumentException('El precio debe ser mayor que cero.');
            if ($precio < CobroVenta::centimos($producto['precioventa']) && $rol !== 'Administrador') throw new InvalidArgumentException('Solo un administrador puede reducir el precio de catálogo.');
            $filas[$id] = ['idproducto' => $id, 'cantidad' => $cantidad, 'precioventa' => number_format($precio / 100, 2, '.', ''), 'nombre_snapshot' => $producto['nombre'], 'config_snapshot' => $this->configuracion($producto, $cantidad)];
        }
        return array_values($filas);
    }

    public function validarArticulos(array $detalles, string $rol): array
    {
        return $this->preparar($detalles, $rol);
    }

    public function historial(int $id, int $usuario, string $evento, ?string $anterior, string $nuevo, ?string $motivo = null): void
    {
        if (!$this->db->table('historial_pedido')->insert(['idpedido' => $id, 'idusuario' => $usuario, 'evento' => $evento, 'estado_anterior' => $anterior, 'estado_nuevo' => $nuevo, 'motivo' => $motivo, 'fecha' => date('Y-m-d H:i:s')])) throw new RuntimeException('No se pudo guardar el historial del pedido.');
    }

    public function guardarPedido(array $datos, array $detalles, int $usuario, int $id = 0, ?int $idCotizacion = null): int
    {
        $rol = $this->usuarioAutorizado($usuario);
        $token = (string) ($datos['request_token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new InvalidArgumentException('Recarga el formulario para guardar el pedido.');
        $this->db->transBegin();
        try {
            $this->db->query('SELECT idusuario FROM usuarios WHERE idusuario = ? FOR UPDATE', [$usuario]);
            if (!$id) {
                $previo = $this->db->table('pedido')->where('request_token', $token)->get()->getRowArray();
                if ($previo) {
                    if ((int) $previo['idusuario'] !== $usuario) throw new InvalidArgumentException('La solicitud pertenece a otro usuario.');
                    if ($idCotizacion !== null && (int) ($previo['idcotizacion'] ?? 0) !== $idCotizacion) throw new InvalidArgumentException('La solicitud pertenece a otra cotización.');
                    $this->db->transCommit();
                    return (int) $previo['idpedido'];
                }
            }
            $actual = $id ? $this->bloquear($id) : null;
            $cotizacionOrigen = null;
            if ($idCotizacion !== null) {
                if ($id) throw new InvalidArgumentException('No se puede cambiar el origen de un pedido existente.');
                $cotizacionOrigen = model(CotizacionModel::class)->validarParaPedido($idCotizacion);
            } elseif (!empty($actual['idcotizacion'])) {
                $cotizacionOrigen = $this->db->table('cotizacion')->where('idcotizacion', $actual['idcotizacion'])->get()->getRowArray();
            }
            if ($actual && $actual['estado_codigo'] !== 'PENDIENTE') throw new InvalidArgumentException('Solo se pueden editar pedidos pendientes.');
            if ($actual && (int) ($datos['revision'] ?? 0) !== (int) $actual['revision']) throw new InvalidArgumentException('El pedido cambió en otra ventana. Recarga antes de editarlo.');
            $cliente = $this->db->table('personas')->where('idpersona', (int) ($datos['idpersona'] ?? 0))->where('estado', 1)->get()->getRowArray();
            if (!$cliente || $this->db->table('usuarios')->where('idpersona', $cliente['idpersona'])->countAllResults()) throw new InvalidArgumentException('Selecciona un cliente activo del catálogo.');
            if (!$this->db->table('locales')->where('idlocal', (int) ($datos['idlocal'] ?? 0))->countAllResults()) throw new InvalidArgumentException('Selecciona un local válido.');
            if (mb_strlen((string) ($datos['observaciones'] ?? '')) > 2000) throw new InvalidArgumentException('Las observaciones no pueden superar 2000 caracteres.');
            $filas = $this->preparar($detalles, $rol);
            $importes = DescuentoVenta::calcular($filas, (string) ($datos['tipodescuento'] ?? 'NINGUNO'), $datos['valordescuento'] ?? '0', (string) ($datos['motivodescuento'] ?? ''));
            if ($importes['tipodescuento'] !== 'NINGUNO' && $rol !== 'Administrador') throw new InvalidArgumentException('Solo un administrador puede aplicar descuentos.');
            $guardar = $importes + ['idpersona' => $cliente['idpersona'], 'idlocal' => (int) $datos['idlocal'], 'observaciones' => trim((string) ($datos['observaciones'] ?? '')) ?: null, 'updated_at' => date('Y-m-d H:i:s')];
            if ($cotizacionOrigen) $guardar += \App\Libraries\ImportesCotizacion::desglosar($importes['total'], $cotizacionOrigen['porcentajeigv']) + ['porcentajeigv' => $cotizacionOrigen['porcentajeigv'], 'tipocambio' => $cotizacionOrigen['tipocambio']];
            if ($id) {
                $guardar['revision'] = (int) $actual['revision'] + 1;
                $this->db->table('pedido')->where('idpedido', $id)->update($guardar);
                $this->db->table('detpedido')->where('idpedido', $id)->delete();
            } else {
                $this->db->table('pedido')->insert($guardar + ['idusuario' => $usuario, 'idcotizacion' => $idCotizacion, 'tipopedido' => $idCotizacion ? 'COTIZACION' : 'DIRECTO', 'idestado' => $this->estadoId('PENDIENTE'), 'fecha' => date('Y-m-d H:i:s'), 'request_token' => $token]);
                $id = (int) $this->db->insertID();
                $this->db->table('pedido')->where('idpedido', $id)->update(['codigo' => 'PED-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
            }
            foreach ($filas as $fila) {
                $fila['preciounitario'] = $fila['precioventa'];
                unset($fila['precioventa']);
                $this->db->table('detpedido')->insert($fila + ['idpedido' => $id]);
            }
            $this->historial($id, $usuario, $actual ? 'EDICION' : 'CREACION', $actual ? 'PENDIENTE' : null, 'PENDIENTE');
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo guardar el pedido.');
            $this->db->transCommit();
            return $id;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    private function bloquear(int $id): array
    {
        $pedido = $this->db->query('SELECT * FROM pedido WHERE idpedido = ? FOR UPDATE', [$id])->getRowArray();
        if (!$pedido) throw new InvalidArgumentException('El pedido no existe.');
        $pedido['estado_codigo'] = $this->db->table('estadopedido')->where('idestado', $pedido['idestado'])->get()->getRow('codigo');
        return $pedido;
    }

    public function cambiarEstado(int $id, string $nuevo, string $motivo, int $usuario): void
    {
        $rol = $this->usuarioAutorizado($usuario);
        $this->db->transBegin();
        try {
            $pedido = $this->bloquear($id);
            $anterior = $pedido['estado_codigo'];
            if (($nuevo === 'CONFIRMADO' && $anterior !== 'PENDIENTE') || ($nuevo === 'CANCELADO' && !in_array($anterior, ['PENDIENTE', 'CONFIRMADO'], true)) || !in_array($nuevo, ['CONFIRMADO', 'CANCELADO'], true)) throw new InvalidArgumentException('Este cambio de estado no está permitido.');
            if ($nuevo === 'CANCELADO' && (mb_strlen(trim($motivo)) < 5 || mb_strlen($motivo) > 300)) throw new InvalidArgumentException('Indica un motivo de cancelación de entre 5 y 300 caracteres.');
            if ($nuevo === 'CONFIRMADO') {
                if (!$this->db->table('personas')->where('idpersona', $pedido['idpersona'])->where('estado', 1)->countAllResults()) throw new InvalidArgumentException('El cliente ya no está activo.');
                $lineas = array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $this->detalles($id));
                $validadas = $this->preparar($lineas, $rol);
                foreach ($validadas as $fila) $this->db->table('detpedido')->where('idpedido', $id)->where('idproducto', $fila['idproducto'])->update(['config_snapshot' => $fila['config_snapshot'], 'nombre_snapshot' => $fila['nombre_snapshot']]);
                if ($pedido['tipodescuento'] !== 'NINGUNO' && $rol !== 'Administrador') throw new InvalidArgumentException('Un administrador debe confirmar este pedido con descuento.');
            }
            $this->db->table('pedido')->where('idpedido', $id)->update(['idestado' => $this->estadoId($nuevo), $nuevo === 'CONFIRMADO' ? 'fecha_confirmacion' : 'fecha_cancelacion' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $this->historial($id, $usuario, $nuevo === 'CONFIRMADO' ? 'CONFIRMACION' : 'CANCELACION', $anterior, $nuevo, trim($motivo) ?: null);
            if (!$this->db->transStatus()) throw new RuntimeException('No se pudo actualizar el pedido.');
            $this->db->transCommit();
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    // Se ejecuta dentro de la transacción de VentaModel; el bloqueo cubre dos vendedores concurrentes.
    public function validarConversion(array $venta, array $detalles): array
    {
        $this->usuarioAutorizado((int) $venta['idvendedor']);
        $pedido = $this->bloquear((int) $venta['idpedido']);
        if ($pedido['estado_codigo'] !== 'CONFIRMADO' || $this->db->table('ventas')->where('idpedido', $pedido['idpedido'])->countAllResults()) throw new InvalidArgumentException('El pedido ya fue convertido o no está confirmado.');
        $recibidas = [];
        foreach ($detalles as $fila) {
            $id = (int) ($fila['idproducto'] ?? 0);
            if (isset($recibidas[$id])) throw new InvalidArgumentException('La venta no admite artículos duplicados.');
            $recibidas[$id] = true;
        }
        return $pedido;
    }

    public function marcarConvertido(int $id, int $venta, int $usuario): void
    {
        $cambios = $this->cambiosDeVenta($id, $venta);
        if ($cambios) {
            if (!$this->db->table('historial_pedido')->insert([
                'idpedido' => $id, 'idusuario' => $usuario, 'evento' => 'AJUSTES_VENTA',
                'estado_anterior' => 'CONFIRMADO', 'estado_nuevo' => 'CONFIRMADO',
                'motivo' => 'Cambios al registrar la venta N.º ' . $venta . '. El pedido original se conserva.',
                'cambios' => json_encode($cambios, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'fecha' => date('Y-m-d H:i:s'),
            ])) throw new RuntimeException('No se pudieron registrar los ajustes de la venta.');
        }
        $this->db->table('pedido')->where('idpedido', $id)->update(['idestado' => $this->estadoId('CONVERTIDO'), 'fecha_conversion' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->historial($id, $usuario, 'CONVERSION', 'CONFIRMADO', 'CONVERTIDO', 'Venta N.º ' . $venta . '.');
    }

    private function cambiosDeVenta(int $id, int $idVenta): array
    {
        $pedido = $this->db->table('pedido')->where('idpedido', $id)->get()->getRowArray();
        $venta = $this->db->table('ventas')->where('idventa', $idVenta)->get()->getRowArray();
        if (!$pedido || !$venta) throw new RuntimeException('No se pudo comparar la venta con su pedido.');
        $cambios = [];
        $agregar = static function (string $campo, $antes, $despues) use (&$cambios): void {
            if ((string) $antes !== (string) $despues) $cambios[] = ['campo' => $campo, 'antes' => (string) $antes, 'despues' => (string) $despues];
        };
        if ((int) $pedido['idpersona'] !== (int) $venta['idcliente']) {
            $nombre = fn($persona) => $this->db->table('personas')->select("COALESCE(NULLIF(razonsocial, ''), CONCAT_WS(' ', nombres, apellidos)) AS nombre", false)->where('idpersona', $persona)->get()->getRow('nombre');
            $agregar('Cliente', $nombre($pedido['idpersona']) . ' (#' . $pedido['idpersona'] . ')', $nombre($venta['idcliente']) . ' (#' . $venta['idcliente'] . ')');
        }
        if ((int) $pedido['idlocal'] !== (int) $venta['idlocal']) {
            $local = fn($idLocal) => $this->db->table('locales')->where('idlocal', $idLocal)->get()->getRow('nombrelocal');
            $agregar('Local', $local($pedido['idlocal']) . ' (#' . $pedido['idlocal'] . ')', $local($venta['idlocal']) . ' (#' . $venta['idlocal'] . ')');
        }
        $antes = array_column($this->detalles($id), null, 'idproducto');
        $despues = array_column($this->db->table('detventa d')->select('d.*, p.nombre')->join('productos p', 'p.idproducto = d.idproducto')->where('d.idventa', $idVenta)->get()->getResultArray(), null, 'idproducto');
        $importe = static fn($valor) => 'S/ ' . number_format((float) $valor, 2, '.', '');
        foreach (array_unique(array_merge(array_keys($antes), array_keys($despues))) as $producto) {
            $original = $antes[$producto] ?? null;
            $final = $despues[$producto] ?? null;
            $nombre = $original['nombre'] ?? $final['nombre'];
            if (!$original || !$final) {
                $agregar('Artículo: ' . $nombre, $original ? $original['cantidad'] . ' × ' . $importe($original['preciounitario']) : 'No incluido', $final ? $final['cantidad'] . ' × ' . $importe($final['precioventa']) : 'Retirado');
                continue;
            }
            $agregar('Cantidad: ' . $nombre, $original['cantidad'], $final['cantidad']);
            $agregar('Precio: ' . $nombre, $importe($original['preciounitario']), $importe($final['precioventa']));
            $config = json_decode($original['config_snapshot'] ?? 'null', true);
            $componentesOriginales = [];
            foreach ($config['componentes'] ?? [] as $componente) $componentesOriginales[(int) $componente['id']] = number_format((float) $componente['cantidad'], 2, '.', '');
            $componentesFinales = [];
            foreach ($this->db->table('venta_combo_componentes')->where('iddetventa', $final['iddetventa'])->get()->getResultArray() as $componente) {
                $componentesFinales[(int) $componente['idproducto']] = number_format((float) $componente['cantidad'] / (float) $final['cantidad'], 2, '.', '');
            }
            ksort($componentesOriginales); ksort($componentesFinales);
            if ($componentesOriginales !== $componentesFinales) {
                $describir = function (array $componentes): string {
                    $partes = [];
                    foreach ($componentes as $pid => $cantidad) {
                        $producto = $this->db->table('productos')->where('idproducto', $pid)->get()->getRow('nombre');
                        $partes[] = $cantidad . ' × ' . $producto;
                    }
                    return $partes ? implode('; ', $partes) : 'Sin componentes';
                };
                $agregar('Composición: ' . $nombre, $describir($componentesOriginales), $describir($componentesFinales));
            }
        }
        $tipos = ['NINGUNO' => 'Sin descuento', 'MONTO' => 'Monto', 'PORCENTAJE' => 'Porcentaje'];
        $agregar('Tipo de descuento', $tipos[$pedido['tipodescuento']] ?? $pedido['tipodescuento'], $tipos[$venta['tipodescuento']] ?? $venta['tipodescuento']);
        $agregar('Valor del descuento', $pedido['valordescuento'], $venta['valordescuento']);
        $agregar('Motivo del descuento', $pedido['motivodescuento'], $venta['motivodescuento']);
        foreach (['subtotal' => 'Subtotal', 'descuento' => 'Descuento aplicado', 'total' => 'Total'] as $campo => $etiqueta) $agregar($etiqueta, $importe($pedido[$campo]), $importe($venta[$campo]));
        return $cambios;
    }
}