<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <?= view('partials/ruta', ['uiSection' => 'Posventa', 'uiCurrent' => 'Garantías', 'uiSectionUrl' => null]) ?>
    <div class="df-page-heading">
        <div><h1>Garantías</h1><p>Recepción y seguimiento de equipos vendidos, incluidos los componentes de combos.</p></div>
        <?php if ($puedeGestionar): ?><a href="<?= base_url('admin/garantias/crear') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Registrar caso</a><?php endif; ?>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="get" action="<?= base_url('admin/garantias') ?>" class="row g-2 mb-4">
                <div class="col-md-7"><label for="buscarGarantia" class="form-label">Serie, DNI/documento, comprobante o producto</label><input type="search" id="buscarGarantia" name="q" maxlength="100" value="<?= esc($buscar, 'attr') ?>" class="form-control"></div>
                <div class="col-md-3"><label for="estadoGarantia" class="form-label">Estado</label><select id="estadoGarantia" name="estado" class="form-select"><option value="">Todos</option><?php foreach ($etiquetas as $codigo => $nombre): ?><option value="<?= $codigo ?>" <?= $estado === $codigo ? 'selected' : '' ?>><?= esc($nombre) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-primary" type="submit">Buscar</button><a class="btn btn-light border" href="<?= base_url('admin/garantias') ?>" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a></div>
            </form>
            <div class="d-flex justify-content-between mb-3"><h2 class="h6">Casos registrados</h2><span class="small text-muted"><?= (int) $resultado['total'] ?> caso(s)</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Caso / ingreso</th><th>Cliente</th><th>Venta / equipo</th><th>Estado</th><th>Proveedor</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($resultado['items'] as $caso): ?>
                        <tr>
                            <td><strong>GAR-<?= (int) $caso['idgarantia'] ?></strong><small class="d-block text-muted"><?= esc($caso['fechaingreso']) ?></small></td>
                            <td><?= esc($caso['cliente']) ?><small class="d-block text-muted"><?= esc($caso['numerodoc']) ?></small></td>
                            <td><?= esc($caso['serie'] . '-' . $caso['numero']) ?><strong class="d-block"><?= esc($caso['producto']) ?></strong><small><?= esc($caso['numeroserie'] ?: 'Sin serie') ?></small></td>
                            <td><span class="badge <?= $caso['estado'] === 'ENTREGADO_CLIENTE' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= esc($etiquetas[$caso['estado']] ?? $caso['estado']) ?></span></td>
                            <td><?= esc($caso['proveedor'] ?: 'Sin envío a proveedor') ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary text-nowrap" href="<?= base_url('admin/garantias/detalles/' . $caso['idgarantia']) ?>">Ver caso</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$resultado['items']): ?><tr><td colspan="6" class="text-center text-muted py-5">No hay casos que coincidan con los filtros.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($resultado['paginas'] > 1): ?>
                <nav aria-label="Paginación de garantías"><ul class="pagination pagination-sm justify-content-end">
                <?php foreach (array_unique([1, max(1, $resultado['pagina'] - 1), $resultado['pagina'], min($resultado['paginas'], $resultado['pagina'] + 1), $resultado['paginas']]) as $pagina): ?>
                    <li class="page-item <?= $pagina === $resultado['pagina'] ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('admin/garantias?' . http_build_query(['q' => $buscar, 'estado' => $estado, 'pagina' => $pagina])) ?>"><?= (int) $pagina ?></a></li>
                <?php endforeach; ?>
                </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
