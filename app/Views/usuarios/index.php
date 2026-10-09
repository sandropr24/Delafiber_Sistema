<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Usuarios y Personal</h4>
        <p class="text-muted small mb-0">Gestión de colaboradores, accesos y roles del sistema.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limpiarFormulario()">
        <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
    </button>
</div>

<div class="card border-0 shadow-sm w-100">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th class="ps-4" style="width: 60px;">#</th>
                    <th>Colaborador</th>
                    <th>Documento</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Teléfono</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end pe-4" style="width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay usuarios registrados en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $i => $u): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?= esc(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''))) ?: esc($u['nombreusuario']) ?>
                                </div>
                                <small class="text-muted"><?= esc(!empty($u['email']) ? $u['email'] : 'Sin correo') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= esc($u['tipodoc'] ?? '-') ?></span>
                                <span class="small font-monospace ms-1"><?= esc($u['numerodoc'] ?? '-') ?></span>
                            </td>
                            <td class="fw-semibold text-primary"><?= esc($u['nombreusuario']) ?></td>
                            <td>
                                <?php if ($u['rol'] === 'Administrador'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Administrador</span>
                                <?php elseif ($u['rol'] === 'Almacenero'): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Almacenero</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= esc($u['rol']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= esc($u['telefono'] ?? '-') ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('usuarios/cambiarestado/' . $u['idusuario']) ?>" 
                                   class="badge <?= $u['estado'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-decoration-none"
                                   title="Clic para alternar estado">
                                    <?= $u['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                </a>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editarUsuario(<?= esc(json_encode($u)) ?>)" 
                                            title="Editar Usuario">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('usuarios/eliminar/' . $u['idusuario']) ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('¿Seguro que deseas eliminar al usuario <?= esc($u['nombreusuario']) ?>? Esta acción no se puede deshacer.')" 
                                       title="Eliminar Usuario">
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

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('usuarios/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idusuario" id="idusuario">
                <input type="hidden" name="idpersona" id="idpersona">

                <div class="modal-header py-3">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-3">1. Datos Personales</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="nombres" class="form-label small fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="apellidos" class="form-label small fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="tipodoc" class="form-label small fw-semibold">Tipo Doc. <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipodoc" name="tipodoc" required>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">C. Extranjería</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="numerodoc" class="form-label small fw-semibold">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numerodoc" name="numerodoc" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="telefono" class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="999888777">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="direccion" class="form-label small fw-semibold">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion">
                        </div>
                    </div>

                    <h6 class="text-secondary small fw-bold text-uppercase mb-3 border-top pt-3">2. Credenciales y Acceso</h6>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="nombreusuario" class="form-label small fw-semibold">Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombreusuario" name="nombreusuario" required placeholder="ej. spachas">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="rol" class="form-label small fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccione un rol</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Almacenero">Almacenero</option>
                                <option value="Cajero">Cajero</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="claveacceso" class="form-label small fw-semibold" id="labelClave">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="claveacceso" name="claveacceso" placeholder="••••••••">
                            <small class="text-muted" id="helpClave" style="display: none; font-size: 0.75rem;">Dejar en blanco para conservar la actual.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function limpiarFormulario() {
        document.getElementById('modalTitulo').textContent = 'Nuevo Usuario';
        document.getElementById('idusuario').value = '';
        document.getElementById('idpersona').value = '';
        document.getElementById('nombres').value = '';
        document.getElementById('apellidos').value = '';
        document.getElementById('tipodoc').value = 'DNI';
        document.getElementById('numerodoc').value = '';
        document.getElementById('telefono').value = '';
        document.getElementById('email').value = '';
        document.getElementById('direccion').value = '';
        document.getElementById('nombreusuario').value = '';
        document.getElementById('rol').value = '';
        document.getElementById('claveacceso').value = '';
        
        document.getElementById('claveacceso').required = true;
        document.getElementById('helpClave').style.display = 'none';
    }

    function editarUsuario(u) {
        document.getElementById('modalTitulo').textContent = 'Editar Usuario';
        document.getElementById('idusuario').value = u.idusuario;
        document.getElementById('idpersona').value = u.idpersona || '';
        document.getElementById('nombres').value = u.nombres || '';
        document.getElementById('apellidos').value = u.apellidos || '';
        document.getElementById('tipodoc').value = u.tipodoc || 'DNI';
        document.getElementById('numerodoc').value = u.numerodoc || '';
        document.getElementById('telefono').value = u.telefono || '';
        document.getElementById('email').value = u.email || '';
        document.getElementById('direccion').value = u.direccion || '';
        document.getElementById('nombreusuario').value = u.nombreusuario;
        document.getElementById('rol').value = u.rol;
        document.getElementById('claveacceso').value = '';
        
        document.getElementById('claveacceso').required = false;
        document.getElementById('helpClave').style.display = 'block';

        new bootstrap.Modal(document.getElementById('modalUsuario')).show();
    }
</script>
<?= $this->endSection() ?>