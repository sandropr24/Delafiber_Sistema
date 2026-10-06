<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Marcas</h4>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMarca" onclick="limpiarFormulario()">
        <i class="bi bi-plus-lg me-1"></i> Nueva Marca
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
                <?php if (empty($marcas)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No hay marcas registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marcas as $i => $m): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold text-dark"><?= esc($m['marca']) ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                    onclick="editarMarca(<?= esc(json_encode($m)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?= base_url('marcas/eliminar/' . $m['idmarca']) ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Eliminar la marca <?= esc($m['marca']) ?>?')">
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

<div class="modal fade" id="modalMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('marcas/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idmarca" id="idmarca">

                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold " id="modalTitulo">Nueva Marca</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <label for="marca" class="form-label small fw-semibold">Nombre<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="marca" name="marca" required placeholder="Ej. TP-LINK">
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
        document.getElementById('modalTitulo').textContent = 'Nueva Marca';
        document.getElementById('idmarca').value = '';
        document.getElementById('marca').value = '';
    }

    function editarMarca(m) {
        document.getElementById('modalTitulo').textContent = 'Editar Marca';
        document.getElementById('idmarca').value = m.idmarca;
        document.getElementById('marca').value = m.marca;

        new bootstrap.Modal(document.getElementById('modalMarca')).show();
    }
</script>
<?= $this->endSection() ?>