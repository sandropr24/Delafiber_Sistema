<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class ArticuloApi extends BaseController
{
    public function buscar()
    {
        $q = $this->request->getGet('q');
        $tipo = $this->request->getGet('tipo');

        if (!is_string($q) || !is_string($tipo)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'Parámetros inválidos.']);
        }

        $q = trim($q);
        $tipo = strtoupper(trim($tipo));

        if (
            mb_strlen($q) < 2 ||
            mb_strlen($q) > 80 ||
            !in_array($tipo, ['PRODUCTO', 'SERVICIO', 'COMBO'], true)
        ) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => 'Ingrese al menos dos caracteres y un tipo válido.']);
        }

        $filas = Database::connect()->table('productos p')
            ->select('p.idproducto, p.tipo, p.nombre, p.modelo, p.codigobarras, p.idunidad, p.controlastock, p.controlaserie, p.garantiameses, p.precioventa, um.abreviatura AS unidad')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
            ->where('p.estado', 1)
            ->where('p.tipo', $tipo)
            ->groupStart()
            ->like('p.nombre', $q)
            ->orLike('p.modelo', $q)
            ->orLike('p.codigobarras', $q)
            ->groupEnd()
            ->orderBy('p.nombre', 'ASC')
            ->orderBy('p.idproducto', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['articulos' => $filas]);
    }

    public function detalle($id = null)
    {
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'ID inválido.']);
        }

        $db = Database::connect();

        $articulo = $db->table('productos p')
            ->select('p.idproducto, p.tipo, p.nombre, p.modelo, p.codigobarras, p.idunidad, p.controlastock, p.controlaserie, p.garantiameses, p.precioventa, um.abreviatura AS unidad')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
            ->where('p.idproducto', (int) $id)
            ->where('p.estado', 1)
            ->get()
            ->getRowArray();

        if (!$articulo) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Artículo no disponible.']);
        }

        $componentes = [];

        if ($articulo['tipo'] === 'COMBO') {
            $componentes = $db->table('combo_detalle cd')
                ->select('cd.idcombo, cd.idproducto, cd.cantidad, p.nombre, p.tipo, p.controlastock, p.controlaserie')
                ->join('productos p', 'p.idproducto = cd.idproducto')
                ->where('cd.idcombo', (int) $id)
                ->get()
                ->getResultArray();
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON([
                'articulo' => $articulo,
                'componentes' => $componentes
            ]);
    }
}
