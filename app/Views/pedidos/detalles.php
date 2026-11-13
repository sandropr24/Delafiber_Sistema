<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/pedidos/index.css') ?>?v=<?= filemtime(FCPATH . 'css/pedidos/index.css') ?>"><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php if (session()->getFlashdata('pedidoGuardado')): ?><script>
        try {
            sessionStorage.removeItem('pedido-borrador-<?= (int) session('idusuario') ?>');
        } catch (e) {
            console.warn(e);
        }
    </script><?php endif; ?>
<div class="container-fluid px-3 px-lg-4 py-4 df-pedidos">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <p class="pedido-eyebrow">Comercial / Pedido</p>
            <h1 class="h3 fw-bold"><?= esc($pedido['codigo']) ?></h1><span class="pedido-estado estado-<?= esc($pedido['estado_codigo'], 'attr') ?>"><?= esc($pedido['estado']) ?></span>
            <?php if (!empty($pedido['idcotizacion'])): ?><a class="small d-block mt-2" href="<?= base_url('admin/cotizaciones/detalles/' . $pedido['idcotizacion']) ?>"><?= esc($pedido['cotizacion_numero']) ?></a><?php endif; ?>
        </div><a href="<?= base_url('admin/pedidos') ?>" class="btn btn-light border">Volver al listado</a>
    </div>
    <div class="pedido-grid">
        <div class="pedido-principal">
            <section class="card pedido-card">
                <div class="card-header">Datos del pedido</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Cliente</dt>
                        <dd class="col-sm-8"><?= esc($pedido['cliente']) ?> · <?= esc($pedido['numerodoc']) ?></dd>
                        <dt class="col-sm-4">Local</dt>
                        <dd class="col-sm-8"><?= esc($pedido['nombrelocal']) ?></dd>
                        <dt class="col-sm-4">Responsable</dt>
                        <dd class="col-sm-8"><?= esc($pedido['nombreusuario']) ?></dd>
                        <dt class="col-sm-4">Fecha</dt>
                        <dd class="col-sm-8"><?= esc(date('d/m/Y H:i', strtotime($pedido['fecha']))) ?></dd>
                        <dt class="col-sm-4">Observaciones</dt>
                        <dd class="col-sm-8"><?= nl2br(esc($pedido['observaciones'] ?: 'Sin observaciones')) ?></dd>
                    </dl>
                </div>
            </section>
            <section class="card pedido-card">
                <div class="card-header">Artículos acordados</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($detalles as $d): ?><tr>
                                    <td><?= esc($d['nombre']) ?></td>
                                    <td class="text-end"><?= number_format((float) $d['cantidad'], 2) ?></td>
                                    <td class="text-end">S/ <?= number_format((float) $d['preciounitario'], 2) ?></td>
                                    <td class="text-end">S/ <?= number_format(round((float) $d['cantidad'] * (float) $d['preciounitario'], 2), 2) ?></td>
                                </tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </section>
            <section class="card pedido-card">
                <div class="card-header">Historial</div>
                <div class="card-body">
                    <?php foreach ($historial as $h): ?>
                        <div class="pedido-evento">
                            <strong><?= $h['evento'] === 'AJUSTES_VENTA' ? 'Ajustes al convertir a venta' : esc(ucfirst(mb_strtolower($h['evento']))) ?></strong>
                            <small class="d-block text-muted"><?= esc($h['nombreusuario']) ?> · <?= esc(date('d/m/Y H:i', strtotime($h['fecha']))) ?></small>
                            <span class="small"><?= esc($h['motivo'] ?: str_replace('_', ' ', $h['estado_nuevo'])) ?></span>
                            <?php $cambios = json_decode($h['cambios'] ?? 'null', true); ?>
                            <?php if (is_array($cambios) && $cambios): ?>
                                <details class="mt-2"><summary class="small">Ver cambios (<?= count($cambios) ?>)</summary>
                                    <div class="table-responsive mt-2"><table class="table table-sm"><thead><tr><th>Dato</th><th>Pedido original</th><th>Venta registrada</th></tr></thead><tbody>
                                        <?php foreach ($cambios as $cambio): ?><tr><td><?= esc($cambio['campo']) ?></td><td><?= esc($cambio['antes']) ?></td><td><?= esc($cambio['despues']) ?></td></tr><?php endforeach; ?>
                                    </tbody></table></div>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <aside class="pedido-principal">
            <section class="card pedido-card pedido-resumen">
                <div class="card-header">Resumen del pedido</div>
                <div class="card-body">
                    <div class="pedido-importe"><span>Subtotal</span><strong>S/ <?= number_format((float) $pedido['subtotal'], 2) ?></strong></div>
                    <div class="pedido-importe"><span>Descuento</span><strong>S/ <?= number_format((float) $pedido['descuento'], 2) ?></strong></div><?php if ($pedido['motivodescuento']): ?><p class="small"><?= esc($pedido['motivodescuento']) ?></p><?php endif; ?>
                    <?php if (isset($pedido['baseimponible'])): ?><div class="pedido-importe"><span>Base imponible</span><strong>S/ <?= number_format((float) $pedido['baseimponible'], 2) ?></strong></div><div class="pedido-importe"><span>IGV <?= number_format((float) $pedido['porcentajeigv'], 2) ?> % incluido</span><strong>S/ <?= number_format((float) $pedido['montoigv'], 2) ?></strong></div><?php endif; ?>
                    <div class="pedido-importe pedido-total"><span>Total</span><strong>S/ <?= number_format((float) $pedido['total'], 2) ?></strong></div>
                    <?php if ($pedido['estado_codigo'] === 'PENDIENTE'): ?><a class="btn btn-outline-light w-100 mb-2" href="<?= base_url('admin/pedidos/editar/' . $pedido['idpedido']) ?>">Editar pedido</a>
                        <form method="post" action="<?= base_url('admin/pedidos/cambiar-estado/' . $pedido['idpedido']) ?>" class="pedido-confirmar" data-confirmacion="Se conservará el pedido confirmado. Podrás ajustar los datos al convertirlo a venta y los cambios quedarán registrados. Todavía no se reservará stock."><?= csrf_field() ?><input type="hidden" name="estado" value="CONFIRMADO"><button type="submit" class="btn pedido-primary w-100">Confirmar pedido</button></form><?php endif; ?>
                    <?php if ($pedido['estado_codigo'] === 'CONFIRMADO'): ?><a id="convertirPedido" class="btn pedido-primary w-100" href="<?= base_url('admin/ventas/crear') . '?pedido=' . (int) $pedido['idpedido'] . '&amp;desde_pedido=1' ?>">Convertir a venta</a>
                        <p class="small mt-2 mb-0">Selecciona las series, completa los datos de venta y registra el pago. Se comprobará el stock disponible.</p><?php endif; ?>
                    <?php if ($pedido['idventa']): ?><a class="btn btn-outline-light w-100" href="<?= base_url('admin/ventas/detalles/' . $pedido['idventa']) ?>">Ver venta vinculada</a><?php if ($pedido['venta_anulada']): ?><p class="small mt-2 mb-0">La venta fue anulada. Este pedido permanece cerrado; para una nueva operación registra otro pedido.</p><?php endif; ?><?php endif; ?>
                            <?php if (in_array($pedido['estado_codigo'], ['PENDIENTE', 'CONFIRMADO'], true)): ?><p class="small mt-3 mb-0">Este pedido no representa un cobro ni una reserva de mercadería.</p><?php endif; ?>
                </div>
            </section>
            <?php if (in_array($pedido['estado_codigo'], ['PENDIENTE', 'CONFIRMADO'], true)): ?><section class="card pedido-card">
                    <div class="card-body">
                        <form method="post" action="<?= base_url('admin/pedidos/cambiar-estado/' . $pedido['idpedido']) ?>" class="pedido-confirmar" data-confirmacion="¿Deseas cancelar este pedido? Esta acción cerrará el pedido."><?= csrf_field() ?><input type="hidden" name="estado" value="CANCELADO"><label class="form-label" for="motivoCancelacionPedido">Motivo de cancelación</label><textarea id="motivoCancelacionPedido" class="form-control mb-2" name="motivo" rows="2" required minlength="5" maxlength="300"></textarea><button type="submit" class="btn btn-outline-danger w-100">Cancelar pedido</button></form>
                    </div>
                </section><?php endif; ?>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script src="<?= base_url('js/pedidos/detalles.js') ?>?v=<?= filemtime(FCPATH . 'js/pedidos/detalles.js') ?>"></script><?= $this->endSection() ?>
