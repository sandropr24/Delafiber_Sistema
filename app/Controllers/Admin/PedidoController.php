<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PedidoModel;

class PedidoController extends BaseController
{
    private PedidoModel $pedidos;

    public function __construct() { $this->pedidos = model(PedidoModel::class); }

    private function autorizado(): bool
    {
        return (int) session('idusuario') > 0 && in_array(session('rol'), ['Administrador', 'Vendedor'], true);
    }

    private function denegar() { return redirect()->to(base_url('admin/dashboard'))->with('error', 'No tienes permiso para gestionar pedidos.'); }

    public function index()
    {
        if (!$this->autorizado()) return $this->denegar();
        $db = db_connect();
        $filtros = ['q' => mb_substr(trim((string) $this->request->getGet('q')), 0, 150), 'estado' => (string) $this->request->getGet('estado'), 'vendedor' => max(0, (int) $this->request->getGet('vendedor'))];
        $consulta = $db->table('pedido p')->select("p.*, e.codigo AS estado_codigo, e.estado, COALESCE(NULLIF(pe.razonsocial, ''), CONCAT_WS(' ', pe.nombres, pe.apellidos)) AS cliente, l.nombrelocal, u.nombreusuario", false)
            ->join('estadopedido e', 'e.idestado = p.idestado')->join('personas pe', 'pe.idpersona = p.idpersona')->join('locales l', 'l.idlocal = p.idlocal')->join('usuarios u', 'u.idusuario = p.idusuario');
        if ($filtros['q'] !== '') {
            $consulta->groupStart()->like('p.codigo', $filtros['q'])->orLike('pe.numerodoc', $filtros['q'])->orLike('pe.razonsocial', $filtros['q'])->orLike("CONCAT_WS(' ', pe.nombres, pe.apellidos)", $filtros['q'])->groupEnd();
        }
        if (in_array($filtros['estado'], ['PENDIENTE', 'CONFIRMADO', 'CONVERTIDO', 'CANCELADO'], true)) $consulta->where('e.codigo', $filtros['estado']);
        else $filtros['estado'] = '';
        if ($filtros['vendedor']) $consulta->where('p.idusuario', $filtros['vendedor']);
        $total = (clone $consulta)->countAllResults();
        $paginas = max(1, (int) ceil($total / 25));
        $pagina = min($paginas, max(1, (int) $this->request->getGet('pagina')));
        $pedidos = $consulta->orderBy('p.idpedido', 'DESC')->limit(25, ($pagina - 1) * 25)->get()->getResultArray();
        $resumen = $db->table('pedido p')->select('e.codigo, COUNT(*) AS cantidad')->join('estadopedido e', 'e.idestado = p.idestado')->groupBy('e.codigo')->get()->getResultArray();
        $vendedores = $db->table('usuarios')->select('idusuario, nombreusuario')->orderBy('nombreusuario')->get()->getResultArray();
        return view('pedidos/index', compact('pedidos', 'filtros', 'total', 'paginas', 'pagina', 'resumen', 'vendedores') + ['titulo' => 'Pedidos']);
    }

    public function crear() { return $this->formulario(); }
    public function editar(int $id) { return $this->formulario($id); }

    private function formulario(int $id = 0)
    {
        if (!$this->autorizado()) return $this->denegar();
        $pedido = $id ? $this->pedidos->obtener($id) : null;
        if ($id && (!$pedido || $pedido['estado_codigo'] !== 'PENDIENTE')) return redirect()->to(base_url('admin/pedidos'))->with('error', 'Solo puedes editar pedidos pendientes.');
        $db = db_connect();
        $clientes = $db->table('personas p')->select("p.idpersona, p.numerodoc, COALESCE(NULLIF(p.razonsocial, ''), CONCAT_WS(' ', p.nombres, p.apellidos)) AS nombre", false)->where('p.estado', 1)->whereNotIn('p.idpersona', static fn($b) => $b->select('idpersona')->from('usuarios'))->orderBy('nombre')->get()->getResultArray();
        $locales = $db->table('locales')->get()->getResultArray();
        $productos = $db->table('productos p')->select('p.idproducto, p.nombre, p.codigobarras, p.tipo, p.precioventa, p.controlaserie, um.abreviatura AS unidad')->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')->where('p.estado', 1)->orderBy('p.nombre')->get()->getResultArray();
        $datos = $pedido ? ['idpersona' => $pedido['idpersona'], 'idlocal' => $pedido['idlocal'], 'tipodescuento' => $pedido['tipodescuento'], 'valordescuento' => $pedido['valordescuento'], 'motivodescuento' => $pedido['motivodescuento'], 'observaciones' => $pedido['observaciones'], 'revision' => $pedido['revision'], 'detalles' => array_map(static fn($d) => ['idproducto' => $d['idproducto'], 'cantidad' => $d['cantidad'], 'precioventa' => $d['preciounitario']], $this->pedidos->detalles($id))] : [];
        $old = session()->get('_ci_old_input')['post'] ?? [];
        if (($old['origen_formulario'] ?? '') !== 'pedido' || (int) ($old['idpedido'] ?? 0) !== $id) $old = [];
        if ($old) $datos = $old;
        $token = preg_match('/^[a-f0-9]{64}$/D', (string) ($old['request_token'] ?? '')) ? $old['request_token'] : bin2hex(random_bytes(32));
        return view('pedidos/crear', compact('pedido', 'clientes', 'locales', 'productos', 'datos', 'token') + ['titulo' => $id ? 'Editar pedido' : 'Nuevo pedido', 'esAdmin' => $this->pedidos->usuarioAutorizado((int) session('idusuario')) === 'Administrador']);
    }

    public function guardar()
    {
        if (!$this->autorizado()) return $this->denegar();
        $id = max(0, (int) $this->request->getPost('idpedido'));
        try {
            $detalles = $this->request->getPost('detalles');
            if (!is_array($detalles)) throw new \InvalidArgumentException('Agrega artículos al pedido.');
            $id = $this->pedidos->guardarPedido($this->request->getPost(), $detalles, (int) session('idusuario'), $id);
            return redirect()->to(base_url('admin/pedidos/detalles/' . $id))->with('mensaje', 'Pedido guardado. No se ha cobrado ni reservado stock.')->with('pedidoGuardado', true);
        } catch (\Throwable $e) {
            log_message('error', 'Guardar pedido: ' . $e->getMessage());
            return redirect()->to(base_url('admin/pedidos/' . ($id ? 'editar/' . $id : 'crear')))->withInput()->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo guardar el pedido.');
        }
    }

    public function detalles(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        $pedido = $this->pedidos->obtener($id);
        if (!$pedido) return redirect()->to(base_url('admin/pedidos'))->with('error', 'El pedido no existe.');
        $detalles = $this->pedidos->detalles($id);
        $historial = db_connect()->table('historial_pedido h')->select('h.*, u.nombreusuario')->join('usuarios u', 'u.idusuario = h.idusuario')->where('h.idpedido', $id)->orderBy('h.idhistorial', 'DESC')->get()->getResultArray();
        return view('pedidos/detalles', compact('pedido', 'detalles', 'historial') + ['titulo' => 'Detalle del pedido']);
    }

    public function cambiarEstado(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        try {
            $this->pedidos->cambiarEstado($id, (string) $this->request->getPost('estado'), trim((string) $this->request->getPost('motivo')), (int) session('idusuario'));
            return redirect()->to(base_url('admin/pedidos/detalles/' . $id))->with('mensaje', 'Estado del pedido actualizado.');
        } catch (\Throwable $e) {
            log_message('error', 'Estado pedido: ' . $e->getMessage());
            return redirect()->to(base_url('admin/pedidos/detalles/' . $id))->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar el pedido.');
        }
    }
}
