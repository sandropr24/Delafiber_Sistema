<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Almacén: <strong><?= esc($kardex['local']) ?></strong> | Stock Actual: <span class="badge bg-success"><?= $kardex['stockactual'] ?></span></p>
        </div>
        <div>
            <a href="<?= base_url('kardex') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver al Kardex</a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history me-1"></i> Historial de Movimientos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Motivo / Descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Saldo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movimientos) && is_array($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?= esc($mov['fecha']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($mov['tipo'] == 'ENTRADA') ? 'success' : 'danger' ?>">
                                            <?= esc($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= esc($mov['motivo'] ?? $mov['descripcion']) ?></td>
                                    <td class="text-center fw-bold"><?= esc($mov['cantidad']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= esc($mov['saldo']) ?></td>
                                    <td><?= esc($mov['usuario'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay movimientos registrados para este producto en este almacén.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>