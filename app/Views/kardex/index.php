<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <!-- Cabecera de la Sección -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Visualización en tiempo real de existencias, costos promedio y stock por almacén.</p>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-warehouse text-primary me-2"></i> Stock Actual en Almacenes
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="text-uppercase fs-7 text-secondary tracking-wider" style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                        <tr>
                            <th class="py-3 px-4" style="width: 50px;">#</th>
                            <th class="py-3">Producto</th>
                            <th class="py-3">Local / Almacén</th>
                            <th class="py-3 text-center" style="width: 130px;">Stock Actual</th>
                            <th class="py-3 text-end" style="width: 150px;">Costo Promedio (S/)</th>
                            <th class="py-3 text-center" style="width: 160px;">Mín / Máx</th>
                            <th class="py-3 text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (!empty($stock)): ?>
                            <?php foreach ($stock as $key => $item): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td class="px-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= esc($item['producto']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-secondary"><?= esc($item['local']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php $esCritico = $item['stockactual'] <= $item['minima']; ?>
                                        <span class="badge bg-<?= $esCritico ? 'danger' : 'success' ?>-subtle text-<?= $esCritico ? 'danger' : 'success' ?> border border-<?= $esCritico ? 'danger' : 'success' ?>-subtle px-3 py-2 fw-bold rounded-pill">
                                            <?= esc($item['stockactual']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        S/ <?= number_format($item['costopromedio'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            Min: <?= $item['minima'] ?> | Máx: <?= $item['maxima'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center px-4">
                                        <a href="<?= base_url('kardex/historial/' . $item['idkardex']) ?>" class="btn btn-outline-info btn-sm rounded-pill px-3" title="Ver Historial de Movimientos en Kardex">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="my-3">
                                        <i class="fas fa-boxes fa-3x text-light mb-3"></i>
                                        <p class="mb-0">No hay registros de stock disponibles en los almacenes.</p>
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