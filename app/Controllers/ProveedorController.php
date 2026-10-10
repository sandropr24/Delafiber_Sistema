<?php

namespace App\Controllers;

use App\Models\ProveedorModel;

class ProveedorController extends BaseController
{
    protected $proveedorModel;

    public function __construct()
    {
        $this->proveedorModel = model(ProveedorModel::class);
    }

    public function index()
    {
        $data = [
            'titulo'      => 'Gestión de Proveedores',
            'proveedores' => $this->proveedorModel->findAll()
        ];

        return view('proveedores/index', $data);
    }

    public function guardar()
    {
        $id = $this->request->getPost('idproveedor');

        $rules = [
            'ruc'         => 'required|exact_length[11]',
            'razonsocial' => 'required|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'El RUC debe tener exactamente 11 dígitos y la Razón Social es obligatoria.');
        }

        $datos = [
            'ruc'             => $this->request->getPost('ruc'),
            'razonsocial'     => $this->request->getPost('razonsocial'),
            'nombrecomercial' => $this->request->getPost('nombrecomercial'),
            'telefono'        => $this->request->getPost('telefono'),
            'email'           => $this->request->getPost('email'),
            'direccion'       => $this->request->getPost('direccion'),
        ];

        if (empty($id)) {
            $this->proveedorModel->insert($datos);
            $mensaje = 'Proveedor registrado correctamente.';
        } else {
            $this->proveedorModel->update($id, $datos);
            $mensaje = 'Proveedor actualizado correctamente.';
        }

        return redirect()->to('/proveedores')->with('mensaje', $mensaje);
    }

    public function eliminar($id)
    {
        $this->proveedorModel->delete($id);
        return redirect()->to('/proveedores')->with('mensaje', 'Proveedor eliminado correctamente.');
    }
}