<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Información detallada del comprobante e ítems ingresados al inventario.</p>
        </div>
        <div>
            <a href="<?= base_url('compras') ?>" class="btn btn-secondary btn-sm shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver a Compras</a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle me-1"></i> Datos del Comprobante</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small d-block">PROVEEDOR</span>
                        <span class="fw-bold text-dark"><?= esc($compra['proveedor'] ?? 'N/A') ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">RUC</span>
                        <code><?= esc($compra['ruc'] ?? 'N/A') ?></code>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">TIPO DE COMPROBANTE</span>
                        <?php
                        $badgeColor = ($compra['tipocomprobante'] == 'FACTURA') ? 'bg-success' : 'bg-info text-dark';
                        ?>
                        <span class="badge <?= $badgeColor ?> px-2 py-1"><?= esc($compra['tipocomprobante']) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">SERIE / NÚMERO</span>
                        <span class="fw-medium"><?= esc($compra['serie']) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">FECHA DE COMPRA</span>
                        <span class="text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> <?= date('d/m/Y', strtotime($compra['fechacompra'])) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">FECHA DE REGISTRO</span>
                        <span class="text-dark"><i class="far fa-clock text-muted me-1"></i> <?= esc($compra['fecharegistro']) ?></span>
                    </div>

                    <hr class="text-muted">

                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                        <span class="fw-bold text-secondary">Total Compra:</span>
                        <h4 class="text-success fw-bold mb-0">S/ <?= number_format($compra['total'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 mb-4">
            <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-boxes me-1"></i> Productos en esta Compra</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" width="100%" cellspacing="0">
                            <thead class="table-light text-uppercase fs-7 text-secondary">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center" style="width: 100px;">Cantidad</th>
                                    <th class="text-end" style="width: 140px;">P. Compra (S/)</th>
                                    <th class="text-end" style="width: 140px;">Subtotal (S/)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detalles) && is_array($detalles)): ?>
                                    <?php foreach ($detalles as $det): ?>
                                        <?php $subtotal = $det['cantidad'] * $det['preciocompra']; ?>
                                        <tr>
                                            <td>
                                                <span class="fw-medium text-dark"><?= esc($det['producto']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary px-2 py-1"><?= $det['cantidad'] ?></span>
                                            </td>
                                            <td class="text-end">S/ <?= number_format($det['preciocompra'], 2) ?></td>
                                            <td class="text-end fw-bold text-success">S/ <?= number_format($subtotal, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-box-open fa-2x mb-2 text-black-50"></i><br>
                                            No se encontraron productos asociados a esta compra.
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