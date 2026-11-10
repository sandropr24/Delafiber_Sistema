<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>

<link rel="stylesheet" href="<?= base_url('css/servicios/index.css') ?>">

<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$etiquetas = ['PENDIENTE' => 'Pendiente', 'PROGRAMADO' => 'Programado', 'EN_PROCESO' => 'En proceso', 'FINALIZADO' => 'Finalizado', 'CANCELADO' => 'Cancelado'];
?>
<div class="container-fluid px-3 px-lg-4 py-4 df-servicios">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="df-sv-eyebrow mb-1">Operaciones / Servicios</p>
            <h1 class="h3 fw-bold mb-1">Servicios Técnicos</h1>
            <p class="text-muted small mb-0">Organiza órdenes, técnicos y visitas programadas.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap"><a href="<?= base_url('servicios/agenda') ?>" class="btn btn-light border"><i class="bi bi-calendar3 me-2"></i>Agenda</a><a href="<?= base_url('servicios/crear') ?>" class="btn df-sv-btn"><i class="bi bi-plus-lg me-2"></i>Nueva orden</a></div>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ([['PENDIENTE', 'Pendientes', 'bi-inbox', 'warning'], ['PROGRAMADO', 'Programados', 'bi-calendar-check', 'primary'], ['EN_PROCESO', 'En proceso', 'bi-tools', 'info'], ['FINALIZADO', 'Finalizados', 'bi-check2-circle', 'success']] as $dato): ?>
            <div class="col-6 col-xl-3">
                <div class="df-sv-kpi">
                    <div class="d-flex align-items-center justify-content-between"><span class="df-sv-kpi-label"><?= $dato[1] ?></span><span class="df-sv-kpi-icon"><i class="bi <?= $dato[2] ?>"></i></span></div><strong><?= (int) ($resumen[$dato[0]] ?? 0) ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card df-sv-card">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h6 fw-bold mb-1">Órdenes registradas</h2>
                    <p class="text-muted small mb-0">Consulta el estado y los datos de cada trabajo.</p>
                </div><span class="small text-muted" id="contadorOrdenes"><?= count($ordenes) ?> orden(es)</span>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-lg-8">
                    <label class="visually-hidden" for="buscarOrden">Buscar órdenes</label>
                    <div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span><input type="search" id="buscarOrden" class="form-control border-start-0" placeholder="Buscar por cliente, código, servicio o técnico"></div>
                </div>
                <div class="col-lg-4"><label class="visually-hidden" for="filtrarEstado">Filtrar por estado</label><select id="filtrarEstado" class="form-select">
                        <option value="TODOS">Todos los estados</option><?php foreach ($etiquetas as $codigo => $texto): ?><option value="<?= $codigo ?>"><?= esc($texto) ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle df-sv-table" id="tablaOrdenes">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente / trabajo</th>
                            <th>Técnico</th>
                            <th>Programación</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $orden): ?>
                            <?php $estado = $orden['estado'];
                            $busqueda = mb_strtolower('OS-' . $orden['idorden'] . ' ' . $orden['cliente'] . ' ' . $orden['titulo'] . ' ' . ($orden['tecnico'] ?? '')); ?>
                            <tr class="fila-orden" data-busqueda="<?= esc($busqueda, 'attr') ?>" data-estado="<?= esc($estado, 'attr') ?>">
                                <td class="fw-semibold text-nowrap">OS-<?= str_pad((string) $orden['idorden'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($orden['cliente']) ?></div>
                                    <div class="text-muted small"><?= esc($orden['titulo']) ?></div>
                                </td>
                                <td><?= $orden['idtecnico'] ? esc(trim($orden['tecnico'])) : '<span class="text-muted">Sin asignar</span>' ?></td>
                                <td class="text-nowrap small"><?= $orden['fecha_inicio'] ? esc(date('d/m/Y H:i', strtotime($orden['fecha_inicio']))) : '<span class="text-muted">Sin programar</span>' ?></td>
                                <td><span class="df-sv-status df-sv-status-<?= esc($estado, 'attr') ?>"><?= esc($etiquetas[$estado] ?? $estado) ?></span></td>
                                <td class="text-end"><a href="<?= base_url('servicios/detalles/' . $orden['idorden']) ?>" class="btn btn-sm btn-outline-secondary text-nowrap" aria-label="Ver detalle de la orden OS-<?= (int) $orden['idorden'] ?>"><i class="bi bi-eye me-1"></i>Ver detalle</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="sinOrdenes" class="text-center text-muted py-5 <?= count($ordenes) ? 'd-none' : '' ?>"><i class="bi bi-clipboard2-x fs-3 d-block mb-2"></i>No hay órdenes que coincidan con la búsqueda.</div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script src="<?= base_url('js/servicios/index.js') ?>"></script><?= $this->endSection() ?>
