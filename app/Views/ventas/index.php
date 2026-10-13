<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Historial y gestión de comprobantes de venta emitidos en el sistema.</p>
        </div>
        <a href="<?= base_url('ventas/crear') ?>" class="btn btn-primary shadow-sm px-3 py-2">
            <i class="fas fa-plus me-2"></i> Nueva Venta
        </a>
    </div>
    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-invoice me-2"></i>Lista de Ventas Registradas
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="text-uppercase fs-7 text-secondary tracking-wider" style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                        <tr>
                            <th class="py-3 px-4" style="width: 60px;">#</th>
                            <th class="py-3">Comprobante</th>
                            <th class="py-3">Cliente</th>
                            <th class="py-3">Local / Sucursal</th>
                            <th class="py-3 text-center" style="width: 300px;">Fecha</th>
                            <th class="py-3 text-end" style="width: 180px;">Total (S/.)</th>
                            <th class="py-3 text-center" style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (!empty($ventas) && is_array($ventas)): ?>
                            <?php foreach ($ventas as $key => $venta): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td class="px-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-bold">
                                            <?= esc($venta['tipodocumento']) ?>: <?= esc($venta['serie']) ?>-<?= esc($venta['numero']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= esc($venta['cliente'] ?? 'Cliente General') ?></span>
                                    </td>
                                    <td class="text-secondary">
                                        <?= esc($venta['local']) ?>
                                    </td>
                                    <td class="text-center text-secondary small font-monospace">
                                        <?= esc($venta['fecha']) ?>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        S/ <?= number_format($venta['total'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('ventas/detalles/' . $venta['idventa']) ?>" class="btn btn-outline-info btn-sm rounded-pill px-3" title="Ver detalles">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="my-3">
                                        <i class="fas fa-receipt fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay ventas registradas en el sistema todavía.</p>
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