<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <div class="df-page-heading d-flex justify-content-between align-items-center">
        <div>
            <h1>Caja y cobros</h1>
            <p>Independiente de caja chica. Cada usuario opera una apertura a la vez.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= base_url('admin/caja/reportes') ?>"><i class="bi bi-bar-chart-line me-1"></i>Reportes</a>
            <?php if ($esAdmin): ?><a class="btn btn-outline-primary" href="<?= base_url('admin/caja/configuracion') ?>"><i class="bi bi-gear me-1"></i>Configurar cajas y cuentas</a><?php endif; ?>
        </div>
    </div>

    <?php if ($propias): ?>
        <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <i class="bi bi-unlock-fill me-1"></i> Tu caja está abierta:
                <?php foreach ($propias as $abierta): ?>
                    <a class="fw-bold text-decoration-underline ms-1" href="<?= base_url('admin/caja/detalles/' . (int) $abierta['idapertura']) ?>">
                        <?= esc($abierta['caja']) ?> · Apertura #<?= (int) $abierta['idapertura'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="d-flex gap-2">
                <?php foreach ($propias as $abierta): ?>
                    <a class="btn btn-sm btn-outline-success bg-white fw-semibold" href="<?= base_url('admin/caja/detalles/' . (int) $abierta['idapertura']) ?>">
                        <i class="bi bi-calculator me-1"></i> Ir a arqueo
                    </a>
                <?php endforeach; ?>
                <a class="btn btn-sm btn-success fw-semibold" href="<?= base_url('admin/ventas/crear') ?>">
                    <i class="bi bi-cart-plus me-1"></i> Registrar venta
                </a>
            </div>
        </div>
    <?php else: ?>
        <form class="card card-body mb-4" method="post" action="<?= base_url('admin/caja/abrir') ?>">
            <?= csrf_field() ?>
            <h2 class="h5">Abrir caja</h2>
            <div class="row g-3 align-items-end">
                <div class="col-md-6"><label for="caja" class="form-label">Punto de venta / Caja</label>
                    <select id="caja" name="idcaja" class="form-select" required>
                        <option value="">Selecciona</option>
                        <?php foreach ($cajas as $caja): ?><option value="<?= (int) $caja['idcaja'] ?>"><?= esc($caja['nombrelocal'] . ' · ' . $caja['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label for="inicial" class="form-label">Efectivo inicial (S/)</label><input id="inicial" name="montoinicial" class="form-control" type="number" min="0" max="99999999.99" step="0.01" value="<?= esc(old('montoinicial', '0.00'), 'attr') ?>" required></div>
                <div class="col-md-3"><button class="btn btn-primary w-100" <?= !$cajas ? 'disabled' : '' ?>>Abrir caja</button></div>
            </div>
            <?php if (!$cajas): ?><p class="text-warning mt-3 mb-0">No tienes cajas disponibles. El administrador debe crear una y asignarte su local en Usuarios.</p><?php endif; ?>
        </form>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="h5 mb-0">Últimas 50 aperturas <?= $esAdmin ? '(todos los usuarios)' : 'de tu usuario' ?></h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Local / Caja</th>
                        <th>Usuario</th>
                        <th>Apertura</th>
                        <th>Inicial</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aperturas as $fila): ?>
                        <?php $estaAbierta = strtoupper(trim($fila['estado'])) === 'ABIERTA'; ?>
                        <tr>
                            <td class="fw-semibold"><?= (int) $fila['idapertura'] ?></td>
                            <td><?= esc($fila['nombrelocal'] . ' · ' . $fila['caja']) ?></td>
                            <td><?= esc($fila['nombreusuario']) ?></td>
                            <td><?= esc($fila['fechaapertura']) ?></td>
                            <td class="fw-semibold">S/ <?= number_format((float) $fila['montoinicial'], 2) ?></td>
                            <td>
                                <span class="badge <?= $estaAbierta ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                                    <?= esc($fila['estado']) ?>
                                </span>
                                <?= !empty($fila['idcierreadministrativo']) ? '<span class="small text-muted ms-1">· Administrativa</span>' : '' ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('admin/caja/detalles/' . (int) $fila['idapertura']) ?>"
                                    class="btn btn-sm <?= $estaAbierta ? 'btn-primary fw-semibold shadow-sm' : 'btn-outline-secondary' ?>">
                                    <i class="bi bi-cash-coin me-1"></i> Arqueo y cobros
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$aperturas): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Todavía no hay aperturas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>