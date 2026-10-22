<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Gestión de ingresos de mercadería y control de compras del sistema.</p>
        </div>
        <div>
            <a href="<?= base_url('compras/nuevo') ?>" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-plus-lg"></i>
                <span class="fw-medium">Nueva Compra</span>
            </a>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3" style="border-color: #e5e7eb !important;">
        <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between" style="border-color: #e5e7eb !important;">
            <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i>
                Historial de Compras
            </h6>
            <span class="badge bg-white text-dark border fw-normal px-2 py-1">
                Total: <?= !empty($compras) ? count($compras) : 0 ?> registros
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dataTable">
                    <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">#ID</th>
                            <th class="py-3">Fecha</th>
                            <th class="py-3">Comprobante</th>
                            <th class="py-3">Serie / Nro</th>
                            <th class="py-3">Proveedor</th>
                            <th class="py-3 text-end">Total (S/)</th>
                            <th class="pe-4 py-3 text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($compras) && is_array($compras)): ?>
                            <?php foreach ($compras as $c): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">#<?= esc($c['idcompra']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 text-dark">
                                            <i class="bi bi-calendar3 text-muted"></i>
                                            <span><?= date('d/m/Y', strtotime($c['fechacompra'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = ($c['tipocomprobante'] == 'FACTURA')
                                            ? 'bg-success-subtle text-success border border-success-subtle'
                                            : 'bg-info-subtle text-info border border-info-subtle';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> px-2 py-1 rounded-2 text-uppercase fw-semibold" style="font-size: 0.75rem;">
                                            <?= esc($c['tipocomprobante']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code class="text-pink fw-semibold bg-light px-2 py-1 rounded border"><?= esc($c['serie']) ?></code>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-medium d-block text-truncate" style="max-width: 280px;" title="<?= esc($c['proveedor'] ?? '') ?>">
                                            <?= esc($c['proveedor'] ?? 'Sin Proveedor') ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        S/ <?= number_format($c['total'], 2) ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="<?= base_url('compras/detalles/' . $c['idcompra']) ?>"
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
                                        <i class="bi bi-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                        <p class="mb-0 fw-medium">No hay compras registradas hasta el momento.</p>
                                        <small class="text-muted">Las compras que registres aparecerán en este historial.</small>
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