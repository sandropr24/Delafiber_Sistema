<?php

namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Models\MarcaModel;
use App\Models\ProductoModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProductoController extends BaseController
{
    private const TIPOS = ['PRODUCTO', 'SERVICIO', 'COMBO'];

    private const CAMPOS_FORM = [
        'idproducto', 'tipo', 'idcategoria', 'idmarca', 'nombre', 'descripcion', 'modelo',
        'codigobarras', 'idunidad', 'preciocosto', 'monedacosto', 'precioventa',
        'garantiameses', 'controlastock', 'controlaserie', 'estado',
    ];

    protected $productoModel;
    protected $categoriaModel;
    protected $marcaModel;

    public function __construct()
    {
        $this->productoModel  = model(ProductoModel::class);
        $this->categoriaModel = model(CategoriaModel::class);
        $this->marcaModel     = model(MarcaModel::class);
    }

    public function index(): string
    {
        $todasCategorias = $this->categoriaModel->obtenerCategoriasConPadre();

        $data = [
            'titulo'           => 'Productos',
            'productos'        => $this->productoModel->buscarConRelaciones(),
            'categoriasFiltro' => $todasCategorias,
            'categorias'       => array_values(array_filter($todasCategorias, static fn ($c) => (int) $c['estado'] === 1)),
            'marcas'           => array_values(array_filter(
                $this->marcaModel->orderBy('marca', 'ASC')->findAll(),
                static fn ($m) => (int) ($m['estado'] ?? 1) === 1
            )),
            'unidades'         => $this->obtenerUnidades(),
        ];

        return view('productos/index', $data);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('productos'));
        }

        $q         = trim((string) $this->request->getGet('q'));
        $idCat     = (int) $this->request->getGet('categoria');
        $tipo      = strtoupper((string) $this->request->getGet('tipo'));
        $estadoGet = $this->request->getGet('estado');

        $tipo   = in_array($tipo, self::TIPOS, true) ? $tipo : null;
        $estado = in_array($estadoGet, ['0', '1'], true) ? (int) $estadoGet : null;

        $ids = [];
        if ($idCat) {
            $ids   = array_column($this->categoriaModel->select('idcategoria')->where('idcategoriapadre', $idCat)->findAll(), 'idcategoria');
            $ids[] = $idCat;
        }

        return view('productos/_filas', [
            'productos' => $this->productoModel->buscarConRelaciones($q, $ids, $tipo, $estado),
        ]);
    }

    public function guardar(): RedirectResponse
    {
        $id     = (int) $this->request->getPost('idproducto');
        $actual = $id ? $this->productoModel->find($id) : null;

        if ($id && !$actual) {
            return redirect()->to(base_url('productos'))->with('error', 'El producto no existe.');
        }

        $tipo        = strtoupper((string) $this->request->getPost('tipo'));
        $esProducto  = $tipo === 'PRODUCTO';
        $idCategoria = (int) $this->request->getPost('idcategoria') ?: null;
        $idMarca     = (int) $this->request->getPost('idmarca') ?: null;
        $idUnidad    = (int) $this->request->getPost('idunidad') ?: null;
        $monedaPost  = strtoupper((string) $this->request->getPost('monedacosto'));
        $estadoPost  = $this->request->getPost('estado');
        $errores     = [];

        if (!in_array($tipo, self::TIPOS, true)) {
            $errores[] = 'Seleccione un tipo válido (producto, servicio o combo).';
        }

        if ($tipo === 'SERVICIO') {
            $idMarca = null;
        }

        $categoria = $idCategoria ? $this->categoriaModel->find($idCategoria) : null;
        if (!$categoria) {
            $errores[] = 'Seleccione una categoría válida.';
        } elseif ((int) $categoria['estado'] !== 1 && (int) ($actual['idcategoria'] ?? 0) !== $idCategoria) {
            $errores[] = 'La categoría seleccionada está inactiva.';
        }

        if ($tipo !== 'SERVICIO') {
            $marca = $idMarca ? $this->marcaModel->find($idMarca) : null;
            if (!$marca) {
                $errores[] = 'La marca es obligatoria (solo los servicios pueden no tener marca).';
            } elseif ((int) ($marca['estado'] ?? 1) !== 1 && (int) ($actual['idmarca'] ?? 0) !== $idMarca) {
                $errores[] = 'La marca seleccionada está inactiva.';
            }
        }

        if (!$idUnidad || db_connect()->table('unidadesmedida')->where('idunidad', $idUnidad)->countAllResults() === 0) {
            $errores[] = 'Seleccione una unidad de medida válida.';
        }

        $datos = [
            'idcategoria'   => $idCategoria,
            'tipo'          => $tipo,
            'nombre'        => trim((string) $this->request->getPost('nombre')),
            'descripcion'   => trim((string) $this->request->getPost('descripcion')) ?: null,
            'idmarca'       => $idMarca,
            'modelo'        => $tipo === 'SERVICIO' ? null : (trim((string) $this->request->getPost('modelo')) ?: null),
            'codigobarras'  => $tipo === 'SERVICIO' ? null : (trim((string) $this->request->getPost('codigobarras')) ?: null),
            'idunidad'      => $idUnidad,
            'controlastock' => ($esProducto && $this->request->getPost('controlastock')) ? 1 : 0,
            'controlaserie' => ($esProducto && $this->request->getPost('controlaserie')) ? 1 : 0,
            'garantiameses' => (int) $this->request->getPost('garantiameses'),
            'preciocosto'   => $this->request->getPost('preciocosto') !== '' ? $this->request->getPost('preciocosto') : 0,
            'monedacosto'   => in_array($monedaPost, ['PEN', 'USD'], true) ? $monedaPost : 'USD',
            'precioventa'   => $this->request->getPost('precioventa'),
            'estado'        => in_array($estadoPost, ['0', '1'], true) ? (int) $estadoPost : 1,
        ];

        if ($id) {
            $datos['idproducto'] = $id;
        }

        if (!$this->productoModel->validate($datos)) {
            $errores = array_merge($errores, array_values($this->productoModel->errors()));
        }

        $archivo = $this->request->getFile('imagen');
        $hayImagen = $tipo !== 'SERVICIO' && $archivo && $archivo->isValid() && !$archivo->hasMoved();

        if ($hayImagen) {
            $reglasImagen = [
                'imagen' => [
                    'rules'  => 'is_image[imagen]|mime_in[imagen,image/jpg,image/jpeg,image/png,image/webp]|max_size[imagen,2048]',
                    'errors' => [
                        'is_image' => 'El archivo seleccionado no es una imagen válida.',
                        'mime_in'  => 'Formato no admitido. Use JPG, PNG o WEBP.',
                        'max_size' => 'La imagen no debe superar los 2MB.',
                    ],
                ],
            ];

            if (!$this->validate($reglasImagen)) {
                $errores = array_merge($errores, array_values($this->validator->getErrors()));
            }
        }

        if (!empty($errores)) {
            return $this->volverConErrores(array_values(array_unique($errores)));
        }

        $nuevaImagen = null;
        if ($hayImagen) {
            $nuevaImagen      = $archivo->getRandomName();
            $archivo->move(FCPATH . 'uploads/productos', $nuevaImagen);
            $datos['imagen']  = $nuevaImagen;
        }

        try {
            $guardado = $this->productoModel->save($datos);
        } catch (\Throwable $e) {
            log_message('error', 'Error al guardar producto: ' . $e->getMessage());
            $guardado = false;
        }

        if (!$guardado) {
            if ($nuevaImagen && is_file(FCPATH . 'uploads/productos/' . $nuevaImagen)) {
                unlink(FCPATH . 'uploads/productos/' . $nuevaImagen);
            }

            $mensajes = array_values($this->productoModel->errors());
            return $this->volverConErrores($mensajes ?: ['No se pudo guardar el producto. Revise los datos e intente nuevamente.']);
        }

        if ($nuevaImagen && !empty($actual['imagen'])) {
            $rutaVieja = FCPATH . 'uploads/productos/' . $actual['imagen'];
            if (is_file($rutaVieja)) {
                unlink($rutaVieja);
            }
        }

        $mensaje = $id ? 'Producto actualizado correctamente.' : 'Producto registrado correctamente.';
        return redirect()->to(base_url('productos'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        $producto = $this->productoModel->find($id);

        if (!$producto) {
            return redirect()->to(base_url('productos'))->with('error', 'El producto no existe.');
        }

        $nuevoEstado = ((int) $producto['estado'] === 1) ? 0 : 1;
        $this->productoModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('productos'))->with('mensaje', 'Estado del producto actualizado.');
    }

    private function volverConErrores(array $errores): RedirectResponse
    {
        $form = array_intersect_key($this->request->getPost(), array_flip(self::CAMPOS_FORM));

        return redirect()->to(base_url('productos'))
            ->with('errors', $errores)
            ->with('form', $form);
    }

    private function obtenerUnidades(): array
    {
        $unidades = [];

        foreach (db_connect()->table('unidadesmedida')->get()->getResultArray() as $fila) {
            $etiqueta = '';
            foreach (['unidad', 'nombre', 'descripcion', 'abreviatura', 'simbolo'] as $col) {
                if (!empty($fila[$col])) {
                    $etiqueta = $fila[$col];
                    break;
                }
            }

            $unidades[] = [
                'idunidad' => (int) $fila['idunidad'],
                'etiqueta' => $etiqueta ?: 'Unidad #' . $fila['idunidad'],
            ];
        }

        return $unidades;
    }
}