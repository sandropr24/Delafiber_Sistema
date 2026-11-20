<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<?php
$id = (int) $apertura['idapertura'];
$puedeOperar = $puedeOperar ?? $puedeCerrar;
$puedeCerrarAdmin = $puedeCerrarAdmin ?? false;
$movimientos = $movimientos ?? [];
$medios = $medios ?? [];
$estaAbierta = strtoupper(trim($apertura['estado'])) === 'ABIERTA';
?>
<div class="container-fluid df-page">
    <div class="df-page-heading d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="mb-0">Apertura #<?= $id ?></h1>
                <span class="badge <?= $estaAbierta ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                    <?= esc($apertura['estado']) ?>
                </span>
            </div>
            <p class="text-muted mb-0 mt-1">
                <i class="bi bi-shop me-1"></i><?= esc($apertura['nombrelocal'] . ' · ' . $apertura['caja']) ?>
                <span class="mx-2">|</span>
                <i class="bi bi-person me-1"></i><?= esc($apertura['nombreusuario']) ?>
            </p>
        </div>
        <a class="btn btn-light border" href="<?= base_url('admin/caja') ?>">
            <i class="bi bi-arrow-left me-1"></i> Volver a caja
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-body py-2 px-3 bg-light border-0">
                <small class="text-muted">Efectivo inicial</small>
                <strong class="fs-5">S/ <?= number_format((float) $apertura['montoinicial'], 2) ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body py-2 px-3 bg-light border-0">
                <small class="text-muted">Fecha de apertura</small>
                <strong class="fs-6"><?= esc($apertura['fechaapertura']) ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body py-2 px-3 bg-light border-0">
                <small class="text-muted">Fecha de cierre</small>
                <strong class="fs-6"><?= $apertura['fechacierre'] ? esc($apertura['fechacierre']) : 'En curso' ?></strong>
            </div>
        </div>
    </div>

    <?php if (!empty($apertura['idcierreadministrativo'])): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Cierre administrativo</strong> registrado el <?= esc($apertura['fecha_cierre_administrativo']) ?>.
            Motivo: <?= esc($apertura['observacion_administrativa']) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        El esperado incluye el efectivo inicial, los cobros y los movimientos registrados. Los reembolsos y demás egresos se descuentan del medio correspondiente. Los pagos históricos sin apertura no se asignan automáticamente.
    </div>

    <?php if ($puedeOperar): ?>
        <form method="post" action="<?= base_url('admin/caja/movimiento/' . $id) ?>" class="card mb-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token_operacion" value="<?= esc(bin2hex(random_bytes(32)), 'attr') ?>">
            <div class="card-header bg-white">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-arrow-left-right me-1 text-primary"></i> Registrar ingreso o egreso</h2>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="INGRESO">Ingreso (+)</option>
                            <option value="EGRESO">Egreso (-)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Concepto</label>
                        <select name="concepto" class="form-select" required>
                            <option value="APORTE">Aporte</option>
                            <option value="RETIRO">Retiro</option>
                            <option value="GASTO">Gasto</option>
                            <option value="AJUSTE">Ajuste</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Medio</label>
                        <select name="idtipopago" class="form-select" required>
                            <?php foreach ($medios as $medio): ?>
                                <option value="<?= (int) $medio['idtipopago'] ?>"><?= esc($medio['tipopago']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Monto (S/)</label>
                        <input name="monto" type="number" min="0.01" max="99999999.99" step="0.01" placeholder="0.00" class="form-control text-end" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Descripción</label>
                        <div class="d-flex gap-2">
                            <input name="descripcion" minlength="5" maxlength="255" placeholder="Ej. Pago de movilidad" class="form-control" required>
                            <button class="btn btn-outline-primary text-nowrap fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i> Registrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <form method="post" action="<?= base_url(($puedeCerrarAdmin ? 'admin/caja/cierre-administrativo/' : 'admin/caja/cerrar/') . $id) ?>" class="card mb-4 border-primary-subtle">
        <?= csrf_field() ?>
        <div class="card-header bg-white">
            <h2 class="h6 fw-bold mb-0">
                <i class="bi bi-calculator me-1 text-primary"></i>
                <?= $estaAbierta ? 'Arqueo y cierre' : 'Arqueo guardado al cerrar' ?>
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Medio</th>
                        <th class="text-end">Esperado</th>
                        <th class="text-end" style="width: 190px;">Contado</th>
                        <th class="text-end" style="width: 160px;">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arqueo as $fila): ?>
                        <?php $esperado = (float) $fila['montoesperado']; ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($fila['tipopago']) ?></td>
                            <td class="text-end">S/ <?= number_format($esperado, 2) ?></td>
                            <td class="text-end">
                                <?php if ($puedeCerrar || $puedeCerrarAdmin): ?>
                                    <div class="input-group input-group-sm justify-content-end">
                                        <span class="input-group-text">S/</span>
                                        <input aria-label="Monto contado en <?= esc($fila['tipopago'], 'attr') ?>"
                                            class="form-control text-end fw-semibold input-contado"
                                            style="max-width: 130px;"
                                            type="number"
                                            name="contados[<?= (int) $fila['idtipopago'] ?>]"
                                            data-esperado="<?= $esperado ?>"
                                            min="0" max="99999999.99" step="0.01" placeholder="0.00" required
                                            value="<?= esc(old('contados.' . $fila['idtipopago'], ''), 'attr') ?>">
                                    </div>
                                <?php elseif (isset($fila['montocontado'])): ?>
                                    <span class="fw-semibold">S/ <?= number_format((float) $fila['montocontado'], 2) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold celda-diferencia">
                                <?php if (isset($fila['diferencia'])): ?>
                                    <?php $dif = (float) $fila['diferencia']; ?>
                                    <span class="<?= $dif < 0 ? 'text-danger' : ($dif > 0 ? 'text-primary' : 'text-success') ?>">
                                        <?= ($dif > 0 ? '+' : '') . 'S/ ' . number_format($dif, 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fw-normal small">Al cerrar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($puedeCerrar || $puedeCerrarAdmin || $apertura['observacion']): ?>
            <div class="card-body border-top bg-light">
                <?php if ($puedeCerrar || $puedeCerrarAdmin): ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="observacion" class="form-label small fw-semibold">
                                Observación <?= $puedeCerrarAdmin ? '(motivo obligatorio del cierre administrativo)' : '(obligatoria si hay diferencias)' ?>
                            </label>
                            <input id="observacion" name="observacion" class="form-control"
                                placeholder="Ingresa una observación o el motivo si existe diferencia..."
                                maxlength="<?= $puedeCerrarAdmin ? 500 : 150 ?>"
                                <?= $puedeCerrarAdmin ? 'minlength="10" required' : '' ?>
                                value="<?= esc(old('observacion', ''), 'attr') ?>">
                        </div>
                        <div class="col-md-4">
                            <button class="btn <?= $puedeCerrarAdmin ? 'btn-warning fw-bold' : 'btn-primary fw-semibold' ?> w-100"
                                onclick="return confirm('¿Confirmas cerrar la caja? No podrás registrar más cobros en esta apertura.');">
                                <i class="bi bi-lock-fill me-1"></i>
                                <?= $puedeCerrarAdmin ? 'Cerrar caja administrativamente' : 'Cerrar caja y guardar arqueo' ?>
                            </button>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2">El servidor recalcula los importes al cerrar. No podrás registrar más cobros en esta apertura.</p>
                <?php elseif ($apertura['observacion']): ?>
                    <p class="mb-0 small"><strong>Observación:</strong> <?= esc($apertura['observacion']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-journal-text me-1 text-primary"></i> Movimientos de caja</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo / concepto</th>
                        <th>Medio</th>
                        <th>Usuario</th>
                        <th>Descripción</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td class="small"><?= esc($movimiento['fecha']) ?></td>
                            <td>
                                <span class="badge <?= $movimiento['tipo'] === 'EGRESO' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                                    <?= esc($movimiento['tipo']) ?>
                                </span>
                                <span class="small ms-1"><?= esc($movimiento['concepto']) ?></span>
                            </td>
                            <td><?= esc($movimiento['tipopago']) ?></td>
                            <td class="small"><?= esc($movimiento['nombreusuario']) ?></td>
                            <td>
                                <?= esc($movimiento['descripcion']) ?>
                                <?php if (!empty($movimiento['idventa'])): ?>
                                    <a class="badge bg-light text-primary border ms-1 text-decoration-none" href="<?= base_url('admin/ventas/detalles/' . (int) $movimiento['idventa']) ?>">
                                        Venta #<?= (int) $movimiento['idventa'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold <?= $movimiento['tipo'] === 'EGRESO' ? 'text-danger' : 'text-success' ?>">
                                <?= $movimiento['tipo'] === 'EGRESO' ? '-' : '+' ?> S/ <?= number_format((float) $movimiento['monto'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$movimientos): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">Sin movimientos adicionales.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-receipt me-1 text-primary"></i> Cobros vinculados</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha / Venta</th>
                        <th>Medio</th>
                        <th>Cuenta / Operación</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td>
                                <span class="small text-muted d-block"><?= esc($pago['fecha']) ?></span>
                                <a class="fw-semibold" href="<?= base_url('admin/ventas/detalles/' . (int) $pago['idventa']) ?>">
                                    <?= esc($pago['serie'] . '-' . $pago['numero']) ?>
                                </a>
                                <?php if ($pago['fechaanulacion']): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">Venta anulada; revisar reembolso</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($pago['tipopago']) ?></td>
                            <td class="small">
                                <?= esc(trim(($pago['banco'] ?? '') . ' ' . ($pago['numerocuenta'] ?? ''))) ?: '—' ?>
                                <div class="text-muted">Ref: <?= esc($pago['referencia'] ?? '—') ?></div>
                            </td>
                            <td class="text-end fw-semibold">S/ <?= number_format((float) $pago['monto'], 2) ?></td>
                            <td class="text-end">
                                <?php if ($pago['voucher']): ?>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="<?= base_url('admin/caja/voucher/' . (int) $pago['idpagoventa']) ?>">
                                        <i class="bi bi-image me-1"></i> Ver foto
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$pagos): ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">Sin cobros vinculados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.input-contado').forEach(input => {
        input.addEventListener('input', () => {
            const fila = input.closest('tr');
            const celdaDif = fila.querySelector('.celda-diferencia');
            const esperado = parseFloat(input.dataset.esperado) || 0;

            if (input.value === '') {
                celdaDif.innerHTML = '<span class="text-muted fw-normal small">Al cerrar</span>';
                return;
            }

            const contado = parseFloat(input.value) || 0;
            const dif = +(contado - esperado).toFixed(2);

            if (dif === 0) {
                celdaDif.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>S/ 0.00</span>';
            } else if (dif > 0) {
                celdaDif.innerHTML = `<span class="text-primary">+S/ ${dif.toFixed(2)}</span>`;
            } else {
                celdaDif.innerHTML = `<span class="text-danger">-S/ ${Math.abs(dif).toFixed(2)}</span>`;
            }
        });
    });
</script>
<?= $this->endSection() ?>