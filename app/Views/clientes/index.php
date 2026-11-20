<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid df-page">
    <?= view('partials/ruta', ['uiSection' => 'Directorio', 'uiCurrent' => 'Clientes', 'uiSectionUrl' => null]) ?>
    <div class="df-page-heading">
        <div>
            <h1>Clientes</h1>
            <p class="text-muted small mb-0">Directorio de personas y empresas para ventas y facturación.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limpiarFormulario()">
            <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
        </button>
    </div>

    <?php if ($erroresCliente = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0"><?php foreach ((array) $erroresCliente as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
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
                        <th class="text-center" style="width: 110px;">Estado</th>
                        <th class="text-end pe-4" style="width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay clientes registrados en el sistema.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $i => $c): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= esc(\App\Models\PersonaModel::nombreCliente($c)) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= esc($c['tipodoc']) ?></span>
                                    <span class="small font-monospace ms-1"><?= esc($c['numerodoc']) ?></span>
                                </td>
                                <td class="small text-muted"><?= esc($c['telefono'] ?? '-') ?></td>
                                <td class="small text-muted"><?= esc($c['email'] ?? '-') ?></td>
                                <td class="small text-muted text-truncate" style="max-width: 220px;" title="<?= esc($c['direccion'] ?? '') ?>">
                                    <?= esc($c['direccion'] ?? '-') ?>
                                </td>
                                <td class="text-center">
                                    <form action="<?= base_url('admin/clientes/cambiarestado/' . $c['idpersona']) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                            class="badge <?= (($c['estado'] ?? 1) == 1) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> px-2 py-1 fw-semibold"
                                            title="Clic para alternar estado">
                                            <?= (($c['estado'] ?? 1) == 1) ? 'Activo' : 'Inactivo' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light text-primary border-0"
                                            onclick="editarCliente(<?= esc(json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>)"
                                            title="Editar">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('admin/clientes/guardar') ?>" method="POST" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="idpersona" id="idpersona">
                <input type="hidden" name="origen_formulario" value="cliente">

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
                                <option value="PASAPORTE">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="numerodoc" class="form-label small fw-semibold">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numerodoc" name="numerodoc" required inputmode="numeric" pattern="[0-9]{8}" title="Ingrese exactamente 8 dígitos para DNI." minlength="8" maxlength="8">
                        </div>
                        <div class="col-12 col-md-6" id="contenedorNombres">
                            <label for="nombres" class="form-label small fw-semibold" id="labelNombres">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required minlength="2" maxlength="100">
                        </div>
                        <div class="col-12 col-md-6" id="contenedorApellidos">
                            <label for="apellidos" class="form-label small fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required minlength="2" maxlength="100">
                        </div>
                        <div class="col-12" id="contenedorRazonSocial" hidden>
                            <label for="razonsocial" class="form-label small fw-semibold">Razón social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="razonsocial" name="razonsocial" minlength="2" maxlength="150" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="telefono" class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="9">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100">
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label small fw-semibold">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" maxlength="150">
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
<div id="clienteConfig" data-form="<?= esc(json_encode($formCliente ?? []), 'attr') ?>"></div>
<script src="<?= base_url('js/clientes/index.js') ?>?v=<?= filemtime(FCPATH . 'js/clientes/index.js') ?>"></script>
<?= $this->endSection() ?>