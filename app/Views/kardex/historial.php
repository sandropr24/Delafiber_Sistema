<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">
                Almacén: <strong class="text-dark"><?= esc($kardex['local']) ?></strong> |
                Stock Actual: <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold rounded-pill ms-1"><?= $kardex['stockactual'] ?></span>
            </p>
        </div>
        <div>
            <a href="<?= base_url('kardex') ?>" class="btn btn-secondary btn-sm shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver a Kardex</a>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-history text-primary me-2"></i> Historial de Movimientos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" width="100%" cellspacing="0">
                    <thead class="table-light text-uppercase fs-7 text-secondary tracking-wider">
                        <tr>
                            <th class="py-3 px-4" style="width: 250px;">Fecha</th>
                            <th class="py-3" style="width: 220px;">Tipo</th>
                            <th class="py-3" style="width: 300px;">Motivo / Descripción</th>
                            <th class="py-3 text-center" style="width: 120px;">Cantidad</th>
                            <th class="py-3 text-center" style="width: 200px;">Saldo</th>
                            <th class="py-3" style="width: 100px;">Usuario</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($movimientos) && is_array($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr class="border-bottom border-light">
                                    <td class="px-4 text-secondary small font-monospace">
                                        <?= esc(date('d/m/Y', strtotime($mov['fecha']))) ?>
                                    </td>
                                    <td>
                                        <?php $esEntrada = ($mov['tipo'] == 'ENTRADA'); ?>
                                        <span class="badge bg-<?= $esEntrada ? 'success' : 'danger' ?>-subtle text-<?= $esEntrada ? 'success' : 'danger' ?> border border-<?= $esEntrada ? 'success' : 'danger' ?>-subtle px-2 py-1 fw-bold">
                                            <i class="fas fa-arrow-<?= $esEntrada ? 'down' : 'up' ?> fa-xs me-1"></i> <?= esc($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="text-dark">
                                        <?= esc($mov['motivo'] ?? $mov['descripcion']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?= esc($mov['cantidad']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        <?= esc($mov['saldo']) ?>
                                    </td>
                                    <td class="text-secondary small">
                                        <i class="fas fa-user-circle text-muted me-1"></i> <?= esc($mov['usuario'] ?? 'Sistema') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="my-3">
                                        <i class="fas fa-clipboard-list fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay movimientos registrados para este producto en este almacén.</p>
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