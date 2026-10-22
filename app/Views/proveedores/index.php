<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Gestión y registro centralizado de proveedores autorizados para Delafiber.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2" onclick="abrirModalNuevo()">
                <i class="bi bi-plus-lg"></i>
                <span class="fw-medium">Nuevo Proveedor</span>
            </button>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3" style="border-color: #e5e7eb !important;">
        <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between" style="border-color: #e5e7eb !important;">
            <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-truck text-primary"></i>
                Lista de Proveedores Registrados
            </h6>
            <span class="badge bg-white text-dark border fw-normal px-2 py-1">
                Total: <?= !empty($proveedores) ? count($proveedores) : 0 ?> registros
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">RUC</th>
                            <th class="py-3">Razón Social</th>
                            <th class="py-3">Nombre Comercial</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3">Email</th>
                            <th class="pe-4 py-3 text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($proveedores) && is_array($proveedores)): ?>
                            <?php foreach ($proveedores as $key => $prov): ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-bold">
                                            <?= esc($prov['ruc']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark d-block text-truncate" style="max-width: 250px;" title="<?= esc($prov['razonsocial']) ?>">
                                            <?= esc($prov['razonsocial']) ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary">
                                        <?= esc($prov['nombrecomercial'] ?: '-') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($prov['telefono'])): ?>
                                            <a href="tel:<?= esc($prov['telefono']) ?>" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-telephone text-muted"></i>
                                                <span><?= esc($prov['telefono']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($prov['email'])): ?>
                                            <a href="mailto:<?= esc($prov['email']) ?>" class="text-decoration-none text-primary d-inline-flex align-items-center gap-1 text-truncate" style="max-width: 200px;" title="<?= esc($prov['email']) ?>">
                                                <i class="bi bi-envelope text-muted"></i>
                                                <span><?= esc($prov['email']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button type="button"
                                                class="btn btn-sm btn-light border text-secondary hover-primary shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;"
                                                onclick='editarProveedor(<?= json_encode($prov) ?>)'
                                                data-bs-toggle="tooltip"
                                                title="Editar proveedor">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="<?= base_url('proveedores/eliminar/' . $prov['idproveedor']) ?>"
                                                class="btn btn-sm btn-light border text-danger hover-danger shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;"
                                                onclick="return confirm('¿Estás seguro de eliminar este proveedor?')"
                                                data-bs-toggle="tooltip"
                                                title="Eliminar proveedor">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div class="py-3">
                                        <i class="bi bi-truck fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                        <p class="mb-0 fw-medium">No hay proveedores registrados en el sistema.</p>
                                        <small class="text-muted">Los proveedores que agregues aparecerán en este listado.</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <form action="<?= base_url('proveedores/guardar') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="idproveedor" id="idproveedor">

                <div class="modal-header bg-light px-4 py-3 border-bottom" style="border-color: #e5e7eb !important;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="tituloModal">
                        <i class="bi bi-truck text-primary"></i>
                        Nuevo Proveedor
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="ruc" class="form-label small fw-bold text-secondary text-uppercase">RUC <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ruc" name="ruc" maxlength="11" required>
                        </div>
                        <div class="col-md-8">
                            <label for="razonsocial" class="form-label small fw-bold text-secondary text-uppercase">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="razonsocial" name="razonsocial" maxlength="150" required>
                        </div>

                        <div class="col-md-6">
                            <label for="nombrecomercial" class="form-label small fw-bold text-secondary text-uppercase">Nombre Comercial</label>
                            <input type="text" class="form-control" id="nombrecomercial" name="nombrecomercial" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label small fw-bold text-secondary text-uppercase">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="9">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-bold text-secondary text-uppercase">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label small fw-bold text-secondary text-uppercase">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" maxlength="150">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 border-top" style="border-color: #e5e7eb !important;">
                    <button type="button" class="btn btn-sm btn-light border text-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalNuevo() {
        document.getElementById('idproveedor').value = '';
        document.getElementById('ruc').value = '';
        document.getElementById('razonsocial').value = '';
        document.getElementById('nombrecomercial').value = '';
        document.getElementById('telefono').value = '';
        document.getElementById('email').value = '';
        document.getElementById('direccion').value = '';

        document.getElementById('tituloModal').innerHTML = '<i class="bi bi-truck text-primary"></i> Nuevo Proveedor';

        var modalElement = document.getElementById('modalProveedor');
        var myModal = new bootstrap.Modal(modalElement);
        myModal.show();
    }

    function editarProveedor(prov) {
        document.getElementById('idproveedor').value = prov.idproveedor;
        document.getElementById('ruc').value = prov.ruc;
        document.getElementById('razonsocial').value = prov.razonsocial;
        document.getElementById('nombrecomercial').value = prov.nombrecomercial || '';
        document.getElementById('telefono').value = prov.telefono || '';
        document.getElementById('email').value = prov.email || '';
        document.getElementById('direccion').value = prov.direccion || '';

        document.getElementById('tituloModal').innerHTML = '<i class="bi bi-truck text-primary"></i> Editar Proveedor';

        var modalElement = document.getElementById('modalProveedor');
        var myModal = new bootstrap.Modal(modalElement);
        myModal.show();
    }
</script>

<?= $this->endSection() ?>