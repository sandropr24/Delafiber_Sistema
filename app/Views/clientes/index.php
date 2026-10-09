<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Clientes</h4>
        <p class="text-muted small mb-0">Directorio de personas y empresas para ventas y facturación.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limpiarFormulario()">
        <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
    </button>
</div>

<div class="card border-0 shadow-sm w-100">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th class="ps-4" style="width: 60px;">#</th>
                    <th>Cliente / Razón Social</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th class="text-end pe-4" style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay clientes registrados en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $i => $c): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?= esc(trim($c['nombres'] . ' ' . ($c['apellidos'] ?? ''))) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= esc($c['tipodoc']) ?></span>
                                <span class="small font-monospace ms-1"><?= esc($c['numerodoc']) ?></span>
                            </td>
                            <td class="small text-muted"><?= esc($c['telefono'] ?? '-') ?></td>
                            <td class="small text-muted"><?= esc($c['email'] ?? '-') ?></td>
                            <td class="small text-muted text-truncate" style="max-width: 220px;">
                                <?= esc($c['direccion'] ?? '-') ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editarCliente(<?= esc(json_encode($c)) ?>)" 
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('clientes/eliminar/' . $c['idpersona']) ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('¿Deseas eliminar a este cliente?')" 
                                       title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('clientes/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idpersona" id="idpersona">

                <div class="modal-header py-3">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="tipodoc" class="form-label small fw-semibold">Tipo Doc. <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipodoc" name="tipodoc" required onchange="adaptarCamposDoc()">
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">C. Extranjería</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="numerodoc" class="form-label small fw-semibold">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numerodoc" name="numerodoc" required placeholder="8 dígitos (DNI) o 11 dígitos (RUC)">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="nombres" class="form-label small fw-semibold" id="labelNombres">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-12 col-md-6" id="contenedorApellidos">
                            <label for="apellidos" class="form-label small fw-semibold">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="telefono" class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="999888777">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="cliente@correo.com">
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label small fw-semibold">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Calle / Av. y número">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function adaptarCamposDoc() {
        const tipo = document.getElementById('tipodoc').value;
        const labelNombres = document.getElementById('labelNombres');
        const contenedorApellidos = document.getElementById('contenedorApellidos');

        if (tipo === 'RUC') {
            labelNombres.innerHTML = 'Razón Social <span class="text-danger">*</span>';
            contenedorApellidos.style.display = 'none';
            document.getElementById('apellidos').value = '';
        } else {
            labelNombres.innerHTML = 'Nombres <span class="text-danger">*</span>';
            contenedorApellidos.style.display = 'block';
        }
    }

    function limpiarFormulario() {
        document.getElementById('modalTitulo').textContent = 'Nuevo Cliente';
        document.getElementById('idpersona').value = '';
        document.getElementById('tipodoc').value = 'DNI';
        document.getElementById('numerodoc').value = '';
        document.getElementById('nombres').value = '';
        document.getElementById('apellidos').value = '';
        document.getElementById('telefono').value = '';
        document.getElementById('email').value = '';
        document.getElementById('direccion').value = '';
        adaptarCamposDoc();
    }

    function editarCliente(c) {
        document.getElementById('modalTitulo').textContent = 'Editar Cliente';
        document.getElementById('idpersona').value = c.idpersona;
        document.getElementById('tipodoc').value = c.tipodoc || 'DNI';
        document.getElementById('numerodoc').value = c.numerodoc || '';
        document.getElementById('nombres').value = c.nombres || '';
        document.getElementById('apellidos').value = c.apellidos || '';
        document.getElementById('telefono').value = c.telefono || '';
        document.getElementById('email').value = c.email || '';
        document.getElementById('direccion').value = c.direccion || '';
        adaptarCamposDoc();

        new bootstrap.Modal(document.getElementById('modalCliente')).show();
    }
</script>
<?= $this->endSection() ?>