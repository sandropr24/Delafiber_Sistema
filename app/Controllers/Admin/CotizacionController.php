<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CotizacionModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class CotizacionController extends BaseController
{
    private CotizacionModel $cotizaciones;
    public function __construct() { $this->cotizaciones = model(CotizacionModel::class); }
    private function autorizado(): bool { return (int) session('idusuario') > 0 && in_array(session('rol'), ['Administrador', 'Vendedor', 'Operador'], true); }
    private function denegar() { return redirect()->to(base_url('admin/dashboard'))->with('error', 'No tienes permiso para gestionar cotizaciones.'); }

    public function index()
    {
        if (!$this->autorizado()) return $this->denegar();
        $db = db_connect();
        $filtros = ['q' => mb_substr(trim((string) $this->request->getGet('q')), 0, 150), 'estado' => (string) $this->request->getGet('estado')];
        $consulta = $db->table('cotizacion c')->select("c.*, e.codigo AS estado_codigo, e.estado, l.nombre AS linea, COALESCE(NULLIF(p.razonsocial, ''), CONCAT_WS(' ', p.nombres, p.apellidos)) AS cliente", false)->join('estadocotizacion e', 'e.idestado = c.idestado')->join('lineas l', 'l.idlinea = c.idlinea')->join('personas p', 'p.idpersona = c.idpersona');
        if ($filtros['q'] !== '') $consulta->groupStart()->like('c.numero', $filtros['q'])->orLike('p.numerodoc', $filtros['q'])->orLike('p.razonsocial', $filtros['q'])->orLike("CONCAT_WS(' ', p.nombres, p.apellidos)", $filtros['q'])->groupEnd();
        if ($filtros['estado'] === 'VENCIDA') $consulta->whereIn('e.codigo', ['BORRADOR', 'EMITIDA', 'ACEPTADA'])->where('c.fechavencimiento <', date('Y-m-d'));
        elseif (in_array($filtros['estado'], ['BORRADOR', 'EMITIDA', 'ACEPTADA', 'RECHAZADA', 'A_PEDIDO', 'A_VENTA'], true)) {
            $consulta->where('e.codigo', $filtros['estado']);
            if (in_array($filtros['estado'], ['BORRADOR', 'EMITIDA', 'ACEPTADA'], true)) $consulta->where('c.fechavencimiento >=', date('Y-m-d'));
        } else $filtros['estado'] = '';
        $total = (clone $consulta)->countAllResults(); $paginas = max(1, (int) ceil($total / 25));
        $pagina = min($paginas, max(1, (int) $this->request->getGet('pagina')));
        $cotizaciones = $consulta->orderBy('c.idcotizacion', 'DESC')->limit(25, ($pagina - 1) * 25)->get()->getResultArray();
        return view('cotizaciones/index', compact('cotizaciones', 'filtros', 'total', 'pagina', 'paginas') + ['titulo' => 'Cotizaciones', 'esAdmin' => session('rol') === 'Administrador']);
    }

    public function crear() { return $this->formulario(); }
    public function editar(int $id) { return $this->formulario($id); }
    private function formulario(int $id = 0)
    {
        if (!$this->autorizado()) return $this->denegar();
        $cotizacion = $id ? $this->cotizaciones->obtener($id) : null;
        if ($id && (!$cotizacion || !in_array($cotizacion['estado_codigo'], ['BORRADOR', 'EMITIDA'], true))) return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La cotización no está disponible para editar.');
        $db = db_connect();
        $clientes = $db->table('personas p')->select("p.idpersona, p.numerodoc, COALESCE(NULLIF(p.razonsocial, ''), CONCAT_WS(' ', p.nombres, p.apellidos)) AS nombre", false)->where('p.estado', 1)->whereNotIn('p.idpersona', static fn($b) => $b->select('idpersona')->from('usuarios'))->orderBy('nombre')->get()->getResultArray();
        $locales = $db->table('locales')->get()->getResultArray();
        $lineas = $db->table('lineas l')->select('l.*, s.prefijo AS prefijo_cotizacion, s.numero_inicial')->join('cotizacion_series s', 's.idlinea = l.idlinea')->where('l.estado', 1)->orderBy('l.nombre')->get()->getResultArray();
        $productos = $db->table('productos p')->select('p.idproducto, p.nombre, p.tipo, p.codigobarras, p.precioventa, p.controlaserie, u.abreviatura AS unidad')->join('unidadesmedida u', 'u.idunidad = p.idunidad', 'left')->where('p.estado', 1)->orderBy('p.nombre')->get()->getResultArray();
        $cambio = $db->table('tipocambio')->orderBy('fecha', 'DESC')->get()->getRowArray();
        $datos = $cotizacion ?? ['idlocal' => session('idlocal'), 'fechavencimiento' => date('Y-m-d', strtotime('+7 days')), 'porcentajeigv' => '18.00', 'tipocambio' => $cambio['venta'] ?? '1.0000', 'moneda_origen' => 'PEN', 'precios_incluyen_igv' => 1, 'condiciones' => 'Precios finales en soles. Disponibilidad sujeta a confirmación al comprar.'];
        $datos['detalles'] = $id ? $this->cotizaciones->detalles($id) : [];
        $old = session()->get('_ci_old_input')['post'] ?? [];
        if (($old['origen_formulario'] ?? '') === 'cotizacion' && (int) ($old['idcotizacion'] ?? 0) === $id) $datos = $old;
        $token = preg_match('/^[a-f0-9]{64}$/D', (string) ($datos['request_token'] ?? '')) && $old ? $datos['request_token'] : bin2hex(random_bytes(32));
        return view('cotizaciones/crear', compact('cotizacion', 'datos', 'token', 'clientes', 'locales', 'lineas', 'productos', 'cambio') + ['titulo' => $id ? 'Editar cotización' : 'Nueva cotización']);
    }

    public function guardar()
    {
        if (!$this->autorizado()) return $this->denegar();
        $id = max(0, (int) $this->request->getPost('idcotizacion'));
        try {
            $detalles = $this->request->getPost('detalles');
            if (!is_array($detalles)) throw new \InvalidArgumentException('Agrega artículos a la cotización.');
            $id = $this->cotizaciones->guardarCotizacion($this->request->getPost(), $detalles, (int) session('idusuario'), $id);
            return redirect()->to(base_url('admin/cotizaciones/detalles/' . $id))->with('mensaje', 'Cotización guardada. Puedes revisar el PDF y emitirla.')->with('cotizacionGuardada', true);
        } catch (\Throwable $e) {
            log_message('error', 'Guardar cotización: ' . $e->getMessage());
            return redirect()->to(base_url('admin/cotizaciones/' . ($id ? 'editar/' . $id : 'crear')))->withInput()->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo guardar la cotización.');
        }
    }

    public function detalles(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        $cotizacion = $this->cotizaciones->obtener($id);
        if (!$cotizacion) return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La cotización no existe.');
        $detalles = $this->cotizaciones->detalles($id);
        $historial = db_connect()->table('historial_cotizacion h')->select('h.*, u.nombreusuario')->join('usuarios u', 'u.idusuario = h.idusuario')->where('h.idcotizacion', $id)->orderBy('h.idhistorial', 'DESC')->get()->getResultArray();
        return view('cotizaciones/detalles', compact('cotizacion', 'detalles', 'historial') + ['titulo' => 'Detalle de cotización', 'puedeConvertir' => in_array(session('rol'), ['Administrador', 'Vendedor'], true)]);
    }

    public function cambiarEstado(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        try { $this->cotizaciones->cambiarEstado($id, (string) $this->request->getPost('estado'), (int) session('idusuario')); return redirect()->to(base_url('admin/cotizaciones/detalles/' . $id))->with('mensaje', 'Estado actualizado.'); }
        catch (\Throwable $e) { log_message('error', 'Estado cotización: ' . $e->getMessage()); return redirect()->to(base_url('admin/cotizaciones/detalles/' . $id))->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar el estado.'); }
    }

    public function convertirPedido(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        try { $pedido = $this->cotizaciones->convertirAPedido($id, (int) session('idusuario')); return redirect()->to(base_url('admin/pedidos/detalles/' . $pedido))->with('mensaje', 'Pedido creado desde la cotización, sin cobrar ni reservar stock.'); }
        catch (\Throwable $e) { log_message('error', 'Convertir cotización: ' . $e->getMessage()); return redirect()->to(base_url('admin/cotizaciones/detalles/' . $id))->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo convertir la cotización.'); }
    }

    public function series()
    {
        if (!$this->autorizado() || $this->cotizaciones->rol((int) session('idusuario')) !== 'Administrador') return $this->denegar();
        $db = db_connect();
        $series = $db->table('lineas l')->select('l.*, s.prefijo AS prefijo_cotizacion, s.numero_inicial')->join('cotizacion_series s', 's.idlinea = l.idlinea', 'left')->orderBy('l.nombre')->get()->getResultArray();
        return view('cotizaciones/series', compact('series') + ['titulo' => 'Numeración de cotizaciones']);
    }

    public function guardarSerie()
    {
        if (!$this->autorizado()) return $this->denegar();
        try { $this->cotizaciones->configurarSerie((int) $this->request->getPost('idlinea'), strtoupper(trim((string) $this->request->getPost('prefijo'))), (int) $this->request->getPost('numero_inicial'), (int) session('idusuario'), trim((string) $this->request->getPost('nombre_linea'))); return redirect()->to(base_url('admin/cotizaciones/series'))->with('mensaje', 'Numeración configurada. Los números ya asignados se conservan.'); }
        catch (\Throwable $e) { log_message('error', 'Serie cotización: ' . $e->getMessage()); return redirect()->to(base_url('admin/cotizaciones/series'))->with('error', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'No se pudo configurar la serie.'); }
    }

    public function pdf(int $id)
    {
        if (!$this->autorizado()) return $this->denegar();
        $cotizacion = $this->cotizaciones->obtener($id);
        if (!$cotizacion) return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La cotización no existe.');
        $detalles = $this->cotizaciones->detalles($id);
        $cliente = json_decode($cotizacion['cliente_snapshot'] ?? 'null', true) ?: ['nombre' => $cotizacion['cliente']];
        $empresa = array_column(db_connect()->table('configuracion')->whereIn('clave', ['empresa_ruc', 'empresa_direccion', 'empresa_telefono', 'empresa_email'])->get()->getResultArray(), 'valor', 'clave');
        $ruta = FCPATH . 'img/Delafiber.png'; $logo = is_readable($ruta) ? 'data:image/png;base64,' . base64_encode(file_get_contents($ruta)) : null;
        $options = new Options(); $options->set('isRemoteEnabled', false); $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options); $pdf->loadHtml(view('cotizaciones/pdf', compact('cotizacion', 'detalles', 'cliente', 'empresa', 'logo')), 'UTF-8'); $pdf->setPaper('A4', 'portrait'); $pdf->render();
        $disposicion = $this->request->getGet('descargar') === '1' ? 'attachment' : 'inline';
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Cache-Control', 'private, no-store')->setHeader('Content-Disposition', $disposicion . '; filename="' . $cotizacion['numero'] . '.pdf"')->setBody($pdf->output());
    }
}
