<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Gestión de ingresos de mercadería y control de compras.</p>
        </div>
        <div>
            <a href="<?= base_url('compras/nuevo') ?>" class="btn btn-primary shadow-sm"><i class="fas fa-plus me-1"></i> Nueva Compra</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Historial de Compras</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light text-uppercase fs-7 text-secondary">
                        <tr>
                            <th style="width: 60px;">#ID</th>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                            <th>Serie / Nro</th>
                            <th>Proveedor</th>
                            <th class="text-end">Total (S/)</th>
                            <th class="text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($compras) && is_array($compras)): ?>
                            <?php foreach ($compras as $c): ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?= $c['idcompra'] ?></td>
                                    <td>
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        <?= date('d/m/Y', strtotime($c['fechacompra'])) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeColor = ($c['tipocomprobante'] == 'FACTURA') ? 'bg-success' : 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?= $badgeColor ?> px-2 py-1"><?= esc($c['tipocomprobante']) ?></span>
                                    </td>
                                    <td><code><?= esc($c['serie']) ?></code></td>
                                    <td>
                                        <span class="text-dark fw-medium"><?= esc($c['proveedor'] ?? 'Sin Proveedor') ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        S/ <?= number_format($c['total'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('compras/detalles/' . $c['idcompra']) ?>" class="btn btn-outline-info btn-sm rounded-pill px-3" title="Ver detalles">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-2x mb-2 text-black-50"></i><br>
                                    No hay compras registradas hasta el momento.
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