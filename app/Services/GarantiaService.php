<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GarantiaService
{
    private GarantiaConsultaService $consultas;
    private GarantiaWorkflow $workflow;

    public function __construct(private ?BaseConnection $db = null, ?GarantiaConsultaService $consultas = null)
    {
        $this->db ??= db_connect();
        $this->consultas = $consultas ?? new GarantiaConsultaService($this->db);
        $this->workflow = new GarantiaWorkflow();
    }

    private function lock(string $sql): string
    {
        return $this->db->DBDriver === 'MySQLi' ? $sql . ' FOR UPDATE' : $sql;
    }

    private function actor(int $usuario): array
    {
        $actor = $this->db->query($this->lock('SELECT u.idusuario, r.rol FROM usuarios u JOIN roles r ON r.idrol = u.idrol WHERE u.idusuario = ? AND u.estado = 1'), [$usuario])->getRowArray();
        if (!$actor || !in_array($actor['rol'], ['Administrador', 'Operador'], true)) {
            throw new InvalidArgumentException('No tienes permiso para gestionar garantías.');
        }
        return $actor;
    }

    private function id($valor, bool $opcional = false): ?int
    {
        if ($opcional && ($valor === null || $valor === '')) return null;
        if (!is_scalar($valor) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $valor) || (float) $valor > 2147483647) {
            throw new InvalidArgumentException('Selecciona un artículo, serie o proveedor válido.');
        }
        return (int) $valor;
    }

    private function transaccion(callable $accion)
    {
        if (!$this->db->transBegin()) throw new RuntimeException('No se pudo iniciar la operación de garantía.');
        try {
            $resultado = $accion();
            if (!$this->db->transStatus() || !$this->db->transCommit()) throw new RuntimeException('No se pudo confirmar la garantía.');
            return $resultado;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function evento(int $id, string $estado, string $nota, int $usuario, ?int $proveedor): void
    {
        if (!$this->db->table('garantia_historial')->insert([
            'idgarantia' => $id, 'estado' => $estado, 'fecha' => date('Y-m-d H:i:s'),
            'observacion' => $nota, 'idusuario' => $usuario, 'idproveedor' => $proveedor,
        ])) throw new RuntimeException('No se pudo guardar el historial de garantía.');
    }

    public function registrar(array $datos, int $usuario): int
    {
        $detalle = $this->id($datos['iddetventa'] ?? null);
        $componente = $this->id($datos['idcomponenteventa'] ?? null, true);
        $serieId = $this->id($datos['idserie'] ?? null, true);
        $falla = $this->workflow->texto($datos['falla'] ?? '', 'descripción de la falla', 5);
        $observacion = $this->workflow->texto($datos['observacion'] ?? '', 'observación');
        $token = $datos['request_token'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new InvalidArgumentException('Recarga el formulario antes de registrar la garantía.');
        }
        return $this->transaccion(function () use ($datos, $usuario, $detalle, $componente, $serieId, $falla, $observacion, $token) {
            $actor = $this->actor($usuario);
            $anterior = $this->db->table('garantias')->where('request_token', $token)->get()->getRowArray();
            if ($anterior) {
                if ((int) $anterior['idusuario'] !== $usuario || (int) $anterior['iddetventa'] !== $detalle
                    || (int) $anterior['idcomponenteventa'] !== (int) $componente || (int) $anterior['idserie'] !== (int) $serieId) {
                    throw new InvalidArgumentException('El formulario ya se utilizó para otro caso.');
                }
                return (int) $anterior['idgarantia'];
            }
            // Serializa recepciones del mismo detalle y protege frente a anulaciones.
            $venta = $this->db->query($this->lock('SELECT v.* FROM ventas v JOIN detventa d ON d.idventa = v.idventa WHERE d.iddetventa = ?'), [$detalle])->getRowArray();
            if (!$venta || $venta['fechaanulacion'] !== null) throw new InvalidArgumentException('La venta no existe o fue anulada.');
            if (!$this->db->table('entregas')->where('idventa', $venta['idventa'])->where('estado', 'ENTREGADO')->countAllResults()) {
                throw new InvalidArgumentException('Primero debe estar confirmada la entrega de la venta.');
            }
            $articulo = $this->consultas->articulo($detalle, $componente);
            if (!$articulo || $articulo['tipo'] !== 'PRODUCTO') throw new InvalidArgumentException('Selecciona un producto físico incluido en esta venta.');
            $serie = null;
            if ($serieId) {
                $serie = $this->db->query($this->lock('SELECT * FROM series WHERE idserie = ?'), [$serieId])->getRowArray();
                if (!$serie || (int) $serie['iddetventa'] !== $detalle || (int) $serie['idproducto'] !== (int) $articulo['idproducto'] || $serie['estado'] !== 'VENDIDO') {
                    throw new InvalidArgumentException('La serie no corresponde a este artículo vendido.');
                }
            } elseif ((int) $articulo['controlaserie'] || $this->db->table('series')->where('iddetventa', $detalle)->where('idproducto', $articulo['idproducto'])->countAllResults()) {
                throw new InvalidArgumentException('Selecciona la serie del equipo vendido.');
            }
            $historico = $articulo['garantiameses'] === null;
            if ($historico) {
                if ($actor['rol'] !== 'Administrador') throw new InvalidArgumentException('Un administrador debe verificar el plazo de esta venta histórica.');
                $this->workflow->texto($observacion, 'observación de verificación del plazo histórico', 10);
                $meses = $datos['meses_verificados'] ?? '';
                if (!is_scalar($meses) || !ctype_digit((string) $meses)) throw new InvalidArgumentException('Indica los meses de garantía verificados en el comprobante original.');
                $meses = (int) $meses;
            } else $meses = (int) $articulo['garantiameses'];
            $vencimiento = $this->workflow->validarVigencia($venta['fecha'], $meses, date('Y-m-d'));
            $duplicado = $this->db->table('garantias')->where('iddetventa', $detalle)->where('estado !=', 'ENTREGADO_CLIENTE');
            if ($serieId) {
                $duplicado->where('idserie', $serieId);
            } else {
                $duplicado->where('idserie', null)->groupStart()->where('idproducto', $articulo['idproducto'])->orWhere('idproducto', null)->groupEnd();
            }
            if ($duplicado->countAllResults()) throw new InvalidArgumentException('Ya existe un caso abierto para esta serie o artículo del detalle de venta.');
            if (!$this->db->table('garantias')->insert([
                'iddetventa' => $detalle, 'idcomponenteventa' => $componente, 'idproducto' => $articulo['idproducto'],
                'idserie' => $serieId, 'producto_snapshot' => $articulo['producto'], 'serie_snapshot' => $serie['numeroserie'] ?? null,
                'fechaingreso' => date('Y-m-d H:i:s'), 'falla' => $falla, 'estado' => 'RECIBIDO', 'idproveedor' => null,
                'idusuario' => $usuario, 'observacion' => $observacion ?: null, 'garantiameses' => $meses,
                'fechavencimiento' => $vencimiento, 'plazo_verificado' => $historico ? 1 : 0, 'request_token' => $token,
            ])) throw new RuntimeException('No se pudo registrar el caso de garantía.');
            $id = (int) $this->db->insertID();
            $this->evento($id, 'RECIBIDO', $falla, $usuario, null);
            return $id;
        });
    }

    public function cambiarEstado(int $id, string $actual, string $nuevo, $nota, $proveedor, int $usuario): void
    {
        $nota = $this->workflow->texto($nota, 'observación', 5);
        $this->workflow->validarTransicion($actual, $nuevo, $nota);
        $this->transaccion(function () use ($id, $actual, $nuevo, $nota, $proveedor, $usuario) {
            $this->actor($usuario);
            $caso = $this->db->query($this->lock('SELECT * FROM garantias WHERE idgarantia = ?'), [$id])->getRowArray();
            if (!$caso || $caso['estado'] !== $actual) throw new InvalidArgumentException('El caso cambió o no existe. Recarga el detalle.');
            $this->workflow->validarTransicion($caso['estado'], $nuevo, $nota);
            $idProveedor = $caso['idproveedor'] ? (int) $caso['idproveedor'] : null;
            if ($nuevo === 'ENVIADO_PROVEEDOR') {
                $idProveedor = $this->id($proveedor);
                if (!$this->db->table('proveedores')->where('idproveedor', $idProveedor)->countAllResults()) throw new InvalidArgumentException('Selecciona un proveedor existente.');
            }
            if (!$this->db->table('garantias')->where('idgarantia', $id)->update(['estado' => $nuevo, 'idproveedor' => $idProveedor])) {
                throw new RuntimeException('No se pudo actualizar el estado.');
            }
            $this->evento($id, $nuevo, $nota, $usuario, $idProveedor);
        });
    }
}
