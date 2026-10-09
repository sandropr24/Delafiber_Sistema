<?php

namespace App\Controllers;

use App\Models\PersonaModel;

class ClienteController extends BaseController
{
    protected $personaModel;

    public function __construct()
    {
        $this->personaModel = new PersonaModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $clientes = $db->table('personas')
            ->whereNotIn('idpersona', static function ($builder) {
                return $builder->select('idpersona')->from('usuarios');
            })
            ->orderBy('idpersona', 'DESC')
            ->get()
            ->getResultArray();

        $data = [
            'titulo'   => 'Gestión de Clientes',
            'clientes' => $clientes,
        ];

        return view('clientes/index', $data);
    }

    public function guardar()
    {
        $idPersona = $this->request->getPost('idpersona');
        $numeroDoc = trim((string) $this->request->getPost('numerodoc'));
        $tipoDoc   = $this->request->getPost('tipodoc');

        $db = \Config\Database::connect();
        $builder = $db->table('personas')->where('numerodoc', $numeroDoc);

        if (!empty($idPersona)) {
            $builder->where('idpersona !=', $idPersona);
        }

        if ($builder->countAllResults() > 0) {
            return redirect()->to(base_url('clientes'))
                             ->withInput()
                             ->with('error', 'El número de documento ya se encuentra registrado.');
        }

        $datosPersona = [
            'nombres'   => trim((string) $this->request->getPost('nombres')),
            'apellidos' => trim((string) $this->request->getPost('apellidos')),
            'tipodoc'   => $tipoDoc,
            'numerodoc' => $numeroDoc,
            'telefono'  => trim((string) $this->request->getPost('telefono')) ?: null,
            'email'     => trim((string) $this->request->getPost('email')) ?: null,
            'direccion' => trim((string) $this->request->getPost('direccion')) ?: null,
        ];

        if (!empty($idPersona)) {
            $datosPersona['idpersona'] = $idPersona;
            $this->personaModel->save($datosPersona);
            $mensaje = 'Cliente actualizado con éxito.';
        } else {
            $this->personaModel->insert($datosPersona);
            $mensaje = 'Cliente registrado con éxito.';
        }

        return redirect()->to(base_url('clientes'))->with('mensaje', $mensaje);
    }

    public function eliminar($id = null)
{
    $db = \Config\Database::connect();

    $persona = $db->table('personas')->where('idpersona', $id)->get()->getRowArray();

    if (!$persona) {
        return redirect()->to(base_url('clientes'))->with('error', 'El cliente no existe.');
    }

    $esUsuario = $db->table('usuarios')->where('idpersona', $id)->countAllResults();
    if ($esUsuario > 0) {
        return redirect()->to(base_url('clientes'))->with('error', 'No se puede eliminar: esta persona está asignada como usuario del sistema.');
    }

    if ($db->tableExists('ventas')) {
        $columnaVentas = $db->fieldExists('idcliente', 'ventas') ? 'idcliente' : ($db->fieldExists('idpersona', 'ventas') ? 'idpersona' : null);

        if ($columnaVentas !== null) {
            $tieneVentas = $db->table('ventas')->where($columnaVentas, $id)->countAllResults();
            if ($tieneVentas > 0) {
                return redirect()->to(base_url('clientes'))->with('error', 'No se puede eliminar: el cliente registra compras o comprobantes.');
            }
        }
    }

    $db->table('personas')->where('idpersona', $id)->delete();

    return redirect()->to(base_url('clientes'))->with('mensaje', 'Cliente eliminado con éxito.');
}
}