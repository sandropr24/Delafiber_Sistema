<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4" id="viewCatalogos">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Administra las categorías y marcas de tus productos desde un solo lugar.</p>
        </div>
    </div>
    <ul class="nav nav-tabs mb-4" id="catalogoTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="categorias-tab" data-bs-toggle="tab" data-bs-target="#categorias-pane" type="button" role="tab">
                <i class="fas fa-tags me-1"></i> Categorías
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="marcas-tab" data-bs-toggle="tab" data-bs-target="#marcas-pane" type="button" role="tab">
                <i class="fas fa-copyright me-1"></i> Marcas
            </button>
        </li>
    </ul>

    <div class="tab-content" id="catalogoTabContent">
        <div class="tab-pane fade show active" id="categorias-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border rounded-3 shadow-sm p-4">
                        <h5 class="fw-bold text-primary mb-3">Nueva Categoría</h5>
                        <form action="<?= base_url('catalogos/guardar-categoria') ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Nombre de Categoría <span class="text-danger">*</span></label>
                                <input type="text" name="categoria" class="form-control" placeholder="Ej. Laptops, Routers..." required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar Categoría
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border rounded-3 shadow-sm overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase fs-7 text-secondary">
                                    <tr>
                                        <th style="width: 80px;" class="text-center">ID</th>
                                        <th>CATEGORÍA</th>
                                        <th style="width: 120px;" class="text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categorias)): ?>
                                        <?php foreach ($categorias as $cat): ?>
                                            <tr>
                                                <td class="text-center fw-bold text-muted"><?= $cat['idcategoria'] ?></td>
                                                <td><span class="fw-semibold text-dark"><?= esc($cat['categoria']) ?></span></td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-light text-primary border-0 me-1 btn-editar-categoria"
                                                        data-id="<?= $cat['idcategoria'] ?>"
                                                        data-nombre="<?= esc($cat['categoria']) ?>"
                                                        title="Editar">
                                                        <i class="bi bi-pencil-square fs-6"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-eliminar-categoria"
                                                        data-id="<?= $cat['idcategoria'] ?>"
                                                        data-nombre="<?= esc($cat['categoria']) ?>"
                                                        data-url="<?= base_url('catalogos/eliminar-categoria/' . $cat['idcategoria']) ?>"
                                                        title="Eliminar">
                                                        <i class="bi bi-trash3 fs-6"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No hay categorías registradas.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="marcas-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border rounded-3 shadow-sm p-4">
                        <h5 class="fw-bold text-primary mb-3">Nueva Marca</h5>
                        <form action="<?= base_url('catalogos/guardar-marca') ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Nombre de Marca <span class="text-danger">*</span></label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej. TP-Link, Cisco..." required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar Marca
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border rounded-3 shadow-sm overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase fs-7 text-secondary">
                                    <tr>
                                        <th style="width: 80px;" class="text-center">ID</th>
                                        <th>MARCA</th>
                                        <th style="width: 120px;" class="text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($marcas)): ?>
                                        <?php foreach ($marcas as $mar): ?>
                                            <tr>
                                                <td class="text-center fw-bold text-muted"><?= $mar['idmarca'] ?></td>
                                                <td><span class="fw-semibold text-dark"><?= esc($mar['marca']) ?></span></td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-light text-primary border-0 me-1 btn-editar-marca"
                                                        data-id="<?= $mar['idmarca'] ?>"
                                                        data-nombre="<?= esc($mar['marca']) ?>"
                                                        title="Editar">
                                                        <i class="bi bi-pencil-square fs-6"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-eliminar-marca"
                                                        data-id="<?= $mar['idmarca'] ?>"
                                                        data-nombre="<?= esc($mar['marca']) ?>"
                                                        data-url="<?= base_url('catalogos/eliminar-marca/' . $mar['idmarca']) ?>"
                                                        title="Eliminar">
                                                        <i class="bi bi-trash3 fs-6"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No hay marcas registradas.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('catalogos/actualizar-categoria') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="idcategoria" id="edit_idcategoria">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Editar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nombre de Categoría</label>
                    <input type="text" name="categoria" id="edit_nombre_categoria" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('catalogos/actualizar-marca') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="idmarca" id="edit_idmarca">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Editar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nombre de Marca</label>
                    <input type="text" name="marca" id="edit_nombre_marca" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/catalogos/index.js') ?>"></script>
<?= $this->endSection() ?>