<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/tecnicos/index.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4 df-tecnicos">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Gestión de Técnicos</h1>
            <p class="text-muted mb-0">Personal asignado a entregas, sin acceso al sistema.</p>
        </div>
        <button type="button" class="btn df-btn-primary" id="btnNuevoTecnico" data-bs-toggle="modal" data-bs-target="#modalTecnico"><i class="bi bi-person-plus me-2"></i>Nuevo técnico</button>
    </div>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-8"><input type="search" class="form-control" id="buscarTecnico" placeholder="Buscar por nombre, DNI o especialidad" aria-label="Buscar técnicos"></div>
                <div class="col-md-4"><select id="filtrarEstadoTecnico" class="form-select" aria-label="Filtrar por estado"><option value="TODOS">Todos los estados</option><option value="ACTIVO">Activos</option><option value="INACTIVO">Inactivos</option></select></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover df-tabla" id="tablaTecnicos">
                    <thead><tr><th>#</th><th>Técnico</th><th>DNI</th><th>Teléfono</th><th>Especialidad</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <tr data-busqueda="<?= esc(mb_strtolower($tecnico['nombres'] . ' ' . $tecnico['apellidos'] . ' ' . $tecnico['dni'] . ' ' . ($tecnico['especialidad'] ?? '')), 'attr') ?>" data-estado="<?= esc($tecnico['estado'], 'attr') ?>">
                                <td><?= (int) $tecnico['idtecnico'] ?></td>
                                <td class="fw-semibold"><?= esc($tecnico['nombres'] . ' ' . $tecnico['apellidos']) ?></td>
                                <td><?= esc($tecnico['dni']) ?></td>
                                <td><?= esc($tecnico['telefono'] ?: '—') ?></td>
                                <td><?= esc($tecnico['especialidad'] ?: '—') ?></td>
                                <td><span class="badge <?= $tecnico['estado'] === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= esc($tecnico['estado']) ?></span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btnEditarTecnico" data-tecnico="<?= esc(json_encode($tecnico, JSON_UNESCAPED_UNICODE), 'attr') ?>" data-bs-toggle="modal" data-bs-target="#modalTecnico" aria-label="Editar técnico"><i class="bi bi-pencil-square"></i></button>
                                    <form action="<?= base_url('tecnicos/cambiar-estado/' . $tecnico['idtecnico']) ?>" method="post" class="d-inline formEstadoTecnico" data-nombre="<?= esc($tecnico['nombres'] . ' ' . $tecnico['apellidos'], 'attr') ?>" data-accion="<?= $tecnico['estado'] === 'ACTIVO' ? 'desactivar' : 'activar' ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm <?= $tecnico['estado'] === 'ACTIVO' ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="Cambiar estado"><i class="bi <?= $tecnico['estado'] === 'ACTIVO' ? 'bi-person-dash' : 'bi-person-check' ?>"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p id="sinResultadosTecnicos" class="text-center text-muted py-4 d-none">No se encontraron técnicos.</p>
            </div>
            <small class="text-muted" id="contadorTecnicos"><?= count($tecnicos) ?> técnico(s)</small>
        </div>
    </div>
</div>
<div class="modal fade" id="modalTecnico" tabindex="-1" aria-labelledby="tituloModalTecnico" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content df-modal-tecnico">
        <div class="modal-header"><h5 class="modal-title fw-bold" id="tituloModalTecnico">Nuevo técnico</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <form action="<?= base_url('tecnicos/guardar') ?>" method="post" id="formTecnico">
            <?= csrf_field() ?>
            <input type="hidden" name="idtecnico" id="idtecnico">
            <div class="modal-body"><div class="row g-3">
                <div class="col-sm-6"><label for="nombresTecnico" class="form-label">Nombres *</label><input class="form-control" id="nombresTecnico" name="nombres" maxlength="100" required></div>
                <div class="col-sm-6"><label for="apellidosTecnico" class="form-label">Apellidos *</label><input class="form-control" id="apellidosTecnico" name="apellidos" maxlength="100" required></div>
                <div class="col-sm-6"><label for="dniTecnico" class="form-label">DNI *</label><input class="form-control" id="dniTecnico" name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" required></div>
                <div class="col-sm-6"><label for="telefonoTecnico" class="form-label">Teléfono</label><input class="form-control" id="telefonoTecnico" name="telefono" inputmode="numeric" pattern="[0-9]{9}" maxlength="9"></div>
                <div class="col-12"><label for="especialidadTecnico" class="form-label">Especialidad</label><input class="form-control" id="especialidadTecnico" name="especialidad" maxlength="100" placeholder="Instalación, soporte, reparto..."></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn df-btn-primary">Guardar técnico</button></div>
        </form>
    </div></div>
</div>
<?php if (session('mensaje')): ?><div id="flashTecnico" data-tipo="success" data-mensaje="<?= esc(session('mensaje'), 'attr') ?>" hidden></div><?php endif; ?>
<?php if (session('error')): ?><div id="flashTecnico" data-tipo="error" data-mensaje="<?= esc(session('error'), 'attr') ?>" hidden></div><?php endif; ?>
<?php if (session('errors')): ?><div id="flashTecnico" data-tipo="error" data-mensaje="<?= esc(implode(' ', (array) session('errors')), 'attr') ?>" hidden></div><?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/tecnicos/index.js') ?>"></script>
<?= $this->endSection() ?>
