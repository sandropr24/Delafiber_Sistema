<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoriaModel;
use App\Models\MarcaModel;
use App\Models\ProductoModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProductoController extends BaseController
{
    private const TIPOS = ['PRODUCTO', 'SERVICIO', 'COMBO'];

    private const CAMPOS_FORM = [
        'serieinicial',
        'idproducto',
        'tipo',
        'idcategoria',
        'idmarca',
        'nombre',
        'descripcion',
        'modelo',
        'codigobarras',
        'idunidad',
        'precioventa',
        'garantiameses',
        'controlastock',
        'controlaserie',
        'estado',
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
        $listado = $this->productoModel->paginarConRelaciones(null, [], null, null, 1, 10);

        $data = [
            'titulo'           => 'Productos',
            'productos'        => $this->agregarSeriesIniciales($listado['productos']),
            'paginacion'       => $listado,
            'categoriasFiltro' => $todasCategorias,
            'categorias'       => array_values(array_filter($todasCategorias, static fn($c) => (int) $c['estado'] === 1)),
            'marcas'           => array_values(array_filter(
                $this->marcaModel->orderBy('marca', 'ASC')->findAll(),
                static fn($m) => (int) ($m['estado'] ?? 1) === 1
            )),
            'unidades'         => $this->obtenerUnidades(),
            'componentesDisponibles' => $this->productoModel->whereIn('tipo', ['PRODUCTO', 'SERVICIO'])->where('estado', 1)->orderBy('nombre', 'ASC')->findAll(),
            'componentesPorCombo' => $this->obtenerComponentesCombos(),
        ];

        return view('productos/index', $data);
    }

    public function buscar_tabla()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('admin/productos'));
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

        $pagina = max(1, (int) $this->request->getGet('pagina'));
        $porPagina = (int) $this->request->getGet('por_pagina');
        $porPagina = in_array($porPagina, [10, 20, 50], true) ? $porPagina : 10;
        $listado = $this->productoModel->paginarConRelaciones($q, $ids, $tipo, $estado, $pagina, $porPagina);

        return $this->response->setJSON([
            'filas' => view('productos/_filas', ['productos' => $this->agregarSeriesIniciales($listado['productos'])]),
            'paginacion' => view('productos/_paginacion', ['paginacion' => $listado]),
            'pagina' => $listado['pagina'],
        ]);
    }

    public function guardar(): RedirectResponse
    {
        $id     = (int) $this->request->getPost('idproducto');
        $actual = $id ? $this->productoModel->find($id) : null;

        if ($id && !$actual) {
            return redirect()->to(base_url('admin/productos'))->with('error', 'El producto no existe.');
        }

        $tipo        = strtoupper((string) $this->request->getPost('tipo'));
        $esProducto  = $tipo === 'PRODUCTO';
        $idCategoria = (int) $this->request->getPost('idcategoria') ?: null;
        $idMarca     = (int) $this->request->getPost('idmarca') ?: null;
        $idUnidad    = (int) $this->request->getPost('idunidad') ?: null;
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
            'precioventa'   => trim((string) $this->request->getPost('precioventa')) === ''
                ? '0.00' : $this->request->getPost('precioventa'),
            'estado'        => in_array($estadoPost, ['0', '1'], true) ? (int) $estadoPost : 1,
        ];

        $componentes = [];
        if ($tipo === 'COMBO') {
            $json = (string) $this->request->getPost('componentes_combo');
            $entrada = json_decode($json, true);
            if (!is_array($entrada) || !$entrada) {
                $errores[] = 'Agregue al menos un producto o servicio al combo.';
            } else {
                foreach ($entrada as $item) {
                    if (!is_array($item)) {
                        $errores[] = 'Los componentes del combo son inválidos.';
                        continue;
                    }
                    $componenteId = filter_var($item['idproducto'] ?? null, FILTER_VALIDATE_INT);
                    $cantidad = $item['cantidad'] ?? null;
                    if (!$componenteId || !is_scalar($cantidad) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', (string) $cantidad) || (float) $cantidad <= 0) {
                        $errores[] = 'Revise el producto y la cantidad de cada componente.';
                        continue;
                    }
                    if (isset($componentes[$componenteId])) {
                        $errores[] = 'No repita un mismo componente en el combo.';
                        continue;
                    }
                    $registro = $this->productoModel->find($componenteId);
                    if (!$registro || !in_array($registro['tipo'], ['PRODUCTO', 'SERVICIO'], true) || (int) $registro['estado'] !== 1 || $componenteId === $id) {
                        $errores[] = 'Un componente no existe, está inactivo o no es un producto/servicio.';
                        continue;
                    }
                    $componentes[$componenteId] = number_format((float) $cantidad, 2, '.', '');
                }
            }
            if (count($componentes) > 100) {
                $errores[] = 'El combo admite un máximo de 100 componentes.';
            }
            if (!is_numeric($datos['precioventa']) || (float) $datos['precioventa'] <= 0) {
                $errores[] = 'Ingrese un precio de venta del combo mayor que cero.';
            }
        } elseif ($id && ($actual['tipo'] ?? '') === 'COMBO') {
            $errores[] = 'No cambie el tipo de un combo existente; edite sus componentes o desactívelo.';
        }

        $serieInicial = trim((string) $this->request->getPost('serieinicial'));
        if ($serieInicial !== '' && strlen($serieInicial) > 60) {
            $errores[] = 'El número de serie no puede superar 60 caracteres.';
        }
        if ($serieInicial !== '' && !$datos['controlaserie']) {
            $errores[] = 'Active «Lleva número de serie» para registrar una serie.';
        }
        if ($datos['controlaserie'] && !$datos['controlastock']) {
            $errores[] = 'Un producto con número de serie debe controlar stock.';
        }
        if ($id) {
            $datos['idproducto'] = $id;
        }

        if (!$this->productoModel->validate($datos)) {
            $errores = array_merge($errores, array_values($this->productoModel->errors()));
        }

        $archivo = $this->request->getFile('imagen');
        $hayArchivo = $archivo && $archivo->getError() !== UPLOAD_ERR_NO_FILE;
        $hayImagen = $hayArchivo && $archivo->isValid() && !$archivo->hasMoved();
        if ($hayArchivo && !$hayImagen) {
            $errores[] = 'No se pudo recibir la imagen. Compruebe su tamaño y vuelva a intentarlo.';
        }

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
            $directorio = FCPATH . 'uploads/productos';
            if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                return $this->volverConErrores(['No se pudo preparar la carpeta de imágenes.']);
            }
            $nuevaImagen = $archivo->getRandomName();
            try {
                $archivo->move($directorio, $nuevaImagen);
                $datos['imagen'] = $nuevaImagen;
            } catch (\Throwable $e) {
                log_message('error', 'Error al subir imagen: ' . $e->getMessage());
                return $this->volverConErrores(['No se pudo guardar la imagen. Revise los permisos de uploads/productos.']);
            }
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $guardado = $this->productoModel->save($datos);
            if (!$guardado) {
                throw new \RuntimeException('No se pudo guardar el producto.');
            }
            $idGuardado = $id ?: (int) $this->productoModel->getInsertID();
            if ($serieInicial !== '') {
                $serieExistente = $db->table('series')->where('idproducto', $idGuardado)
                    ->where('numeroserie', $serieInicial)->get()->getRowArray();
                if (!$serieExistente) {
                    if (!$db->table('series')->insert([
                        'idproducto' => $idGuardado,
                        'numeroserie' => $serieInicial,
                        'idlocal' => null,
                        'iddetcompra' => null,
                        'estado' => 'EN_STOCK',
                    ])) {
                        throw new \RuntimeException('No se pudo registrar la serie inicial.');
                    }
                }
            }
            if ($tipo === 'COMBO') {
                $db->table('combo_detalle')->where('idcombo', $idGuardado)->delete();
                foreach ($componentes as $componenteId => $cantidad) {
                    if (!$db->table('combo_detalle')->insert([
                        'idcombo' => $idGuardado,
                        'idproducto' => $componenteId,
                        'cantidad' => $cantidad,
                    ])) {
                        throw new \RuntimeException('No se pudo guardar un componente del combo.');
                    }
                }
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Error de base de datos.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
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
        return redirect()->to(base_url('admin/productos'))->with('mensaje', $mensaje);
    }

    public function cambiarEstado($id = null): RedirectResponse
    {
        $producto = $this->productoModel->find($id);

        if (!$producto) {
            return redirect()->to(base_url('admin/productos'))->with('error', 'El producto no existe.');
        }

        $nuevoEstado = ((int) $producto['estado'] === 1) ? 0 : 1;
        $this->productoModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to(base_url('admin/productos'))->with('mensaje', 'Estado del producto actualizado.');
    }

    private function volverConErrores(array $errores): RedirectResponse
    {
        $form = array_intersect_key($this->request->getPost(), array_flip(self::CAMPOS_FORM));

        return redirect()->to(base_url('admin/productos'))
            ->with('errors', $errores)
            ->with('form', $form);
    }

    private function agregarSeriesIniciales(array $productos): array
    {
        if (!$productos) return $productos;
        $db = db_connect();
        foreach ($productos as &$producto) {
            $fila = $db->table('series')->select('numeroserie')
                ->where('idproducto', $producto['idproducto'])
                ->where('iddetcompra', null)->where('idlocal', null)
                ->orderBy('idserie', 'ASC')->get(1)->getRowArray();
            $producto['serieinicial'] = $fila['numeroserie'] ?? '';
        }
        unset($producto);
        return $productos;
    }

    private function obtenerComponentesCombos(): array
    {
        $resultado = [];
        $filas = db_connect()->table('combo_detalle')->select('idcombo, idproducto, cantidad')->get()->getResultArray();
        foreach ($filas as $fila) {
            $resultado[(int) $fila['idcombo']][] = [
                'idproducto' => (int) $fila['idproducto'],
                'cantidad' => $fila['cantidad'],
            ];
        }
        return $resultado;
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
