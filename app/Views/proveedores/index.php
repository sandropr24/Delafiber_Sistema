<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Gestión y registro centralizado de proveedores autorizados para Delafiber.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm px-3 py-2" onclick="abrirModalNuevo()">
            <i class="fas fa-plus me-2"></i> Nuevo Proveedor
        </button>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-truck text-primary me-2"></i> Lista de Proveedores Registrados</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="table-light text-uppercase fs-7 text-secondary tracking-wider">
                        <tr>
                            <th class="py-3 px-4" style="width: 50px;">#</th>
                            <th class="py-3">RUC</th>
                            <th class="py-3">Razón Social</th>
                            <th class="py-3">Nombre Comercial</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3">Email</th>
                            <th class="py-3 text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($proveedores)): ?>
                            <?php foreach ($proveedores as $key => $prov): ?>
                                <tr class="border-bottom border-light">
                                    <td class="px-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-bold">
                                            <?= esc($prov['ruc']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= esc($prov['razonsocial']) ?></span>
                                    </td>
                                    <td class="text-secondary"><?= esc($prov['nombrecomercial'] ?: '-') ?></td>
                                    <td>
                                        <?php if (!empty($prov['telefono'])): ?>
                                            <a href="tel:<?= esc($prov['telefono']) ?>" class="text-decoration-none text-dark">
                                                <i class="fas fa-phone-alt text-muted fa-xs me-1"></i> <?= esc($prov['telefono']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($prov['email'])): ?>
                                            <a href="mailto:<?= esc($prov['email']) ?>" class="text-decoration-none text-primary">
                                                <i class="fas fa-envelope text-muted fa-xs me-1"></i> <?= esc($prov['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center px-4">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-light text-primary border-0 me-1"
                                                onclick='editarProveedor(<?= json_encode($prov) ?>)'
                                                title="Editar proveedor">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                            <a href="<?= base_url('proveedores/eliminar/' . $prov['idproveedor']) ?>"
                                                class="btn btn-sm btn-light text-danger border-0 fs-5"
                                                onclick="return confirm('¿Estás seguro de eliminar este proveedor?')"
                                                title="Eliminar proveedor">
                                                <i class="bi bi-trash3 fs-5"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="my-3">
                                        <i class="fas fa-folder-open fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay proveedores registrados en el sistema.</p>
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

<!-- Modal -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="<?= base_url('proveedores/guardar') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="idproveedor" id="idproveedor">

                <div class="modal-header bg-light px-4 py-3 border-bottom border-light">
                    <h5 class="modal-title fw-bold text-primary" id="tituloModal">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ruc" class="form-label small fw-bold text-secondary">RUC <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ruc" name="ruc" maxlength="11" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="razonsocial" class="form-label small fw-bold text-secondary">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="razonsocial" name="razonsocial" maxlength="150" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombrecomercial" class="form-label small fw-bold text-secondary">Nombre Comercial</label>
                            <input type="text" class="form-control" id="nombrecomercial" name="nombrecomercial" maxlength="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label small fw-bold text-secondary">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="9">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="direccion" class="form-label small fw-bold text-secondary">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" maxlength="150">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 border-top border-light">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Proveedor</button>
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

        document.getElementById('tituloModal').innerText = 'Nuevo Proveedor';

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

        document.getElementById('tituloModal').innerText = 'Editar Proveedor';

        var modalElement = document.getElementById('modalProveedor');
        var myModal = new bootstrap.Modal(modalElement);
        myModal.show();
    }
</script>

<?= $this->endSection() ?>