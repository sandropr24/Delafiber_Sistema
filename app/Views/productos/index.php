<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4" id="viewProductos" data-url-buscar="<?= site_url('productos/buscar_tabla') ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Catálogo de Productos</h4>
            <p class="text-muted small mb-0">Equipos, servicios técnicos y combos que se pueden vender.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="limpiarFormulario()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
        </button>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <ul class="mb-0 ps-3">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border rounded-3 shadow-sm p-3 mb-4" style="border-color: #e0e0e0 !important;">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="inputBuscarTablaProductos" class="form-control border-start-0" placeholder="Buscar por nombre, modelo, código, marca o categoría...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroCategoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categoriasFiltro as $c): ?>
                        <option value="<?= (int) $c['idcategoria'] ?>">
                            <?= esc(!empty($c['categoriapadre']) ? $c['categoriapadre'] . ' › ' . $c['categoria'] : $c['categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filtroTipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="PRODUCTO">Producto</option>
                    <option value="SERVICIO">Servicio</option>
                    <option value="COMBO">Combo</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filtroEstado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-secondary">
                        <th class="ps-4" style="width: 60px;">Foto</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Código</th>
                        <th class="text-end" style="white-space: nowrap;">P. Costo</th>
                        <th class="text-end" style="white-space: nowrap;">P. Venta</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4" style="width: 90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyProductos">
                    <?= $this->include('productos/_filas') ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('productos/guardar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="idproducto" id="idproducto">

                <div class="modal-header py-3">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-3">
                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <label for="tipo" class="form-label small fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="PRODUCTO">Producto (equipo físico)</option>
                                <option value="SERVICIO">Servicio</option>
                                <option value="COMBO">Combo / Kit</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="idcategoria" class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="idcategoria" name="idcategoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['idcategoria'] ?>">
                                        <?= esc(!empty($c['categoriapadre']) ? $c['categoriapadre'] . ' › ' . $c['categoria'] : $c['categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4" data-oculto-en="SERVICIO">
                            <label for="idmarca" class="form-label small fw-semibold">Marca <span class="text-danger" id="asteriscoMarca">*</span></label>
                            <select class="form-select" id="idmarca" name="idmarca" required>
                                <option value="">Seleccione una marca</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= (int) $m['idmarca'] ?>"><?= esc($m['marca']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-text mt-0" id="tipoAyuda"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nombre" class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="150">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="descripcion" class="form-label small fw-semibold">Descripción</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" maxlength="150">
                        </div>

                        <div class="col-12 col-md-4" data-oculto-en="SERVICIO">
                            <label for="modelo" class="form-label small fw-semibold">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" maxlength="80">
                        </div>

                        <div class="col-12 col-md-4" data-oculto-en="SERVICIO">
                            <label for="codigobarras" class="form-label small fw-semibold">Código</label>
                            <input type="text" class="form-control" id="codigobarras" name="codigobarras" maxlength="50">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="idunidad" class="form-label small fw-semibold">Unidad de medida <span class="text-danger">*</span></label>
                            <select class="form-select" id="idunidad" name="idunidad" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= (int) $u['idunidad'] ?>"><?= esc($u['etiqueta']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="preciocosto" class="form-label small fw-semibold">Precio costo</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="preciocosto" name="preciocosto" value="0">
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="monedacosto" class="form-label small fw-semibold">Moneda del costo</label>
                            <select class="form-select" id="monedacosto" name="monedacosto">
                                <option value="USD">Dólares (US$)</option>
                                <option value="PEN">Soles (S/)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="precioventa" class="form-label small fw-semibold">Precio venta (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precioventa" name="precioventa" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="garantiameses" class="form-label small fw-semibold">Garantía (meses)</label>
                            <input type="number" step="1" min="0" max="120" class="form-control" id="garantiameses" name="garantiameses" value="0">
                        </div>

                        <div class="col-12 col-md-6" data-oculto-en="SERVICIO,COMBO">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="controlastock" name="controlastock" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="controlastock">Controla stock</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6" data-oculto-en="SERVICIO,COMBO">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="controlaserie" name="controlaserie" value="1">
                                <label class="form-check-label small fw-semibold" for="controlaserie">Lleva número de serie (garantías)</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-8" data-oculto-en="SERVICIO">
                            <label for="imagen" class="form-label small fw-semibold">Imagen (opcional)</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/png, image/jpeg, image/webp" onchange="previsualizarImagen(event)">
                            <small class="text-muted" style="font-size: 0.75rem;">JPG, PNG o WEBP. Máximo 2MB.</small>
                        </div>

                        <div class="col-12 col-md-4 text-center" data-oculto-en="SERVICIO">
                            <label class="form-label small fw-semibold d-block">Vista previa</label>
                            <div class="d-flex align-items-center justify-content-center bg-light border rounded mx-auto" style="width: 70px; height: 70px; overflow: hidden;">
                                <img id="previewImg" src="" alt="Previsualización" class="w-100 h-100 object-fit-cover" style="display: none;">
                                <i id="previewIcon" class="bi bi-image fs-3 text-secondary"></i>
                            </div>
                        </div>

                        <div class="col-12" id="divEstado" style="display: none;">
                            <label for="estado" class="form-label small fw-semibold">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const BASE_URL = "<?= base_url() ?>";
    const FORM_PREVIO = <?= json_encode(session()->getFlashdata('form') ?: null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= base_url('js/productos/crear.js') ?>"></script>
<?= $this->endSection() ?>