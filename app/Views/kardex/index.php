<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Visualización de existencias, costos promedio y stock por almacén.</p>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-warehouse me-1"></i> Stock Actual en Almacenes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Local / Almacén</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-end">Costo Promedio (S/)</th>
                            <th class="text-center">Mín / Máx</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stock)): ?>
                            <?php foreach ($stock as $key => $item): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><span class="fw-bold"><?= esc($item['producto']) ?></span></td>
                                    <td><?= esc($item['local']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $item['stockactual'] <= $item['minima'] ? 'danger' : 'success' ?> fs-6">
                                            <?= esc($item['stockactual']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">S/ <?= number_format($item['costopromedio'], 2) ?></td>
                                    <td class="text-center text-muted small">
                                        Min: <?= $item['minima'] ?> | Máx: <?= $item['maxima'] ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('kardex/historial/' . $item['idkardex']) ?>" class="btn btn-info btn-sm text-white" title="Ver Historial de Movimientos">
                                            <i class="fas fa-list-alt me-1"></i> Kardex
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>