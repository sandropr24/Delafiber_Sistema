<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Información detallada del comprobante e ítems ingresados al inventario.</p>
        </div>
        <div>
            <a href="<?= base_url('compras') ?>" class="btn btn-light border text-secondary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-arrow-left"></i>
                <span class="fw-medium">Volver a Compras</span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border shadow-sm rounded-3 h-100" style="border-color: #e5e7eb !important;">
                <div class="card-header bg-light py-3 px-4 border-bottom" style="border-color: #e5e7eb !important;">
                    <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        Datos del Comprobante
                    </h6>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="row g-3">
                        <div class="col-12">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Proveedor</span>
                            <span class="fw-bold text-dark fs-6 d-block"><?= esc($compra['proveedor'] ?? 'N/A') ?></span>
                        </div>

                        <div class="col-6">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">RUC</span>
                            <code class="text-pink fw-semibold bg-light px-2 py-1 rounded d-inline-block border"><?= esc($compra['ruc'] ?? 'N/A') ?></code>
                        </div>

                        <div class="col-6">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Comprobante</span>
                            <?php
                            $badgeClass = ($compra['tipocomprobante'] == 'FACTURA')
                                ? 'bg-success-subtle text-success border border-success-subtle'
                                : 'bg-info-subtle text-info border border-info-subtle';
                            ?>
                            <span class="badge <?= $badgeClass ?> px-2 py-1 rounded-2 text-uppercase fw-semibold" style="font-size: 0.75rem;">
                                <?= esc($compra['tipocomprobante']) ?>
                            </span>
                        </div>

                        <div class="col-6">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Serie / Número</span>
                            <span class="fw-medium text-dark"><?= esc($compra['serie']) ?></span>
                        </div>

                        <div class="col-6">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Fecha Emisión</span>
                            <span class="text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-calendar3 text-muted"></i>
                                <?= date('d/m/Y', strtotime($compra['fechacompra'])) ?>
                            </span>
                        </div>

                        <div class="col-12">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Fecha de Registro</span>
                            <span class="text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-clock-history text-muted"></i>
                                <?= esc($compra['fecharegistro']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top" style="border-color: #e5e7eb !important;">
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border">
                            <span class="fw-semibold text-secondary">Total Compra:</span>
                            <h4 class="text-success fw-bold mb-0">S/ <?= number_format($compra['total'], 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border shadow-sm rounded-3 h-100" style="border-color: #e5e7eb !important;">
                <div class="card-header bg-light py-3 px-4 border-bottom" style="border-color: #e5e7eb !important;">
                    <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary"></i>
                        Productos en esta Compra
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom">
                                <tr>
                                    <th class="ps-4 py-3">Producto</th>
                                    <th class="py-3 text-center" style="width: 110px;">Cantidad</th>
                                    <th class="py-3 text-end" style="width: 150px;">P. Compra (S/)</th>
                                    <th class="pe-4 py-3 text-end" style="width: 150px;">Subtotal (S/)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detalles) && is_array($detalles)): ?>
                                    <?php foreach ($detalles as $det): ?>
                                        <?php $subtotal = $det['cantidad'] * $det['preciocompra']; ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-medium text-dark d-block"><?= esc($det['producto']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-2">
                                                    <?= esc($det['cantidad']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end text-muted">S/ <?= number_format($det['preciocompra'], 2) ?></td>
                                            <td class="pe-4 text-end fw-bold text-success">S/ <?= number_format($subtotal, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <div class="py-3">
                                                <i class="bi bi-box-open fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                                <p class="mb-0 fw-medium">No se encontraron productos asociados a esta compra.</p>
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
    </div>
</div>

<?= $this->endSection() ?>