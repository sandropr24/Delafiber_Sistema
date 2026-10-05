<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                ¡Hola, <?= esc($nombreUsuario ?? session()->get('nombreusuario') ?? 'Usuario') ?>! 👋
            </h4>
            <p class="text-muted small mb-0">
                Sesión iniciada con el rol de <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($rol ?? session()->get('rol')) ?></span>
            </p>
        </div>
        <div class="text-muted small fw-medium">
            <i class="bi bi-calendar3 me-1"></i> Fecha: <strong><?= date('d/m/Y') ?></strong>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Ventas de Hoy</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0">S/ <?= number_format($totalVentasHoy ?? 0, 2) ?></h4>
                </div>
                <div class="bg-success-subtle text-success rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-currency-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Alertas de Stock</span>
                    <h4 class="fw-bold mt-1 mb-0 <?= ($stockCritico ?? 0) > 0 ? 'text-danger' : 'text-dark' ?>">
                        <?= $stockCritico ?? 0 ?>
                    </h4>
                </div>
                <div class="bg-danger-subtle text-danger rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Productos</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0"><?= $totalProductos ?? 0 ?></h4>
                </div>
                <div class="bg-primary-subtle text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cotizaciones Pend.</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0"><?= $cotizacionesPendientes ?? 0 ?></h4>
                </div>
                <div class="bg-warning-subtle text-warning rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-clipboard-data fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
        <h6 class="fw-bold mb-0 text-dark">Últimas Ventas Emitidas</h6>
        <a href="<?= base_url('ventas') ?>" class="text-primary small text-decoration-none fw-semibold">Ver todas &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-muted" style="font-size: 0.75rem;">
                    <th class="ps-4">Comprobante</th>
                    <th>Cliente</th>
                    <th>Fecha y Hora</th>
                    <th class="text-end">Total</th>
                    <th class="text-center pe-4">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasVentas)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4 small">
                            No hay ventas registradas aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimasVentas as $venta): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark">
                                <?= esc($venta['tipodocumento']) ?> <?= esc($venta['serie']) ?>-<?= esc($venta['numero']) ?>
                            </td>
                            <td><?= esc($venta['nombres']) ?> <?= esc($venta['apellidos']) ?></td>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                            <td class="text-end fw-bold">S/ <?= number_format($venta['total'], 2) ?></td>
                            <td class="text-center pe-4">
                                <?php if ($venta['estafacturado']): ?>
                                    <span class="badge bg-success-subtle text-success">Facturado</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Emitido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>