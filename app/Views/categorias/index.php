<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Categorías</h4>
        <p class="text-muted small mb-0">Clasificación de productos (Insumos, Equipos, Cables, etc.)</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria" onclick="limpiarFormulario()">
        <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th class="ps-4" style="width: 70px;">#</th>
                    <th>Nombre</th>
                    <th class="text-end pe-4" style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No hay categorías registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categorias as $i => $cat): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold text-dark"><?= esc($cat['categoria']) ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                    onclick="editarCategoria(<?= esc(json_encode($cat)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?= base_url('categorias/eliminar/' . $cat['idcategoria']) ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Eliminar la categoría <?= esc($cat['categoria']) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('categorias/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idcategoria" id="idcategoria">

                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold" id="modalTitulo">Nueva Categoría</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <label for="nombrecategoria" class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombrecategoria" name="nombrecategoria" required placeholder="Ej. Insumos">
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function limpiarFormulario() {
        document.getElementById('modalTitulo').textContent = 'Nueva Categoría';
        document.getElementById('idcategoria').value = '';
        document.getElementById('nombrecategoria').value = '';
    }

    function editarCategoria(cat) {
        document.getElementById('modalTitulo').textContent = 'Editar Categoría';
        document.getElementById('idcategoria').value = cat.idcategoria;
        document.getElementById('nombrecategoria').value = cat.categoria;

        new bootstrap.Modal(document.getElementById('modalCategoria')).show();
    }
</script>
<?= $this->endSection() ?>