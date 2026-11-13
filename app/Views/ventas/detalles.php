<?= $this->extend('layouts/main') ?>
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
<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Ventas / Detalle</div>
            <h1 class="h4 fw-bold mb-1">Venta N.º <?= $idVenta ?></h1>
            <div class="text-muted small"><?= esc($comprobante !== '' ? $comprobante : 'Sin número de comprobante') ?> · <?= esc($fecha($venta['fecha'] ?? null)) ?></div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($entregaComercial): ?>
                <span class="badge bg-light text-dark border">Entrega: <?= esc(str_replace('_', ' ', $estadoEntrega)) ?></span>
            <?php endif; ?>
            <a href="<?= base_url('admin/ventas/nota-pdf/' . $idVenta) ?>" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i> Nota de venta</a>
            <a href="<?= base_url('admin/ventas') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold py-3">Información de la venta</div>
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
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold py-3">Resumen de importes</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between small mb-2"><span class="text-muted">Subtotal</span><span><?= $subtotal !== null ? esc($moneda($subtotal)) : 'No disponible' ?></span></div>
                    <div class="d-flex justify-content-between small mb-2"><span class="text-muted">Tipo de descuento</span><span><?= esc($tipoDescuento === 'NINGUNO' ? 'Sin descuento' : $tipoDescuento) ?></span></div>
                    <?php if ($tipoDescuento !== 'NINGUNO'): ?>
                        <div class="d-flex justify-content-between small mb-2"><span class="text-muted">Valor indicado</span><span><?= $tipoDescuento === 'PORCENTAJE' ? esc(number_format($valorDescuento, 2)) . '%' : esc($moneda($valorDescuento)) ?></span></div>
                        <?php if ($descuentoAplicado !== null): ?><div class="d-flex justify-content-between small mb-2"><span class="text-muted">Descuento aplicado</span><span class="text-danger">− <?= esc($moneda($descuentoAplicado)) ?></span></div><?php endif; ?>
                        <?php if (!empty($venta['motivodescuento'])): ?><div class="small text-muted mb-2">Motivo: <?= esc($venta['motivodescuento']) ?></div><?php endif; ?>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center"><strong>Total de venta</strong><strong class="fs-5"><?= esc($moneda($total)) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-semibold py-3">Productos, servicios y combos</div>
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
                <div class="card-header bg-white fw-semibold py-3">Métodos de pago</div>
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
                <div class="card-header bg-white fw-semibold py-3">Entrega</div>
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
        <div class="card-header bg-white fw-semibold py-3">Anulaciones y solicitudes</div>
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
<?= $this->endSection() ?>
