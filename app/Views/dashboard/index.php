<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                ¡Hola, <?= esc($nombreUsuario ?? session()->get('nombreusuario') ?? 'Usuario') ?>! 
            </h4>
            <p class="text-muted small mb-0">
                Resumen de movimientos para el rol de <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-semibold"><?= esc($rol ?? session()->get('rol')) ?></span>
            </p>
        </div>
        <div class="text-muted small fw-medium bg-light px-3 py-2 rounded-3 border border-light-subtle">
            <i class="bi bi-calendar3 me-1.5 text-primary"></i> Fecha: <strong class="text-dark"><?= date('d/m/Y') ?></strong>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 style-card-hover" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.6px; opacity: 0.85;">Ventas de Hoy</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0">S/ <?= number_format($totalVentasHoy ?? 0, 2) ?></h3>
                </div>
                <div class="bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="bi bi-currency-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 style-card-hover" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.6px; opacity: 0.85;">Alertas de Stock</span>
                    <h3 class="fw-bold mt-2 mb-0 <?= ($stockCritico ?? 0) > 0 ? 'text-danger fw-extrabold' : 'text-dark' ?>">
                        <?= $stockCritico ?? 0 ?>
                    </h3>
                </div>
                <div class="bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 style-card-hover" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.6px; opacity: 0.85;">Total Productos</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?= $totalProductos ?? 0 ?></h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 style-card-hover" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.6px; opacity: 0.85;">Cotizaciones Pend.</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?= $cotizacionesPendientes ?? 0 ?></h3>
                </div>
                <div class="bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="bi bi-clipboard-data fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3.5 d-flex justify-content-between align-items-center border-0 rounded-top-3">
        <h6 class="fw-bold mb-0 text-dark">Últimas Ventas Emitidas</h6>
        <a href="<?= base_url('ventas') ?>" class="text-primary small text-decoration-none fw-semibold build-hover-link">
            Ver todas <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <th class="ps-4 py-3">Comprobante</th>
                    <th class="py-3">Cliente</th>
                    <th class="py-3">Fecha y Hora</th>
                    <th class="text-end py-3">Total</th>
                    <th class="text-center pe-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasVentas)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <div class="d-flex flex-column align-items-center justify-content-center my-3">
                                <div class="bg-light rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-receipt text-secondary fs-3 opacity-50"></i>
                                </div>
                                <h6 class="fw-semibold text-secondary mb-1">No hay ventas registradas aún</h6>
                                <p class="text-muted small mb-0">Las transacciones que realices hoy se mostrarán en esta sección.</p>
                            </div>
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
                            <td class="text-end fw-bold text-dark">S/ <?= number_format($venta['total'], 2) ?></td>
                            <td class="text-center pe-4">
                                <?php if ($venta['estafacturado']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-semibold">Facturado</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 fw-semibold">Emitido</span>
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
