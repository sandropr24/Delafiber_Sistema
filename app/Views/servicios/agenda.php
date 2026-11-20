<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>

<link rel="stylesheet" href="<?= base_url('css/servicios/index.css') ?>"><?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<?php
$grupos = [];
foreach ($ordenes as $orden) $grupos[substr($orden['fecha_inicio'], 0, 10)][] = $orden;
?>
<div class="container-fluid df-page df-servicios">
    <?= view('partials/ruta', ['uiSection' => 'Servicios', 'uiCurrent' => 'Agenda', 'uiSectionUrl' => 'admin/servicios']) ?>
    <div class="df-page-heading">
        <div>
            <h1 class="h3 fw-bold mb-1">Agenda de Servicios</h1>
            <p class="text-muted small mb-0">Trabajos programados y en proceso, ordenados por fecha.</p>
        </div>
        <div class="d-flex gap-2"><a href="<?= base_url('admin/servicios') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Órdenes</a><?php if ($puedeGestionar): ?><a href="<?= base_url('admin/servicios/crear') ?>" class="btn df-sv-btn"><i class="bi bi-plus-lg me-1"></i>Nueva orden</a><?php endif; ?></div>
    </div>
    <div class="card df-sv-card mb-4">
        <div class="card-body p-3">
            <form id="filtrosAgenda" method="get" action="<?= base_url('admin/servicios/agenda') ?>" class="row g-2">
                <div class="col-sm-6 col-xl-2"><label for="agendaDesde" class="form-label fw-semibold">Desde</label><input id="agendaDesde" name="desde" type="date" class="form-control" value="<?= esc($filtros['desde'], 'attr') ?>" required></div>
                <div class="col-sm-6 col-xl-2"><label for="agendaHasta" class="form-label fw-semibold">Hasta</label><input id="agendaHasta" name="hasta" type="date" class="form-control" value="<?= esc($filtros['hasta'], 'attr') ?>" required></div>
                <div class="col-md-5 col-xl-3"><label for="agendaEstado" class="form-label fw-semibold">Estado</label><select id="agendaEstado" name="estado" class="form-select"><option value="">Todos</option><option value="PROGRAMADO" <?= $filtros['estado'] === 'PROGRAMADO' ? 'selected' : '' ?>>Programado</option><option value="EN_PROCESO" <?= $filtros['estado'] === 'EN_PROCESO' ? 'selected' : '' ?>>En proceso</option></select></div>
                <div class="col-md-5 col-xl-3"><label for="agendaTecnico" class="form-label fw-semibold">Técnico</label><select id="agendaTecnico" name="tecnico" class="form-select"><option value="">Todos los técnicos</option><?php foreach ($tecnicos as $tecnico): ?><option value="<?= (int) $tecnico['idtecnico'] ?>" <?= (int) $filtros['tecnico'] === (int) $tecnico['idtecnico'] ? 'selected' : '' ?>><?= esc(trim($tecnico['nombres'] . ' ' . $tecnico['apellidos'])) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn df-sv-btn flex-grow-1" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="<?= base_url('admin/servicios/agenda') ?>" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a></div>
            </form>
        </div>
    </div>
    <?php if ($agendaTruncada): ?><div class="alert alert-warning">Se muestran los primeros 500 trabajos. Reduce el rango o aplica más filtros.</div><?php endif; ?>
    <?php if (!$grupos): ?><div class="card df-sv-card">
            <div class="card-body text-center py-5 text-muted"><i class="bi bi-calendar-x display-5 d-block mb-2"></i>No hay trabajos programados en el periodo consultado.</div>
        </div><?php endif; ?>
    <?php foreach ($grupos as $dia => $items): ?>
        <div class="df-sv-agenda-day" data-fecha="<?= esc($dia, 'attr') ?>">
            <h2 class="h6 fw-bold mb-3"><i class="bi bi-calendar3 me-2"></i><?= esc(date('d/m/Y', strtotime($dia))) ?> <span class="text-muted fw-normal">· <?= count($items) ?> trabajo(s)</span></h2>
            <div class="row g-3 mb-4">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-xxl-4 df-sv-agenda-item" data-tecnico="<?= (int) ($item['idtecnico'] ?? 0) ?>" data-estado="<?= esc($item['estado'], 'attr') ?>" data-fecha="<?= esc($dia, 'attr') ?>">
                        <div class="card df-sv-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><strong><?= esc(date('H:i', strtotime($item['fecha_inicio']))) ?> – <?= esc(date('H:i', strtotime($item['fecha_fin']))) ?></strong><span class="df-sv-status df-sv-status-<?= esc($item['estado'], 'attr') ?>"><?= esc($etiquetas[$item['estado']] ?? $item['estado']) ?></span></div>
                                <div class="fw-semibold mb-1"><?= esc($item['titulo']) ?></div>
                                <div class="small text-muted mb-2"><?= esc($item['cliente']) ?></div>
                                <div class="small mb-1"><i class="bi bi-person-gear me-2"></i><?= esc(trim($item['tecnico'] ?? '')) ?></div>
                                <div class="small text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?= esc($item['direccion'] ?: 'Dirección no registrada') ?></div><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('admin/servicios/detalles/' . $item['idorden']) ?>">Ver orden OS-<?= str_pad((string) $item['idorden'], 5, '0', STR_PAD_LEFT) ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <p class="text-muted small">Se muestran trabajos cuya fecha de inicio está dentro del rango seleccionado. El rango máximo es de 180 días.</p>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/servicios/agenda.js') ?>"></script>
<?= $this->endSection() ?>
