<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<div class="container-fluid px-4 py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i><?= esc($titulo) ?>
            </h1>
            <p class="text-muted small mb-0">Detalle completo del comprobante de pago emitido y afectación en el inventario.</p>
        </div>
        <div>
            <a href="<?= base_url('ventas') ?>" class="btn btn-sm btn-outline-secondary bg-white shadow-sm px-3 py-2">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="fas fa-user-tie fa-lg"></i>
                        </div>
                        <h6 class="fw-bold text-dark m-0">Datos del Cliente</h6>
                    </div>
                    <div class="ps-1">
                        <div class="mb-2">
                            <span class="text-muted small d-block">Cliente / Razón Social</span>
                            <span class="text-dark fw-semibold"><?= esc($venta['cliente'] ?: 'Cliente General') ?></span>
                        </div>
                        <div class="mb-0">
                            <span class="text-muted small d-block mb-1">N° Documento</span>
                            <span class="badge bg-light text-secondary border px-2 py-1.5 font-monospace fs-7">
                                <?= esc($venta['numerodoc'] ?? 'Sin Documento') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="fas fa-receipt fa-lg"></i>
                        </div>
                        <h6 class="fw-bold text-dark m-0">Comprobante y Sucursal</h6>
                    </div>
                    <div class="ps-1">
                        <div class="mb-2">
                            <span class="text-muted small d-block mb-1">Comprobante</span>
                            <span class="badge bg-primary px-2 py-1 me-1"><?= esc($venta['tipodocumento']) ?></span>
                            <span class="text-dark fw-bold"><?= esc($venta['serie']) ?> - <?= esc($venta['numero']) ?></span>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Local / Sucursal</span>
                                <span class="text-secondary fw-medium"><?= esc($venta['local']) ?></span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha de Emisión</span>
                                <span class="text-secondary fw-medium"><?= esc($venta['fecha']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border rounded-3 overflow-hidden shadow-sm mb-4" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-boxes text-primary me-2"></i>Productos Retirados del Inventario
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted tracking-wider">
                            <tr>
                                <th class="ps-4 py-3">Producto</th>
                                <th class="text-center py-3" style="width: 120px;">Cantidad</th>
                                <th class="text-end py-3" style="width: 150px;">P. Venta Unit.</th>
                                <th class="text-end pe-4 py-3" style="width: 150px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php if (!empty($detalles)): ?>
                                <?php foreach ($detalles as $det): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-cube text-muted me-2 opacity-50"></i>
                                                <span><?= esc($det['producto']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center py-3">
                                            <span class="badge bg-light text-dark border px-2.5 py-1.5 font-monospace">
                                                <?= esc($det['cantidad']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-secondary py-3 font-monospace">
                                            S/ <?= number_format($det['precioventa'], 2) ?>
                                        </td>
                                        <td class="text-end pe-4 fw-bold text-dark py-3 font-monospace">
                                            S/ <?= number_format($det['cantidad'] * $det['precioventa'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5 bg-light bg-opacity-25">
                                        <div class="py-4">
                                            <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-40"></i>
                                            <p class="mb-0 fw-medium">No hay detalles registrados para esta venta.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light border-top">
                            <tr>
                                <td colspan="3" class="text-end fw-bold py-3 text-secondary text-uppercase fs-7 tracking-wider">Total General:</td>
                                <td class="text-end pe-4 fw-bold text-primary fs-4 py-3 font-monospace">
                                    S/ <?= number_format($venta['total'], 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <h6 class="fw-bold text-dark m-0">Formas de Pago</h6>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7 text-muted tracking-wider">
                                <tr>
                                    <th class="ps-4 py-3">Método</th>
                                    <th class="text-end pe-4 py-3">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                <?php if (!empty($pagos)): ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                    <?= esc($pago['tipopago']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-dark py-3 font-monospace">
                                                S/ <?= number_format($pago['monto'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">
                                            Sin registros de pago.
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