<?php

namespace App\Controllers;

use App\Models\ProductoModel;
use App\Models\CategoriaModel;
use App\Models\MarcaModel;

class ProductoController extends BaseController
{
    protected $productoModel;
    protected $categoriaModel;
    protected $marcaModel;

    public function __construct()
    {
        $this->productoModel  = model(ProductoModel::class);
        $this->categoriaModel = model(CategoriaModel::class);
        $this->marcaModel     = model(MarcaModel::class);
    }

    public function index()
    {
        $data = [
            'titulo'     => 'Productos',
            'productos'  => $this->productoModel->obtenerConRelaciones(),
            'categorias' => $this->categoriaModel->orderBy('categoria', 'ASC')->findAll(),
            'marcas'     => $this->marcaModel->orderBy('marca', 'ASC')->findAll(),
        ];

        return view('productos/index', $data);
    }

    public function guardar()
    {
        $id = $this->request->getPost('idproducto');

        $datos = [
            'idcategoria'  => $this->request->getPost('idcategoria'),
            'idmarca'      => $this->request->getPost('idmarca'),
            'descripcion'  => trim((string) $this->request->getPost('descripcion')),
            'modelo'       => trim((string) $this->request->getPost('modelo')) ?: null,
            'codigobarras' => trim((string) $this->request->getPost('codigobarras')) ?: null,
            'precioventa'  => $this->request->getPost('precioventa'),
            'estado'       => $this->request->getPost('estado') ?? 1,
        ];

        if (!empty($id)) {
            $datos['idproducto'] = $id;
        }

        // Manejo de carga de imagen
        $archivoImagen = $this->request->getFile('imagen');
        if ($archivoImagen && $archivoImagen->isValid() && !$archivoImagen->hasMoved()) {
            // Validacion de la imagen
            $validacionesFoto = [
                'imagen' => [
                    'rules' => 'is_image[imagen]|mime_in[imagen,image/jpg,image/jpeg,image/png,image/webp]|max_size[imagen,2048]',
                    'errors' => [
                        'is_image' => 'El archivo seleccionado no es una imagen válida.',
                        'mime_in'  => 'Formato no admitido. Use JPG, PNG o WEBP.',
                        'max_size' => 'La imagen no debe superar los 2MB.',
                    ]
                ]
            ];

            if (!$this->validate($validacionesFoto)) {
                return redirect()->to(base_url('productos'))
                                 ->withInput()
                                 ->with('errors', $this->validator->getErrors());
            }

            // Si es edición y ya tenía una imagen anterior se borra
            if (!empty($id)) {
                $prodActual = $this->productoModel->find($id);
                if (!empty($prodActual['imagen'])) {
                    $rutaVieja = FCPATH . 'uploads/productos/' . $prodActual['imagen'];
                    if (is_file($rutaVieja)) {
                        unlink($rutaVieja);
                    }
                }
            }

            $nuevoNombre = $archivoImagen->getRandomName();
            $archivoImagen->move(FCPATH . 'uploads/productos', $nuevoNombre);
            $datos['imagen'] = $nuevoNombre;
        }

        if (!$this->productoModel->save($datos)) {
            return redirect()->to(base_url('productos'))
                             ->withInput()
                             ->with('errors', $this->productoModel->errors());
        }

        $mensaje = !empty($id) ? 'Producto actualizado correctamente.' : 'Producto registrado correctamente.';
        return redirect()->to(base_url('productos'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null)
    {
        $producto = $this->productoModel->find($id);

        if (!$producto) {
            return redirect()->to(base_url('productos'))->with('error', 'El producto no existe.');
        }

        $nuevoEstado = ((int) $producto['estado'] === 1) ? 0 : 1;
        $this->productoModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('productos'))->with('mensaje', 'Estado del producto actualizado.');
    }
}