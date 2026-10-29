<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4" id="viewCatalogos">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Administra las categorías, subcategorías y marcas de tus productos desde un solo lugar.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria" onclick="limpiarModalCategoria()">
                <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaMarca" onclick="limpiarModalMarca()">
                <i class="bi bi-plus-lg me-1"></i> Nueva Marca
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="catalogoTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="categorias-tab" data-bs-toggle="tab" data-bs-target="#categorias-pane" type="button" role="tab">
                <i class="bi bi-tags me-1"></i> Categorías
                <span class="badge bg-secondary-subtle text-secondary ms-1"><?= !empty($categorias) ? count($categorias) : 0 ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="marcas-tab" data-bs-toggle="tab" data-bs-target="#marcas-pane" type="button" role="tab">
                <i class="bi bi-c-circle me-1"></i> Marcas
                <span class="badge bg-secondary-subtle text-secondary ms-1"><?= !empty($marcas) ? count($marcas) : 0 ?></span>
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
                                <th style="width: 70px;" class="ps-4">ID</th>
                                <th>Nombre de Categoría</th>
                                <th>Categoría Padre</th>
                                <th class="text-center" style="width: 130px;">Productos</th>
                                <th class="text-center" style="width: 110px;">Estado</th>
                                <th style="width: 130px;" class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <?php $activa = ((int) ($cat['estado'] ?? 1) === 1); ?>
                                    <tr class="<?= $activa ? '' : 'text-muted' ?>">
                                        <td class="ps-4 fw-bold text-muted"><?= (int) $cat['idcategoria'] ?></td>
                                        <td>
                                            <span class="<?= $cat['nivel'] === 0 ? 'fw-bold' : 'fw-semibold' ?> text-dark"
                                                style="padding-left: <?= $cat['nivel'] * 1.5 ?>rem">
                                                <?php if ($cat['nivel'] > 0): ?>
                                                    <span class="text-muted me-1">↳</span>
                                                <?php endif; ?>
                                                <?= esc($cat['categoria']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($cat['categoriapadre'])): ?>
                                                <span class="badge bg-light text-dark border px-2 py-1"><?= esc($cat['categoriapadre']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Categoría Principal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int) $cat['total_productos'] ?></span>
                                            <?php if (!empty($cat['productos_hijos'])): ?>
                                                <div class="text-muted" style="font-size: .7rem;">+<?= (int) $cat['productos_hijos'] ?> en subcategorías</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= base_url('catalogos/cambiar-estado-categoria/' . $cat['idcategoria']) ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" title="Clic para alternar estado"
                                                    class="badge border <?= $activa ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> px-2 py-1 fw-semibold">
                                                    <?= $activa ? 'Activo' : 'Inactivo' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end pe-4" style="white-space: nowrap;">
                                            <?php if ($cat['nivel'] === 0 && $activa): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-light text-success border-0 shadow-sm"
                                                    onclick="nuevaSubcategoria(<?= (int) $cat['idcategoria'] ?>)"
                                                    title="Agregar subcategoría">
                                                    <i class="bi bi-node-plus fs-5"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button"
                                                class="btn btn-sm btn-light text-primary border-0 shadow-sm"
                                                onclick="editarCategoria(<?= esc(json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                                                title="Editar Categoría">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-tags fs-1 text-secondary-emphasis d-block mb-2"></i>
                                        <p class="mb-0">No hay categorías registradas aún. Crea la primera con "Nueva Categoría".</p>
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
                                <th style="width: 70px;" class="ps-4">ID</th>
                                <th>Nombre de Marca</th>
                                <th class="text-center" style="width: 130px;">Productos</th>
                                <th class="text-center" style="width: 110px;">Estado</th>
                                <th style="width: 130px;" class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($marcas)): ?>
                                <?php foreach ($marcas as $mar): ?>
                                    <?php $marcaActiva = ((int) ($mar['estado'] ?? 1) === 1); ?>
                                    <tr class="<?= $marcaActiva ? '' : 'text-muted' ?>">
                                        <td class="ps-4 fw-bold text-muted"><?= (int) $mar['idmarca'] ?></td>
                                        <td><span class="fw-semibold text-dark"><?= esc($mar['marca']) ?></span></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int) ($mar['total_productos'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (array_key_exists('estado', $mar)): ?>
                                                <form action="<?= base_url('catalogos/cambiar-estado-marca/' . $mar['idmarca']) ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" title="Clic para alternar estado"
                                                        class="badge border <?= $marcaActiva ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> px-2 py-1 fw-semibold">
                                                        <?= $marcaActiva ? 'Activo' : 'Inactivo' ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4" style="white-space: nowrap;">
                                            <button type="button"
                                                class="btn btn-sm btn-light text-primary border-0 shadow-sm"
                                                onclick="editarMarca(<?= esc(json_encode($mar, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                                                title="Editar Marca">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-c-circle fs-1 text-secondary-emphasis d-block mb-2"></i>
                                        <p class="mb-0">Aún no hay marcas. Crea la primera con "Nueva Marca".</p>
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
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('catalogos/guardar-categoria') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="idcategoria" id="edit_idcategoria">

            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold" id="modalCategoriaTitulo">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Categoría Padre (Opcional)</label>
                    <select name="idcategoriapadre" id="edit_idcategoriapadre" class="form-select">
                        <option value="">Ninguna (Es categoría principal)</option>
                        <?php if (!empty($categoriasPadre)): ?>
                            <?php foreach ($categoriasPadre as $p): ?>
                                <option value="<?= (int) $p['idcategoria'] ?>">
                                    <?= esc($p['categoria']) ?><?= ((int) $p['estado'] === 0) ? ' (inactiva)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text" id="ayudaPadre"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Categoría <span class="text-danger">*</span></label>
                    <input type="text" name="categoria" id="edit_nombre_categoria" class="form-control"
                        placeholder="Ej. Redes, Impresoras y tintas..." required minlength="3" maxlength="80">
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
            <input type="hidden" name="idmarca" id="edit_idmarca">

            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold" id="modalMarcaTitulo">Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombre de Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" id="edit_nombre_marca" class="form-control" placeholder="Ej. TP-Link, Hikvision..." required minlength="2" maxlength="50">
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Marca</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="<?= base_url('js/catalogos/index.js') ?>"></script>
<?= $this->endSection() ?>