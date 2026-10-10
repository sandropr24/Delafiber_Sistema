<?= $this->extend('layouts/main') ?> 

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Gestión y registro de proveedores autorizados para Delafiber.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" onclick="abrirModalNuevo()">
            <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Nuevo Proveedor
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-truck me-1"></i> Lista de Proveedores Registrados</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>RUC</th>
                            <th>Razón Social</th>
                            <th>Nombre Comercial</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($proveedores)): ?>
                            <?php foreach ($proveedores as $key => $prov): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><span class="fw-bold"><?= esc($prov['ruc']) ?></span></td>
                                    <td><?= esc($prov['razonsocial']) ?></td>
                                    <td><?= esc($prov['nombrecomercial'] ?: '-') ?></td>
                                    <td><?= esc($prov['telefono'] ?: '-') ?></td>
                                    <td><?= esc($prov['email'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-primary" onclick='editarProveedor(<?= json_encode($prov) ?>)' title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= base_url('proveedores/eliminar/' . $prov['idproveedor']) ?>" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este proveedor?')" title="Eliminar">
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
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('proveedores/guardar') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="idproveedor" id="idproveedor">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-primary" id="tituloModal">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ruc" class="form-label small fw-bold text-secondary">RUC <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ruc" name="ruc" maxlength="11"  required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="razonsocial" class="form-label small fw-bold text-secondary">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="razonsocial" name="razonsocial" maxlength="150" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombrecomercial" class="form-label small fw-bold text-secondary">Nombre Comercial</label>
                            <input type="text" class="form-control" id="nombrecomercial" name="nombrecomercial" maxlength="150" >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label small fw-bold text-secondary">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="9" >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100" >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="direccion" class="form-label small fw-bold text-secondary">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" maxlength="150" >
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
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