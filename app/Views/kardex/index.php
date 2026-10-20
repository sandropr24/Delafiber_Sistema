<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4" id="viewKardex" data-url-buscar="<?= base_url('kardex/buscar_tabla') ?>">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Visualización en tiempo real de existencias, costos promedio y stock por almacén.</p>
        </div>
    </div>

    <div class="card border rounded-3 shadow-sm p-3 mb-4" style="border-color: #e0e0e0 !important;">
        <div class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="inputBuscarKardex" class="form-control border-start-0" placeholder="Buscar por producto o local...">
                </div>
            </div>
            <div class="col-md-5">
                <div class="input-group">
                    <label class="input-group-text bg-light" for="selectLocal"><i class="bi bi-building text-muted me-1"></i> Local / Almacén</label>
                    <select class="form-select" id="selectLocal">
                        <option value="">Todos los locales</option>
                        <?php foreach ($locales as $loc): ?>
                            <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
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
                <table class="table table-hover align-middle mb-0" width="100%" cellspacing="0">
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
                    <tbody class="bg-white" id="tbodyKardex">
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
                                    <td class="text-center px-4 align-middle">
                                        <a href="<?= base_url('kardex/historial/' . $item['idkardex']) ?>" class="text-secondary link-primary fs-5" title="Ver Historial de Movimientos en Kardex">
                                            <i class="bi bi-eye"></i>
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

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/kardex/index.js') ?>"></script>
<?= $this->endSection() ?>