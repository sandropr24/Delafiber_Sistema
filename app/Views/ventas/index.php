<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Historial y gestión de comprobantes de venta emitidos en el sistema.</p>
        </div>
        <div>
            <a href="<?= base_url('ventas/crear') ?>" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-plus-lg"></i>
                <span class="fw-medium">Nueva Venta</span>
            </a>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3" style="border-color: #e5e7eb !important;">
        <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between" style="border-color: #e5e7eb !important;">
            <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary"></i>
                Lista de Ventas Registradas
            </h6>
            <span class="badge bg-white text-dark border fw-normal px-2 py-1">
                Total: <?= !empty($ventas) ? count($ventas) : 0 ?> registros
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">Comprobante</th>
                            <th class="py-3">Cliente</th>
                            <th class="py-3">Local / Sucursal</th>
                            <th class="py-3 text-center" style="width: 200px;">Fecha</th>
                            <th class="py-3 text-end" style="width: 150px;">Total (S/)</th>
                            <th class="pe-4 py-3 text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (!empty($ventas) && is_array($ventas)): ?>
                            <?php foreach ($ventas as $key => $venta): ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-bold">
                                            <?= esc($venta['tipodocumento']) ?>: <?= esc($venta['serie']) ?>-<?= esc($venta['numero']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark d-block text-truncate" style="max-width: 250px;" title="<?= esc($venta['cliente'] ?? '') ?>">
                                            <?= esc($venta['cliente'] ?? 'Cliente General') ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary">
                                        <?= esc($venta['local']) ?>
                                    </td>
                                    <td class="text-center text-secondary small font-monospace">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-calendar3 text-muted"></i>
                                            <span><?= esc($venta['fecha']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        S/ <?= number_format($venta['total'], 2) ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="<?= base_url('ventas/detalles/' . $venta['idventa']) ?>"
                                            class="btn btn-sm btn-light border text-secondary hover-primary shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;"
                                            data-bs-toggle="tooltip"
                                            title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div class="py-3">
                                        <i class="bi bi-receipt fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                        <p class="mb-0 fw-medium">No hay ventas registradas en el sistema todavía.</p>
                                        <small class="text-muted">Las ventas que emitas se mostrarán en este historial.</small>
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

<?= $this->endSection() ?>