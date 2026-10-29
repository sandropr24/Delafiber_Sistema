<?php

namespace App\Controllers;

use App\Models\EntregaModel;
use App\Models\UsuarioModel;

class EntregaController extends BaseController
{
    protected $entregaModel;
    protected $usuarioModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->entregaModel = model(EntregaModel::class);
        $this->usuarioModel = model(UsuarioModel::class);
    }

    public function index()
    {
        $data = [
            'titulo'   => 'Gestión de Entregas y Logística de Campo',
            'entregas' => $this->entregaModel->obtenerEntregasFiltradas(),
            'tecnicos' => $this->usuarioModel->where('estado', 1)->findAll(),
        ];

        return view('entregas/index', $data);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('entregas'));
        }

        $estado    = $this->request->getGet('estado');
        $idtecnico = $this->request->getGet('idtecnico');
        $q         = $this->request->getGet('q');

        $resultado = $this->entregaModel->obtenerEntregasFiltradas($estado, $idtecnico, $q);

        return $this->response->setJSON($resultado);
    }

    public function cambiar_estado()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Petición no válida']);
        }

        $identrega = $this->request->getPost('identrega');
        $nuevoEstado = $this->request->getPost('estado');
        $idtecnico   = $this->request->getPost('idtecnico');

        $entrega = $this->entregaModel->find($identrega);
        if (!$entrega) {
            return $this->response->setJSON(['success' => false, 'message' => 'Entrega no encontrada']);
        }

        $dataUpdate = [
            'estado'    => $nuevoEstado,
            'idtecnico' => !empty($idtecnico) ? $idtecnico : $entrega['idtecnico']
        ];

        if ($nuevoEstado === 'EN_CAMINO' && empty($entrega['fechaencamino'])) {
            $dataUpdate['fechaencamino'] = date('Y-m-d H:i:s');
        } elseif ($nuevoEstado === 'ENTREGADO' && empty($entrega['fechaentrega'])) {
            $dataUpdate['fechaentrega'] = date('Y-m-d H:i:s');
        }

        if ($this->entregaModel->update($identrega, $dataUpdate)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Estado actualizado correctamente']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo actualizar el estado']);
    }
}