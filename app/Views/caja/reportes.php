<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <div class="df-page-heading">
        <div>
            <h1>Reportes de caja y ventas</h1>
            <p>Los cobros usan la fecha del pago; los productos usan la fecha de venta y excluyen anulaciones.</p>
        </div>
        <a class="btn btn-light border" href="<?= base_url('admin/caja') ?>">
            <i class="bi bi-arrow-left me-1"></i> Volver a caja
        </a>
    </div>
    <form method="get" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Agrupar</label><select name="granularidad" class="form-select">
                    <option value="DIA" <?= $filtros['granularidad'] === 'DIA' ? 'selected' : '' ?>>Día</option>
                    <option value="MES" <?= $filtros['granularidad'] === 'MES' ? 'selected' : '' ?>>Mes</option>
                </select></div>
            <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" value="<?= esc($filtros['desde'], 'attr') ?>" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" value="<?= esc($filtros['hasta'], 'attr') ?>" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Línea</label><select name="idlinea" class="form-select">
                    <option value="">Todas</option><?php foreach ($lineas as $fila): ?><option value="<?= (int) $fila['idlinea'] ?>" <?= $filtros['idlinea'] === (int) $fila['idlinea'] ? 'selected' : '' ?>><?= esc($fila['nombre']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2"><label class="form-label">Vendedor</label><select name="idvendedor" class="form-select" <?= !$esAdmin ? 'disabled' : '' ?>>
                    <option value="">Todos</option><?php foreach ($vendedores as $fila): ?><option value="<?= (int) $fila['idusuario'] ?>" <?= $filtros['idvendedor'] === (int) $fila['idusuario'] ? 'selected' : '' ?>><?= esc($fila['nombreusuario']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2"><label class="form-label">Técnico</label><select name="idtecnico" class="form-select">
                    <option value="">Todos</option><?php foreach ($tecnicos as $fila): ?><option value="<?= (int) $fila['idtecnico'] ?>" <?= $filtros['idtecnico'] === (int) $fila['idtecnico'] ? 'selected' : '' ?>><?= esc(trim($fila['nombres'] . ' ' . $fila['apellidos'])) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-4"><label class="form-label">Producto</label><select name="idproducto" class="form-select">
                    <option value="">Todos</option><?php foreach ($productos as $fila): ?><option value="<?= (int) $fila['idproducto'] ?>" <?= $filtros['idproducto'] === (int) $fila['idproducto'] ? 'selected' : '' ?>><?= esc($fila['nombre']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2 align-self-end"><button class="btn btn-primary w-100">Aplicar filtros</button></div>
        </div>
    </form>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Cobros reales por medio</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Línea</th>
                        <th>Vendedor</th>
                        <th>Técnico</th>
                        <th>Medio</th>
                        <th class="text-end">Operaciones</th>
                        <th class="text-end">Cobrado</th>
                        <th class="text-end">De ventas anuladas</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($cobros as $fila): ?><tr>
                            <td><?= esc($fila['periodo']) ?></td>
                            <td><?= esc($fila['linea']) ?></td>
                            <td><?= esc($fila['vendedor']) ?></td>
                            <td><?= esc($fila['tecnico']) ?></td>
                            <td><?= esc($fila['tipopago']) ?></td>
                            <td class="text-end"><?= (int) $fila['operaciones'] ?></td>
                            <td class="text-end">S/ <?= number_format((float) $fila['monto'], 2) ?></td>
                            <td class="text-end">S/ <?= number_format((float) $fila['monto_anulado'], 2) ?></td>
                        </tr><?php endforeach; ?><?php if (!$cobros): ?><tr>
                            <td colspan="8" class="text-center py-4">Sin cobros en el período.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2 class="h5 mb-0">Ventas activas por producto</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Línea</th>
                        <th>Vendedor</th>
                        <th>Técnico de entrega</th>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Importe bruto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productosReporte as $fila): ?><tr>
                            <td><?= esc($fila['periodo']) ?></td>
                            <td><?= esc($fila['linea']) ?></td>
                            <td><?= esc($fila['vendedor']) ?></td>
                            <td><?= esc($fila['tecnico']) ?></td>
                            <td><?= esc($fila['producto']) ?></td>
                            <td class="text-end"><?= number_format((float) $fila['cantidad'], 2) ?></td>
                            <td class="text-end">S/ <?= number_format((float) $fila['importe_bruto'], 2) ?></td>
                        </tr><?php endforeach; ?><?php if (!$productosReporte): ?><tr>
                            <td colspan="7" class="text-center py-4">Sin ventas activas en el período.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Otros movimientos de caja</h2>
            <p class="small text-muted mb-0">Aportes, retiros, gastos, ajustes y reembolsos; no se atribuyen artificialmente a productos.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Local / Caja</th>
                        <th>Usuario</th>
                        <th>Medio</th>
                        <th>Tipo / Concepto</th>
                        <th class="text-end">Operaciones</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($movimientosReporte as $fila): ?><tr>
                            <td><?= esc($fila['periodo']) ?></td>
                            <td><?= esc($fila['nombrelocal'] . ' · ' . $fila['caja']) ?></td>
                            <td><?= esc($fila['nombreusuario']) ?></td>
                            <td><?= esc($fila['tipopago']) ?></td>
                            <td><?= esc($fila['tipo'] . ' · ' . $fila['concepto']) ?></td>
                            <td class="text-end"><?= (int) $fila['operaciones'] ?></td>
                            <td class="text-end <?= $fila['tipo'] === 'EGRESO' ? 'text-danger' : 'text-success' ?>"><?= $fila['tipo'] === 'EGRESO' ? '-' : '+' ?> S/ <?= number_format((float) $fila['monto'], 2) ?></td>
                        </tr><?php endforeach; ?><?php if (!$movimientosReporte): ?><tr>
                            <td colspan="7" class="text-center py-4">Sin movimientos adicionales en el período.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>