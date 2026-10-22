<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Usuarios y Personal</h1>
            <p class="text-muted small mb-0">Gestión de colaboradores, accesos y roles del sistema.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limpiarFormulario()">
                <i class="bi bi-person-plus"></i>
                <span class="fw-medium">Nuevo Usuario</span>
            </button>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3" style="border-color: #e5e7eb !important;">
        <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between" style="border-color: #e5e7eb !important;">
            <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-people text-primary"></i>
                Lista de Usuarios Registrados
            </h6>
            <span class="badge bg-white text-dark border fw-normal px-2 py-1">
                Total: <?= !empty($usuarios) ? count($usuarios) : 0 ?> registros
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" width="100%" cellspacing="0">
                    <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">Colaborador</th>
                            <th class="py-3">Documento</th>
                            <th class="py-3">Usuario</th>
                            <th class="py-3">Rol</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3 text-center" style="width: 110px;">Estado</th>
                            <th class="pe-4 py-3 text-end" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <div class="py-3">
                                        <i class="bi bi-person-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                        <p class="mb-0 fw-medium">No hay usuarios registrados en el sistema.</p>
                                        <small class="text-muted">Los colaboradores que agregues aparecerán aquí.</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $i => $u): ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= esc(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''))) ?: esc($u['nombreusuario']) ?>">
                                            <?= esc(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''))) ?: esc($u['nombreusuario']) ?>
                                        </div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= esc(!empty($u['email']) ? $u['email'] : 'Sin correo') ?>">
                                            <?= esc(!empty($u['email']) ? $u['email'] : 'Sin correo') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1"><?= esc($u['tipodoc'] ?? '-') ?></span>
                                        <span class="small font-monospace ms-1 text-secondary"><?= esc($u['numerodoc'] ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary"><?= esc($u['nombreusuario']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['rol'] === 'Administrador'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Administrador</span>
                                        <?php elseif ($u['rol'] === 'Almacenero'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Almacenero</span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><?= esc($u['rol']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?= esc($u['telefono'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('usuarios/cambiarestado/' . $u['idusuario']) ?>"
                                            class="badge <?= $u['estado'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> text-decoration-none px-2 py-1 fw-semibold"
                                            title="Clic para alternar estado">
                                            <?= $u['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                        </a>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button"
                                                class="btn btn-sm btn-light border text-secondary hover-primary shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;"
                                                onclick="editarUsuario(<?= esc(json_encode($u)) ?>)"
                                                data-bs-toggle="tooltip"
                                                title="Editar Usuario">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="<?= base_url('usuarios/eliminar/' . $u['idusuario']) ?>"
                                                class="btn btn-sm btn-light border text-danger hover-danger shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;"
                                                onclick="return confirm('¿Seguro que deseas eliminar al usuario <?= esc($u['nombreusuario']) ?>? Esta acción no se puede deshacer.')"
                                                data-bs-toggle="tooltip"
                                                title="Eliminar Usuario">
                                                <i class="bi bi-trash3"></i>
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
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <form action="<?= base_url('usuarios/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idusuario" id="idusuario">
                <input type="hidden" name="idpersona" id="idpersona">

                <div class="modal-header bg-light px-4 py-3 border-bottom" style="border-color: #e5e7eb !important;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalTitulo">
                        <i class="bi bi-person-plus text-primary"></i>
                        Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-3">1. Datos Personales</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="nombres" class="form-label small fw-bold text-secondary text-uppercase">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="apellidos" class="form-label small fw-bold text-secondary text-uppercase">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="tipodoc" class="form-label small fw-bold text-secondary text-uppercase">Tipo Doc. <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipodoc" name="tipodoc" required>
                                <option value="DNI">DNI</option>
                                <option value="CE">C. Extranjería</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="numerodoc" class="form-label small fw-bold text-secondary text-uppercase">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numerodoc" name="numerodoc" maxlength="8" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="telefono" class="form-label small fw-bold text-secondary text-uppercase">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="9">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-bold text-secondary text-uppercase">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="direccion" class="form-label small fw-bold text-secondary text-uppercase">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion">
                        </div>
                    </div>

                    <h6 class="text-secondary small fw-bold text-uppercase mb-3 border-top pt-3" style="border-color: #e5e7eb !important;">2. Credenciales y Acceso</h6>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="nombreusuario" class="form-label small fw-bold text-secondary text-uppercase">Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombreusuario" name="nombreusuario" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="rol" class="form-label small fw-bold text-secondary text-uppercase">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccione un rol</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Almacenero">Almacenero</option>
                                <option value="Cajero">Cajero</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="claveacceso" class="form-label small fw-bold text-secondary text-uppercase" id="labelClave">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="claveacceso" name="claveacceso">
                            <small class="text-muted d-block mt-1" id="helpClave" style="display: none; font-size: 0.75rem;">Dejar en blanco para conservar la actual.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 border-top" style="border-color: #e5e7eb !important;">
                    <button type="button" class="btn btn-sm btn-light border text-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const BASE_URL = "<?= base_url() ?>";
</script>
<script src="<?= base_url('js/usuarios/crear.js') ?>"></script>
<?= $this->endSection() ?>