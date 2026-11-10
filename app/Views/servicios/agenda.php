<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>

<link rel="stylesheet" href="<?= base_url('css/servicios/index.css') ?>"><?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<?php
$grupos = [];
foreach ($ordenes as $orden) $grupos[substr($orden['fecha_inicio'], 0, 10)][] = $orden;
?>
<div class="container-fluid px-3 px-lg-4 py-4 df-servicios">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <p class="df-sv-eyebrow mb-1">Servicios / Agenda</p>
            <h1 class="h3 fw-bold mb-1">Agenda de Servicios</h1>
            <p class="text-muted small mb-0">Trabajos programados y en proceso, ordenados por fecha.</p>
        </div>
        <div class="d-flex gap-2"><a href="<?= base_url('servicios') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Órdenes</a><a href="<?= base_url('servicios/crear') ?>" class="btn df-sv-btn"><i class="bi bi-plus-lg me-1"></i>Nueva orden</a></div>
    </div>
    <?php if (!$grupos): ?><div class="card df-sv-card">
            <div class="card-body text-center py-5 text-muted"><i class="bi bi-calendar-x display-5 d-block mb-2"></i>No hay trabajos programados en el periodo consultado.</div>
        </div><?php endif; ?>
    <?php foreach ($grupos as $dia => $items): ?>
        <div class="df-sv-agenda-day">
            <h2 class="h6 fw-bold mb-3"><i class="bi bi-calendar3 me-2"></i><?= esc(date('d/m/Y', strtotime($dia))) ?> <span class="text-muted fw-normal">· <?= count($items) ?> trabajo(s)</span></h2>
            <div class="row g-3 mb-4">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-xxl-4">
                        <div class="card df-sv-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><strong><?= esc(date('H:i', strtotime($item['fecha_inicio']))) ?> – <?= esc(date('H:i', strtotime($item['fecha_fin']))) ?></strong><span class="df-sv-status df-sv-status-<?= esc($item['estado'], 'attr') ?>"><?= $item['estado'] === 'EN_PROCESO' ? 'En proceso' : 'Programado' ?></span></div>
                                <div class="fw-semibold mb-1"><?= esc($item['titulo']) ?></div>
                                <div class="small text-muted mb-2"><?= esc($item['cliente']) ?></div>
                                <div class="small mb-1"><i class="bi bi-person-gear me-2"></i><?= esc(trim($item['tecnico'] ?? '')) ?></div>
                                <div class="small text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?= esc($item['direccion'] ?: 'Dirección no registrada') ?></div><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('servicios/detalles/' . $item['idorden']) ?>">Ver orden OS-<?= str_pad((string) $item['idorden'], 5, '0', STR_PAD_LEFT) ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <p class="text-muted small">Periodo mostrado: desde hace 7 días hasta los próximos 60 días.</p>
</div>
<?= $this->endSection() ?>