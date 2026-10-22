<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4" id="viewProductos" data-url-buscar="<?= site_url('productos/buscar_tabla') ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Catálogo de Productos</h4>
            <p class="text-muted small mb-0">Gestión de insumos, materiales y equipos de telecomunicaciones.</p>
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
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="inputBuscarTablaProductos" class="form-control border-start-0" placeholder="Buscar por nombre, descripción, marca o categoría...">
                </div>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-secondary">
                        <th class="ps-4" style="width: 60px;">Foto</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Cód. Barras</th>
                        <th class="text-end" style="white-space: nowrap;">P. Venta</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4" style="width: 110px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyProductos">
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No hay productos registrados aún.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if (!empty($p['imagen']) && is_file(FCPATH . 'uploads/productos/' . $p['imagen'])): ?>
                                        <img src="<?= base_url('uploads/productos/' . esc($p['imagen'])) ?>"
                                            alt="<?= esc($p['nombre']) ?>"
                                            class="rounded border object-fit-cover shadow-sm"
                                            style="width: 44px; height: 44px; background: #f8f9fa; padding: 2px;">
                                    <?php else: ?>
                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-secondary"
                                            style="width: 44px; height: 44px;">
                                            <i class="bi bi-image fs-5"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= esc($p['nombre']) ?></td>
                                <td class="text-muted small text-truncate" style="max-width: 220px;"><?= esc($p['descripcion'] ?: '-') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= esc($p['categoria']) ?></span></td>
                                <td><?= esc($p['marca']) ?></td>
                                <td class="text-muted small"><?= esc($p['modelo'] ?: '-') ?></td>
                                <td class="text-muted small font-monospace"><?= esc($p['codigobarras'] ?: '-') ?></td>
                                <td class="text-end fw-bold text-dark" style="white-space: nowrap;">S/ <?= number_format($p['precioventa'], 2) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('productos/cambiarestado/' . $p['idproducto']) ?>"
                                        class="badge <?= $p['estado'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-decoration-none px-2 py-1"
                                        title="Clic para cambiar estado">
                                        <?= $p['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                    </a>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-light text-primary border-0 me-1 shadow-sm"
                                        onclick="editarProducto(<?= esc(json_encode($p)) ?>)"
                                        title="Editar Producto">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                        <div class="col-12 col-md-6">
                            <label for="nombre" class="form-label small fw-semibold">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="descripcion" class="form-label small fw-semibold">Descripción</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="idcategoria" class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="idcategoria" name="idcategoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['idcategoria'] ?>"><?= esc($c['categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="idmarca" class="form-label small fw-semibold">Marca <span class="text-danger">*</span></label>
                            <select class="form-select" id="idmarca" name="idmarca" required>
                                <option value="">Seleccione una marca</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= $m['idmarca'] ?>"><?= esc($m['marca']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="modelo" class="form-label small fw-semibold">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="codigobarras" class="form-label small fw-semibold">Código de Barras</label>
                            <input type="text" class="form-control" id="codigobarras" name="codigobarras" maxlength="13">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="precioventa" class="form-label small fw-semibold">Precio Venta (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precioventa" name="precioventa" required>
                        </div>

                        <div class="col-12 col-md-8">
                            <label for="imagen" class="form-label small fw-semibold">Imagen del Producto (Opcional)</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/png, image/jpeg, image/webp" onchange="previsualizarImagen(event)">
                            <small class="text-muted" style="font-size: 0.75rem;">Admite JPG, PNG o WEBP. Máximo 2MB.</small>
                        </div>

                        <div class="col-12 col-md-4 text-center">
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
</script>
<script src="<?= base_url('js/productos/crear.js') ?>"></script>
<?= $this->endSection() ?>