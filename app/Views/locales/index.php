<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Locales y Sedes</h4>
        <p class="text-muted small mb-0">Gestión de almacenes centrales, sucursales y puntos de venta.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLocal" onclick="limpiarFormulario()">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Local
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

<div class="card border-0 shadow-sm w-100">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th class="ps-4" style="width: 70px;">#</th>
                    <th>Nombre del Local</th>
                    <th>Tipo</th>
                    <th>Dirección</th>
                    <th class="text-end pe-4" style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locales)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No hay locales registrados en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($locales as $i => $l): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold text-dark">
                                <i class="bi bi-geo-alt text-primary me-1"></i>
                                <?= esc($l['nombrelocal']) ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= esc($l['tipolocal']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= esc($l['direccion']) ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editarLocal(<?= esc(json_encode($l)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?= base_url('locales/eliminar/' . $l['idlocal']) ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('¿Seguro que deseas eliminar <?= esc($l['nombrelocal']) ?>?')">
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
<div class="modal fade" id="modalLocal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('locales/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idlocal" id="idlocal">

                <div class="modal-header py-3">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Local</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="nombrelocal" class="form-label small fw-semibold">Nombre del Local / Almacén <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombrelocal" name="nombrelocal" required placeholder="Ej. Almacén Central Chincha">
                    </div>

                    <div class="mb-3">
                        <label for="tipolocal" class="form-label small fw-semibold">Tipo de Local <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipolocal" name="tipolocal" required>
                            <option value="">Seleccione el tipo</option>
                            <option value="Almacén Principal">Almacén Principal</option>
                            <option value="Sucursal / Tienda">Sucursal / Tienda</option>
                            <option value="Punto de Distribución">Punto de Distribución</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label small fw-semibold">Dirección <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required placeholder="Ej. Av. Mariscal Castilla 450">
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Local</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function limpiarFormulario() {
        document.getElementById('modalTitulo').textContent = 'Nuevo Local';
        document.getElementById('idlocal').value = '';
        document.getElementById('nombrelocal').value = '';
        document.getElementById('tipolocal').value = '';
        document.getElementById('direccion').value = '';
    }

    function editarLocal(l) {
        document.getElementById('modalTitulo').textContent = 'Editar Local';
        document.getElementById('idlocal').value = l.idlocal;
        document.getElementById('nombrelocal').value = l.nombrelocal;
        document.getElementById('tipolocal').value = l.tipolocal;
        document.getElementById('direccion').value = l.direccion;

        new bootstrap.Modal(document.getElementById('modalLocal')).show();
    }
</script>
<?= $this->endSection() ?>