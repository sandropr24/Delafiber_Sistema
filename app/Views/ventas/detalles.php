<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/interfaz.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/interfaz.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$idVenta = (int) ($venta['idventa'] ?? 0);
$moneda = static fn($valor): string => 'S/ ' . number_format((float) $valor, 2);
$fecha = static function ($valor): string {
    if (empty($valor)) return 'No registrada';
    $timestamp = strtotime((string) $valor);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : (string) $valor;
};
$valor = static function ($fila, array $campos, $predeterminado = null) {
    foreach ($campos as $campo) {
        if (isset($fila[$campo]) && $fila[$campo] !== '') return $fila[$campo];
    }
    return $predeterminado;
};
$numero = trim((string) ($venta['numero'] ?? ''));
$serie = trim((string) ($venta['serie'] ?? ''));
$comprobante = trim(($serie !== '' ? $serie . '-' : '') . $numero);
$tipoDescuento = strtoupper((string) ($venta['tipodescuento'] ?? 'NINGUNO'));
$valorDescuento = (float) ($venta['valordescuento'] ?? 0);
$total = (float) ($venta['total'] ?? 0);
$totalPagado = 0.0;
$subtotalCalculado = 0.0;
$puedeCalcularSubtotal = !empty($detalles);
foreach ($detalles as $detalle) {
    $cantidad = (float) $valor($detalle, ['cantidad'], 0);
    $importe = $valor($detalle, ['subtotal', 'importe', 'total']);
    if ($importe === null) {
        $precio = $valor($detalle, ['precioventa', 'precio_unitario', 'precio']);
        $importe = $precio !== null ? $cantidad * (float) $precio : null;
    }
    if ($importe === null) $puedeCalcularSubtotal = false;
    else $subtotalCalculado += (float) $importe;
}
foreach ($pagos as $pago) $totalPagado += (float) $valor($pago, ['monto', 'importe', 'cantidad'], 0);
$subtotal = $valor($venta, ['subtotal']);
if ($subtotal === null && $puedeCalcularSubtotal) $subtotal = $subtotalCalculado;
$descuentoAplicado = $valor($venta, ['montodescuento', 'descuento', 'totaldescuento']);
if ($descuentoAplicado === null && $subtotal !== null) {
    $descuentoAplicado = max(0, (float) $subtotal - $total);
}
$estadoEntrega = (string) ($entregaComercial['estado'] ?? 'Sin registro');
$ventaAnulada = !empty($venta['fechaanulacion']) || !empty($anulacionComercial);
$entregaCancelada = $estadoEntrega === 'CANCELADO';
$puedeSolicitarAnulacion = !empty($puedeSolicitar) && !$ventaAnulada && $entregaCancelada && !$solicitudActiva;
$esAdministradorAnulacion = !empty($puedeAutorizar);

?>
<div class="container-fluid df-ventas ventas-detalle">
    <nav class="ventas-breadcrumb" aria-label="Ruta de navegación">
        <a href="<?= base_url('admin/ventas') ?>">Ventas</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Detalle de venta</span>
    </nav>
    <header class="ventas-page-heading">
        <div>
            <h1>Venta <?= esc($comprobante !== '' ? $comprobante : '#' . $idVenta) ?></h1>
            <p>Consulta los artículos, el pago y el seguimiento de esta operación.</p>
        </div>
        <div class="ventas-heading-acciones">
            <a href="<?= base_url('admin/ventas/nota-pdf/' . $idVenta) ?>" class="btn ventas-btn-secundario" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Nota de venta</a>
            <a href="<?= base_url('admin/ventas') ?>" class="btn ventas-btn-secundario"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al historial</a>
        </div>
    </header>

    <div class="ventas-detalle-layout">
        <div class="ventas-detalle-principal">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-person-vcard" aria-hidden="true"></i></span><div><h2>Información de la venta</h2><p>Cliente, comprobante y local de atención.</p></div></div>
                <div class="card-body">
                    <dl class="row mb-0 gy-2 small">
                        <dt class="col-sm-4 text-muted">Cliente</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($venta['cliente'] ?: 'No identificado') ?></dd>
                        <dt class="col-sm-4 text-muted">Documento</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($venta['numerodoc'] ?: 'No registrado') ?></dd>
                        <dt class="col-sm-4 text-muted">Local / Sucursal</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($venta['local'] ?: 'No asignado') ?></dd>
                        <dt class="col-sm-4 text-muted">Comprobante</dt>
                        <dd class="col-sm-8 mb-0"><?= esc((string) ($venta['tipodocumento'] ?? 'No especificado')) ?><?= $comprobante !== '' ? ' · ' . esc($comprobante) : '' ?></dd>
                        <dt class="col-sm-4 text-muted">Fecha</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($fecha($venta['fecha'] ?? null)) ?></dd>
                        <dt class="col-sm-4 text-muted">Estado de venta</dt>
                        <dd class="col-sm-8 mb-0"><?= esc((string) ($venta['estado'] ?? ('ID ' . ($venta['idestado'] ?? 'No registrado')))) ?></dd>
                    </dl>
                </div>
            </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span><div><h2>Artículos de la venta</h2><p>Productos, servicios y combos incluidos.</p></div><span class="ventas-etiqueta ms-auto"><?= count($detalles) ?> artículos</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Artículo</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Precio unitario</th>
                            <th class="text-end pe-3">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($detalles)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay artículos registrados.</td>
                            </tr>
                            <?php else: foreach ($detalles as $detalle): ?>
                                <?php
                                $cantidad = (float) $valor($detalle, ['cantidad'], 0);
                                $precio = $valor($detalle, ['precioventa', 'precio_unitario', 'precio']);
                                $importe = $valor($detalle, ['subtotal', 'importe', 'total']);
                                if ($importe === null && $precio !== null) $importe = $cantidad * (float) $precio;
                                ?>
                                <tr>
                                    <td class="ps-3 fw-medium"><?= esc($detalle['producto'] ?? ('Producto #' . ($detalle['idproducto'] ?? ''))) ?></td>
                                    <td class="text-end"><?= esc(number_format($cantidad, 2)) ?></td>
                                    <td class="text-end"><?= $precio === null ? '—' : esc($moneda($precio)) ?></td>
                                    <td class="text-end pe-3"><?= $importe === null ? '—' : esc($moneda($importe)) ?></td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-wallet2" aria-hidden="true"></i></span><h2>Métodos de pago</h2></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Método</th>
                                    <th class="text-end pe-3">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagos)): ?><tr>
                                        <td colspan="2" class="text-center text-muted py-3">Sin pagos registrados.</td>
                                    </tr>
                                    <?php else: foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td class="ps-3"><?= esc($pago['tipopago'] ?? 'No especificado') ?>
                                                <?php if ((float) ($pago['vuelto'] ?? 0) > 0): ?>
                                                    <small class="d-block text-muted">Recibido: <?= esc($moneda($pago['montorecibido'])) ?> · Vuelto: <?= esc($moneda($pago['vuelto'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3"><?= esc($moneda($valor($pago, ['monto', 'importe', 'cantidad'], 0))) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($pagos): ?><div class="card-footer bg-white d-flex justify-content-between"><span class="fw-semibold">Total registrado</span><span class="fw-semibold"><?= esc($moneda($totalPagado)) ?></span></div><?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-truck" aria-hidden="true"></i></span><h2>Entrega</h2></div>
                <div class="card-body small">
                    <?php if (!$entregaComercial): ?>
                        <p class="text-muted mb-0">No hay una entrega vinculada a esta venta.</p>
                    <?php else: ?>
                        <dl class="row gy-2 mb-0">
                            <dt class="col-5 text-muted">Estado</dt>
                            <dd class="col-7 mb-0"><?= esc(str_replace('_', ' ', $estadoEntrega)) ?></dd>
                            <dt class="col-5 text-muted">Tipo</dt>
                            <dd class="col-7 mb-0"><?= esc(str_replace('_', ' ', (string) ($entregaComercial['tipoentrega'] ?? 'No indicado'))) ?></dd>
                            <dt class="col-5 text-muted">En camino</dt>
                            <dd class="col-7 mb-0"><?= esc($fecha($entregaComercial['fechaencamino'] ?? null)) ?></dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-clock-history" aria-hidden="true"></i></span><div><h2>Seguimiento comercial</h2><p>Historial de solicitudes y anulaciones.</p></div></div>
        <div class="card-body">
            <?php if ($anulacionComercial): ?>
                <div class="alert alert-warning small">Esta venta tiene un registro de anulación comercial.</div>
            <?php endif; ?>
            <?php if ($solicitudActiva): ?>
                <div class="alert alert-info small">Existe una solicitud de anulación activa: <?= esc(str_replace('_', ' ', (string) ($solicitudActiva['estado'] ?? ''))) ?>.</div>
            <?php endif; ?>
            <?php if (empty($solicitudesAnulacion)): ?>
                <p class="text-muted small mb-0">No hay solicitudes de anulación registradas.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Solicitante</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudesAnulacion as $solicitud): ?>
                                <tr>
                                    <td><?= esc($fecha($valor($solicitud, ['fecha_solicitud', 'fecha']))) ?></td>
                                    <td><?= esc($solicitud['solicitante'] ?? 'No identificado') ?></td>
                                    <td><?= esc($solicitud['motivo'] ?? 'Sin motivo') ?></td>
                                    <td><?= esc(str_replace('_', ' ', (string) ($solicitud['estado'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if ($ventaAnulada): ?>
                <div class="alert alert-success small mt-3 mb-0">La anulación comercial ya fue ejecutada. No se permiten nuevas solicitudes.</div>
            <?php elseif (!$entregaCancelada): ?>
                <div class="alert alert-secondary small mt-3 mb-0">
                    Para solicitar la anulación, primero debe cancelarse la entrega desde el módulo Entregas.
                    Una entrega confirmada como ENTREGADO requiere un procedimiento de devolución, no esta anulación.
                </div>
            <?php endif; ?>

            <?php if ($puedeSolicitarAnulacion): ?>
                <div class="card border mt-3">
                    <div class="card-header bg-light fw-semibold">Solicitar anulación de la venta</div>
                    <div class="card-body">
                        <form action="<?= base_url('admin/ventas/solicitar-anulacion/' . $idVenta) ?>" method="post">
                            <?= csrf_field() ?>
                            <label for="motivoAnulacion" class="form-label">Motivo de la anulación</label>
                            <textarea id="motivoAnulacion" name="motivo" class="form-control mb-2" rows="3" minlength="15" maxlength="2000" required placeholder="Explica por qué se solicita anular la venta."></textarea>
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Registrar esta solicitud de anulación? La venta todavía no será anulada.');">
                                <i class="bi bi-file-earmark-x me-1"></i> Solicitar anulación
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdministradorAnulacion && !$ventaAnulada && !empty($solicitudesAnulacion)): ?>
                <?php foreach ($solicitudesAnulacion as $solicitud): ?>
                    <?php if (($solicitud['estado'] ?? '') === 'PENDIENTE'): ?>
                        <div class="card border mt-3">
                            <div class="card-header bg-light fw-semibold">Revisar solicitud N.º <?= (int) $solicitud['idsolicitud'] ?></div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">Motivo: <?= esc($solicitud['motivo'] ?? '') ?></p>
                                <form action="<?= base_url('admin/ventas/revisar-anulacion/' . (int) $solicitud['idsolicitud']) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <label class="form-label" for="observacionRevision<?= (int) $solicitud['idsolicitud'] ?>">Observación de la revisión</label>
                                    <textarea id="observacionRevision<?= (int) $solicitud['idsolicitud'] ?>" name="observacion" class="form-control mb-2" minlength="10" maxlength="2000" rows="2" required></textarea>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="submit" name="decision" value="AUTORIZADA" class="btn btn-outline-success" onclick="return confirm('¿Autorizar el trámite? Esto aún NO anula la venta.');">Autorizar</button>
                                        <button type="submit" name="decision" value="RECHAZADA" class="btn btn-outline-danger" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php elseif (($solicitud['estado'] ?? '') === 'AUTORIZADA' && $entregaCancelada): ?>
                        <div class="card border-danger mt-3">
                            <div class="card-header bg-light fw-semibold">Ejecutar anulación autorizada N.º <?= (int) $solicitud['idsolicitud'] ?></div>
                            <div class="card-body">
                                <div class="alert alert-warning small">
                                    Esta operación libera reservas, cambia el estado comercial de la venta y genera un registro de anulación.
                                    No realiza automáticamente ningún reembolso de dinero.
                                </div>
                                <form action="<?= base_url('admin/ventas/ejecutar-anulacion/' . (int) $solicitud['idsolicitud']) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="inventarioVerificado<?= (int) $solicitud['idsolicitud'] ?>" name="inventario_verificado" value="1" required>
                                        <label class="form-check-label" for="inventarioVerificado<?= (int) $solicitud['idsolicitud'] ?>">Verifiqué físicamente que los artículos no fueron entregados y que el inventario corresponde a la reserva.</label>
                                    </div>
                                    <label for="observacionInventario<?= (int) $solicitud['idsolicitud'] ?>" class="form-label">Observación del control de inventario</label>
                                    <textarea id="observacionInventario<?= (int) $solicitud['idsolicitud'] ?>" name="observacion_inventario" class="form-control mb-3" minlength="15" maxlength="1000" rows="3" required placeholder="Describe el resultado de la verificación física."></textarea>
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('ATENCIÓN: ¿Ejecutar definitivamente la anulación de esta venta? Esta acción no puede deshacerse desde esta pantalla.');">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Ejecutar anulación
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
        </div>
        <aside class="ventas-detalle-lateral">
            <section class="card ventas-resumen-detalle" aria-labelledby="tituloResumenVenta">
                <div class="card-header ventas-section-header"><span class="ventas-resumen-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span><div><h2 id="tituloResumenVenta">Resumen de venta</h2><p>Importes de la operación</p></div></div>
                <div class="card-body">
                    <span class="ventas-estado <?= $ventaAnulada ? 'ventas-estado-anulado' : 'ventas-estado-activo' ?>"><?= $ventaAnulada ? 'Venta anulada' : 'Venta activa' ?></span>
                    <dl class="ventas-resumen-datos mt-3">
                        <div><dt>Fecha de registro</dt><dd><?= esc($fecha($venta['fecha'] ?? null)) ?></dd></div>
                        <div><dt>Estado de entrega</dt><dd><?= esc(str_replace('_', ' ', $estadoEntrega)) ?></dd></div>
                    </dl>
                    <div class="ventas-separador"></div>
                    <div class="ventas-importe-fila"><span>Subtotal</span><strong><?= $subtotal !== null ? esc($moneda($subtotal)) : 'No disponible' ?></strong></div>
                    <div class="ventas-importe-fila"><span>Descuento</span><strong><?= esc($moneda($descuentoAplicado ?? 0)) ?></strong></div>
                    <?php if ($tipoDescuento !== 'NINGUNO'): ?>
                        <p class="ventas-texto-secundario"><?= esc($tipoDescuento) ?> · <?= $tipoDescuento === 'PORCENTAJE' ? esc(number_format($valorDescuento, 2)) . '%' : esc($moneda($valorDescuento)) ?></p>
                        <?php if (!empty($venta['motivodescuento'])): ?><p class="ventas-texto-secundario">Motivo: <?= esc($venta['motivodescuento']) ?></p><?php endif; ?>
                    <?php endif; ?>
                    <div class="ventas-total-panel"><span>Total de la venta</span><strong><?= esc($moneda($total)) ?></strong><small>Importe final en soles (PEN)</small></div>
                    <div class="ventas-importe-fila"><span>Pago registrado</span><strong><?= esc($moneda($totalPagado)) ?></strong></div>
                </div>
            </section>
            <div class="ventas-nota-lateral"><i class="bi bi-info-circle" aria-hidden="true"></i><div><strong>Documento interno</strong><p>Consulta la nota de venta para ver el detalle del comprobante.</p></div></div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
