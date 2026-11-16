<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/interfaz.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/interfaz.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php if (session()->getFlashdata('ventaRegistrada') && !session()->getFlashdata('pedidoVendido') && !session()->getFlashdata('cotizacionVendida')): ?>
<script>try { sessionStorage.removeItem('venta-borrador-<?= (int) session()->get('idusuario') ?>'); } catch (error) { console.warn(error); }</script>
<?php endif; ?>
<?php if ($cotizacionVendida = session()->getFlashdata('cotizacionVendida')): ?>
<script>try { sessionStorage.removeItem('venta-borrador-<?= (int) session('idusuario') ?>-cotizacion-<?= (int) $cotizacionVendida ?>'); } catch (error) { console.warn(error); }</script>
<?php endif; ?>
<?php if ($pedidoVendido = session()->getFlashdata('pedidoVendido')): ?>
<script>try { sessionStorage.removeItem('venta-borrador-<?= (int) session('idusuario') ?>-pedido-<?= (int) $pedidoVendido ?>'); } catch (error) { console.warn(error); }</script>
<?php endif; ?>

<?php
$cantidadVentas = count($ventas ?? []);
$cantidadAnuladas = count(array_filter($ventas ?? [], static fn($venta) => !empty($venta['fechaanulacion'])));
?>
<div class="container-fluid df-ventas ventas-historial">
    <nav class="ventas-breadcrumb" aria-label="Ruta de navegación">
        <span>Comercial</span><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Ventas</span>
    </nav>
    <header class="ventas-page-heading">
        <div>
            <h1>Ventas</h1>
            <p>Consulta tus comprobantes y da seguimiento a cada operación.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/ventas/crear') ?>" class="btn ventas-btn-principal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva venta
            </a>
        </div>
    </header>

    <div class="ventas-indicadores" aria-label="Resumen del listado filtrado">
        <?php foreach ([
            ['Comprobantes', $cantidadVentas, 'bi-receipt', ''],
            ['Ventas activas', $cantidadVentas - $cantidadAnuladas, 'bi-check2-circle', 'ventas-indicador-activo'],
            ['Ventas anuladas', $cantidadAnuladas, 'bi-file-earmark-x', 'ventas-indicador-anulado'],
        ] as [$etiqueta, $cantidad, $icono, $clase]): ?>
            <div class="ventas-indicador <?= $clase ?>">
                <span class="ventas-indicador-icono"><i class="bi <?= $icono ?>" aria-hidden="true"></i></span>
                <div><span><?= $etiqueta ?></span><strong><?= $cantidad ?></strong><small>En este listado</small></div>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="get" class="card ventas-filtros mb-4">
        <div class="card-header ventas-section-header">
            <span class="ventas-resumen-icono"><i class="bi bi-funnel" aria-hidden="true"></i></span>
            <div><h2>Filtrar historial</h2><p>Encuentra una venta por cliente, fecha o estado.</p></div>
        </div>
        <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small" for="filtroVenta">Cliente, documento o comprobante</label><input id="filtroVenta" name="q" value="<?= esc($filtros['q'] ?? '', 'attr') ?>" class="form-control" placeholder="Buscar venta"></div>
            <div class="col-md-2"><label class="form-label small" for="desdeVenta">Desde</label><input id="desdeVenta" type="date" name="desde" value="<?= esc($filtros['desde'] ?? '', 'attr') ?>" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small" for="hastaVenta">Hasta</label><input id="hastaVenta" type="date" name="hasta" value="<?= esc($filtros['hasta'] ?? '', 'attr') ?>" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small" for="estadoVenta">Venta</label><select id="estadoVenta" name="estado" class="form-select"><option value="">Todas</option><?php foreach (['ACTIVA' => 'Activas', 'ANULADA' => 'Anuladas'] as $valor => $texto): ?><option value="<?= $valor ?>" <?= ($filtros['estado'] ?? '') === $valor ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="form-label small" for="vendedorVenta">Vendedor</label><select id="vendedorVenta" name="vendedor" class="form-select"><option value="">Todos</option><?php foreach ($vendedores as $vendedor): ?><option value="<?= (int) $vendedor['idusuario'] ?>" <?= (string) ($filtros['vendedor'] ?? '') === (string) $vendedor['idusuario'] ? 'selected' : '' ?>><?= esc($vendedor['nombreusuario']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 d-flex gap-2 mt-3"><button class="btn ventas-btn-morado" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Buscar ventas</button><a class="btn ventas-btn-secundario" href="<?= base_url('admin/ventas') ?>">Limpiar filtros</a></div>
        </div>
        </div>
    </form>
    <div class="card border shadow-sm rounded-3" style="border-color: #e5e7eb !important;">
        <div class="card-header ventas-section-header">
            <span class="ventas-resumen-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span>
            <div><h2>Historial de ventas</h2><p>Información comercial, entregas y comprobantes.</p></div>
            <span class="ventas-etiqueta ms-auto"><?= $cantidadVentas ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                        <tr>
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">Comprobante</th>
                            <th class="py-3">Cliente</th>
                            <th class="py-3">Local / Sucursal</th>
                            <th class="py-3 text-center" style="width: 200px;">Fecha</th>
                            <th class="py-3 text-end" style="width: 150px;">Importe</th>
                            <th class="pe-4 py-3 text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (!empty($ventas) && is_array($ventas)): ?>
                            <?php foreach ($ventas as $key => $venta): ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold"><?= $key + 1 ?></td>
                                    <td>
                                        <strong class="ventas-comprobante-numero"><?= esc(trim(($venta['serie'] ?? '') . '-' . $venta['numero'], '-')) ?></strong>
                                        <span class="ventas-texto-secundario"><?= esc(str_replace('_', ' ', $venta['tipodocumento'])) ?></span>
                                        <?php if (!empty($venta['fechaanulacion'])): ?><span class="ventas-estado ventas-estado-anulado mt-1">Anulada</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark d-block text-truncate" style="max-width: 250px;" title="<?= esc($venta['cliente'] ?? '') ?>">
                                            <?= esc($venta['cliente'] ?? 'Cliente General') ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary">
                                        <?= esc($venta['local']) ?>
                                        <small class="d-block text-muted">Vendedor: <?= esc($venta['vendedor'] ?? '') ?></small>
                                        <span class="badge bg-light text-dark border">Entrega: <?= esc($venta['estadoentrega'] ?? 'Sin entrega') ?></span>
                                    </td>
                                    <td class="text-center text-secondary small font-monospace">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-calendar3 text-muted"></i>
                                            <span><?= esc($venta['fecha']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        S/ <?= number_format($venta['total'], 2) ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <a href="<?= base_url('admin/ventas/detalles/' . $venta['idventa']) ?>"
                                            class="btn ventas-btn-icono"
                                            style="width: 32px; height: 32px;"
                                            data-bs-toggle="tooltip"
                                            title="Ver detalles" aria-label="Ver detalles de la venta <?= (int) $venta['idventa'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('admin/ventas/nota-pdf/' . $venta['idventa']) ?>"
                                            class="btn ventas-btn-icono ms-1 js-nota-pdf"
                                            style="width: 32px; height: 32px; color: #FF2B50;"
                                            data-bs-toggle="modal" data-bs-target="#modalNotaVentaPdf"
                                            title="Ver nota de venta PDF" aria-label="Ver PDF de la venta <?= (int) $venta['idventa'] ?>">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="ventas-estado-vacio">
                                    <div class="ventas-vacio-icono"><i class="bi bi-receipt" aria-hidden="true"></i></div>
                                    <strong>No hay ventas en este listado</strong>
                                    <span>Registra una nueva venta o ajusta los filtros de búsqueda.</span>
                                    <a href="<?= base_url('admin/ventas/crear') ?>" class="btn ventas-btn-secundario mt-3"><i class="bi bi-plus-lg" aria-hidden="true"></i> Crear una venta</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


<?php if (!empty($puedeAutorizar) && !empty($solicitudesPendientes)): ?>
<div class="mt-4">
    <div class="card shadow-sm border">
        <div class="card-header bg-light py-3 px-4">
            <h2 class="ventas-titulo-seccion"><i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Solicitudes de anulación pendientes (<?= count($solicitudesPendientes) ?>)</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-4">Venta</th><th>Fecha</th><th>Motivo</th><th class="pe-4 text-end">Revisar</th></tr></thead>
                <tbody>
                <?php foreach ($solicitudesPendientes as $sol): ?>
                    <tr>
                        <td class="ps-4"><?= esc(trim(($sol['serie'] ?? '') . '-' . ($sol['numero'] ?? ''))) ?></td>
                        <td><?= esc($sol['fecha_solicitud']) ?></td>
                        <td><?= esc($sol['motivo']) ?></td>
                        <td class="pe-4 text-end"><a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/ventas/detalles/' . $sol['idventa']) ?>">Evaluar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
</div>


<div class="modal fade" id="modalNotaVentaPdf" tabindex="-1" aria-labelledby="tituloModalNotaVentaPdf" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="tituloModalNotaVentaPdf"><i class="bi bi-file-earmark-pdf me-2" style="color: #FF2B50;"></i>Nota de venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0" style="height: 72vh; min-height: 420px;">
                <iframe id="iframeNotaVentaPdf" title="Vista previa de nota de venta" style="width: 100%; height: 100%; border: 0;" loading="lazy"></iframe>
            </div>
            <div class="modal-footer">
                <a id="descargarNotaVentaPdf" href="#" class="btn btn-outline-secondary" download="nota_venta.pdf"><i class="bi bi-download me-1"></i> Descargar PDF</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('js/ventas/nota_venta.js') ?>"></script>

<?= $this->endSection() ?>
