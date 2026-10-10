<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Almacén / Local: <span class="fw-bold text-dark"><?= esc($kardex['local']) ?></span></p>
        </div>
        <div>
            <a href="<?= base_url('kardex') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver a Saldos</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Stock Actual</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($kardex['stockactual']) ?> unidades</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Costo Promedio</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ <?= number_format($kardex['costopromedio'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history me-1"></i> Historial Cronológico de Movimientos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción / Motivo</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Saldo Resultante</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?= esc($mov['fecha']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['tipo'] == 'ENTRADA' ? 'success' : 'danger' ?>">
                                            <?= esc($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= esc($mov['descripcion']) ?> 
                                        <?php if (!empty($mov['motivo'])): ?>
                                            <br><small class="text-muted">Motivo: <?= esc($mov['motivo']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold <?= $mov['tipo'] == 'ENTRADA' ? 'text-success' : 'text-danger' ?>">
                                        <?= $mov['tipo'] == 'ENTRADA' ? '+' : '-' ?><?= esc($mov['cantidad']) ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= esc($mov['saldo']) ?></td>
                                    <td><?= esc($mov['usuario'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay movimientos registrados para este producto en este local.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>