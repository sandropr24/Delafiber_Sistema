<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4" id="viewCatalogos">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Administra las categorías y marcas de tus productos desde un solo lugar.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaMarca">
                <i class="bi bi-plus-lg me-1"></i> Nueva Marca
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="catalogoTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="categorias-tab" data-bs-toggle="tab" data-bs-target="#categorias-pane" type="button" role="tab">
                <i class="fas fa-tags me-1"></i> Categorías <span class="badge bg-secondary-subtle text-secondary ms-1"><?= !empty($categorias) ? count($categorias) : 0 ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="marcas-tab" data-bs-toggle="tab" data-bs-target="#marcas-pane" type="button" role="tab">
                <i class="fas fa-copyright me-1"></i> Marcas <span class="badge bg-secondary-subtle text-secondary ms-1"><?= !empty($marcas) ? count($marcas) : 0 ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="catalogoTabContent">
        <div class="tab-pane fade show active" id="categorias-pane" role="tabpanel">
            <div class="card border rounded-3 shadow-sm overflow-hidden" style="border-color: #e0e0e0 !important;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-secondary">
                            <tr>
                                <th style="width: 80px;" class="ps-4">ID</th>
                                <th>Nombre de Categoría</th>
                                <th style="width: 130px;" class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= $cat['idcategoria'] ?></td>
                                        <td><span class="fw-semibold text-dark"><?= esc($cat['categoria']) ?></span></td>
                                        <td class="text-end pe-4" style="white-space: nowrap;">
                                            <button type="button"
                                                class="btn btn-sm btn-light text-primary border-0 shadow-sm me-1 btn-editar-categoria"
                                                data-id="<?= $cat['idcategoria'] ?>"
                                                data-nombre="<?= esc($cat['categoria']) ?>"
                                                title="Editar Categoría">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-light text-danger border-0 shadow-sm btn-eliminar-categoria"
                                                data-id="<?= $cat['idcategoria'] ?>"
                                                data-nombre="<?= esc($cat['categoria']) ?>"
                                                data-url="<?= base_url('catalogos/eliminar-categoria/' . $cat['idcategoria']) ?>"
                                                title="Eliminar Categoría">
                                                <i class="bi bi-trash3 fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        <i class="fas fa-tags fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay categorías registradas aún.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="marcas-pane" role="tabpanel">
            <div class="card border rounded-3 shadow-sm overflow-hidden" style="border-color: #e0e0e0 !important;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-secondary">
                            <tr>
                                <th style="width: 80px;" class="ps-4">ID</th>
                                <th>Nombre de Marca</th>
                                <th style="width: 130px;" class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($marcas)): ?>
                                <?php foreach ($marcas as $mar): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= $mar['idmarca'] ?></td>
                                        <td><span class="fw-semibold text-dark"><?= esc($mar['marca']) ?></span></td>
                                        <td class="text-end pe-4" style="white-space: nowrap;">
                                            <button type="button"
                                                class="btn btn-sm btn-light text-primary border-0 shadow-sm me-1 btn-editar-marca"
                                                data-id="<?= $mar['idmarca'] ?>"
                                                data-nombre="<?= esc($mar['marca']) ?>"
                                                title="Editar Marca">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-light text-danger border-0 shadow-sm btn-eliminar-marca"
                                                data-id="<?= $mar['idmarca'] ?>"
                                                data-nombre="<?= esc($mar['marca']) ?>"
                                                data-url="<?= base_url('catalogos/eliminar-marca/' . $mar['idmarca']) ?>"
                                                title="Eliminar Marca">
                                                <i class="bi bi-trash3 fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        <i class="fas fa-copyright fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay marcas registradas aún.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('catalogos/guardar-categoria') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Categoría <span class="text-danger">*</span></label>
                    <input type="text" name="categoria" class="form-control" placeholder="Ej. Laptops, Routers..." required>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Categoría</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalNuevaMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('catalogos/guardar-marca') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" class="form-control" placeholder="Ej. TP-Link, Cisco..." required>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Marca</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('catalogos/actualizar-categoria') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="idcategoria" id="edit_idcategoria">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Editar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Categoría <span class="text-danger">*</span></label>
                    <input type="text" name="categoria" id="edit_nombre_categoria" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('catalogos/actualizar-marca') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="idmarca" id="edit_idmarca">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Editar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" id="edit_nombre_marca" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/catalogos/index.js') ?>"></script>
<?= $this->endSection() ?>