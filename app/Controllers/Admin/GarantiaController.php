<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\GarantiaConsultaService;
use App\Services\GarantiaService;
use App\Services\GarantiaWorkflow;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class GarantiaController extends BaseController
{
    private function texto($valor): string
    {
        return is_scalar($valor) ? mb_substr(trim((string) $valor), 0, 100) : '';
    }

    public function index()
    {
        $buscar = $this->texto($this->request->getGet('q'));
        $estado = $this->texto($this->request->getGet('estado'));
        if (!isset(GarantiaWorkflow::ETIQUETAS[$estado])) $estado = '';
        $resultado = (new GarantiaConsultaService())->listar($buscar, $estado, (int) $this->request->getGet('pagina'));
        return view('garantias/index', [
            'titulo' => 'Garantías', 'buscar' => $buscar, 'estado' => $estado, 'resultado' => $resultado,
            'etiquetas' => GarantiaWorkflow::ETIQUETAS,
            'puedeGestionar' => in_array(session('rol'), ['Administrador', 'Operador'], true),
        ]);
    }

    public function crear()
    {
        $buscar = $this->texto($this->request->getGet('q'));
        $venta = max(0, (int) $this->request->getGet('venta'));
        return view('garantias/crear', [
            'titulo' => 'Registrar garantía', 'buscar' => $buscar, 'venta' => $venta,
            'articulos' => (new GarantiaConsultaService())->buscarVentas($buscar, $venta),
            'token' => bin2hex(random_bytes(32)), 'esAdmin' => session('rol') === 'Administrador',
        ]);
    }

    public function guardar()
    {
        try {
            $id = (new GarantiaService())->registrar($this->request->getPost(), (int) session('idusuario'));
            return redirect()->to(base_url('admin/garantias/detalles/' . $id))->with('mensaje', 'Caso de garantía registrado.');
        } catch (Throwable $e) {
            log_message('error', 'Registro de garantía: ' . $e->getMessage());
            $buscar = $this->texto($this->request->getPost('buscar_origen'));
            $venta = max(0, (int) $this->request->getPost('venta_origen'));
            return redirect()->to(base_url('admin/garantias/crear?' . http_build_query(['q' => $buscar, 'venta' => $venta])))
                ->withInput()->with('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo registrar la garantía. Verifica que las migraciones estén aplicadas.');
        }
    }

    public function detalles(int $id)
    {
        $consultas = new GarantiaConsultaService();
        $caso = $consultas->obtener($id);
        if (!$caso) throw PageNotFoundException::forPageNotFound('La garantía no existe.');
        return view('garantias/detalles', [
            'titulo' => 'Garantía GAR-' . $id, 'caso' => $caso, 'historial' => $consultas->historial($id),
            'etiquetas' => GarantiaWorkflow::ETIQUETAS, 'siguientes' => (new GarantiaWorkflow())->siguientes($caso['estado']),
            'proveedores' => db_connect()->table('proveedores')->select('idproveedor, razonsocial')->orderBy('razonsocial')->get()->getResultArray(),
            'puedeGestionar' => in_array(session('rol'), ['Administrador', 'Operador'], true),
        ]);
    }

    public function cambiarEstado(int $id)
    {
        try {
            (new GarantiaService())->cambiarEstado(
                $id, $this->texto($this->request->getPost('estado_actual')), $this->texto($this->request->getPost('estado')),
                $this->request->getPost('observacion'), $this->request->getPost('idproveedor'), (int) session('idusuario')
            );
            return redirect()->to(base_url('admin/garantias/detalles/' . $id))->with('mensaje', 'Estado de garantía actualizado.');
        } catch (Throwable $e) {
            log_message('error', 'Estado de garantía: ' . $e->getMessage());
            return redirect()->to(base_url('admin/garantias/detalles/' . $id))->withInput()
                ->with('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar la garantía.');
        }
    }
}
