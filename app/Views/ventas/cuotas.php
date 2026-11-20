<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/interfaz.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/interfaz.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-ventas ventas-historial">
    <nav class="ventas-breadcrumb" aria-label="Ruta de navegación"><a href="<?= base_url('admin/ventas') ?>">Ventas</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Cartera de cuotas</span></nav>
    <header class="ventas-page-heading">
        <div>
            <h1>Cartera de cuotas</h1>
            <p>Controla vencimientos, pagos parciales y saldos financiados.</p>
        </div><a class="btn ventas-btn-secundario" href="<?= base_url('admin/ventas') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a ventas</a>
    </header>
    <div class="ventas-indicadores mb-4">
        <div class="ventas-indicador"><span class="ventas-indicador-icono"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <div><span>Saldo por cobrar</span><strong>S/ <?= number_format((float) ($pendientes['monto'] ?? 0), 2) ?></strong><small><?= (int) ($pendientes['cantidad'] ?? 0) ?> cuotas pendientes</small></div>
        </div>
        <div class="ventas-indicador ventas-indicador-anulado"><span class="ventas-indicador-icono"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
            <div><span>Saldo vencido</span><strong>S/ <?= number_format((float) ($vencidas['monto'] ?? 0), 2) ?></strong><small><?= (int) ($vencidas['cantidad'] ?? 0) ?> cuotas vencidas</small></div>
        </div>
    </div>
    <form method="get" class="card ventas-filtros mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-6"><label class="form-label" for="buscarCuota">Cliente, documento o comprobante</label><input id="buscarCuota" name="q" value="<?= esc($q, 'attr') ?>" maxlength="100" class="form-control" placeholder="Buscar en la cartera"></div>
                <div class="col-md-3"><label class="form-label" for="estadoCuota">Estado</label><select id="estadoCuota" name="estado" class="form-select"><?php foreach (['VENCIDA' => 'Vencidas', 'PENDIENTE' => 'Por vencer', 'PAGADA' => 'Pagadas', 'TODAS' => 'Todas'] as $valor => $etiqueta): ?><option value="<?= $valor ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 d-flex gap-2"><button class="btn ventas-btn-morado" type="submit">Filtrar</button><a class="btn ventas-btn-secundario" href="<?= base_url('admin/ventas/cuotas') ?>">Limpiar</a></div>
            </div>
        </div>
    </form>
    <div class="card border-0 shadow-sm">
        <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div>
                <h2>Cuotas registradas</h2>
                <p>Se muestran hasta 500 resultados ordenados por vencimiento.</p>
            </div><span class="ventas-etiqueta ms-auto"><?= count($cuotas) ?> resultados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Venta / cliente</th>
                            <th>Cuota</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            <th class="text-end">Importe</th>
                            <th class="text-end">Pendiente</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotas as $cuota): $pendiente = max(0, (float) $cuota['monto'] - (float) $cuota['montopagado']);
                            $vencida = $cuota['estado'] !== 'PAGADA' && $cuota['fechavencimiento'] < date('Y-m-d'); ?><tr>
                                <td class="ps-3"><strong><?= esc($cuota['serie'] . '-' . $cuota['numero']) ?></strong><small class="d-block text-muted"><?= esc($cuota['cliente']) ?> · <?= esc($cuota['numerodoc']) ?></small></td>
                                <td>N.º <?= (int) $cuota['nrocuota'] ?></td>
                                <td><?= esc(date('d/m/Y', strtotime($cuota['fechavencimiento']))) ?><?php if ($vencida): ?><small class="d-block text-danger"><?= (int) $cuota['diasvencida'] ?> días vencida</small><?php endif; ?></td>
                                <td><?php if ($vencida): ?><span class="badge text-bg-danger">Vencida</span><?php elseif ($cuota['estado'] === 'PAGADA'): ?><span class="badge text-bg-success">Pagada</span><?php elseif ($cuota['estado'] === 'PARCIAL'): ?><span class="badge text-bg-warning">Parcial</span><?php else: ?><span class="badge text-bg-secondary">Pendiente</span><?php endif; ?></td>
                                <td class="text-end">S/ <?= number_format((float) $cuota['monto'], 2) ?></td>
                                <td class="text-end fw-semibold">S/ <?= number_format($pendiente, 2) ?></td>
                                <td class="text-end pe-3"><a class="btn btn-sm ventas-btn-secundario" href="<?= base_url('admin/ventas/detalles/' . (int) $cuota['idventa']) ?>"><?= $cuota['estado'] === 'PAGADA' ? 'Ver venta' : 'Registrar cobro' ?></a></td>
                            </tr><?php endforeach; ?>
                        <?php if (!$cuotas): ?><tr>
                                <td colspan="7" class="text-center text-muted py-5">No hay cuotas que coincidan con los filtros.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>