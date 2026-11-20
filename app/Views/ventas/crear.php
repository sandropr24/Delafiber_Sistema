<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/crear.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/crear.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/ventas/interfaz.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/interfaz.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$pedidoId = (int) ($pedidoSeleccionado['idpedido'] ?? 0);
$cotizacionId = (int) ($cotizacionSeleccionada['idcotizacion'] ?? 0);
$oldVenta = session()->get('_ci_old_input')['post'] ?? [];
if (!empty($iniciarPedido) || (int) ($oldVenta['idpedido'] ?? 0) !== $pedidoId || (int) ($oldVenta['idcotizacion'] ?? 0) !== $cotizacionId || in_array($oldVenta['origen_formulario'] ?? '', ['pedido', 'cotizacion'], true)) $oldVenta = [];
$entregaFormulario = ['tipoentrega' => 'RECOJO_TIENDA', 'direccion_entrega' => '', 'telefono_entrega' => '', 'referencia_entrega' => ''];
foreach ([$datosPedido ?? [], $oldVenta] as $origenEntrega) {
    foreach (array_keys($entregaFormulario) as $campo) {
        if (array_key_exists($campo, $origenEntrega) && (is_scalar($origenEntrega[$campo]) || $origenEntrega[$campo] === null)) {
            $entregaFormulario[$campo] = (string) ($origenEntrega[$campo] ?? '');
        }
    }
}
?>


<div class="container-fluid df-ventas df-venta">
    <nav class="ventas-breadcrumb" aria-label="Ruta de navegación">
        <a href="<?= base_url('admin/ventas') ?>">Ventas</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Nueva venta</span>
    </nav>
    <header class="ventas-page-heading">
        <div>
            <h1>Nueva venta</h1>
            <p>Selecciona al cliente, agrega sus artículos y registra el pago.</p>
            <?php if (!empty($pedidoSeleccionado)): ?><span class="ventas-etiqueta mt-2">Pedido <?= esc($pedidoSeleccionado['codigo']) ?></span><?php endif; ?>
            <?php if (!empty($cotizacionSeleccionada)): ?><span class="ventas-etiqueta mt-2">Cotización <?= esc($cotizacionSeleccionada['numero']) ?></span><?php endif; ?>
        </div>
        <div class="ventas-heading-acciones">
            <?php if (!empty($pedidoSeleccionado)): ?>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') . '?pedido=' . (int) $pedidoSeleccionado['idpedido'] . '&amp;desde_pedido=1' ?>">Recargar pedido</a>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') ?>">Venta directa</a>
            <?php endif; ?>
            <?php if (empty($pedidoSeleccionado) && !empty($cotizacionSeleccionada)): ?>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') . '?cotizacion=' . (int) $cotizacionSeleccionada['idcotizacion'] . '&amp;desde_cotizacion=1' ?>">Recargar cotización</a>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') ?>">Venta directa</a>
            <?php endif; ?>
            <button type="button" id="descartarBorrador" class="btn ventas-btn-secundario"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Limpiar venta</button>
            <a href="<?= base_url('admin/ventas') ?>" class="btn ventas-btn-secundario">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al historial
            </a>
        </div>
    </header>

    <form action="<?= base_url('admin/ventas/guardar') ?>" method="POST" enctype="multipart/form-data" autocomplete="off" id="formVenta">
        <?= csrf_field() ?>
        <input type="hidden" name="request_token" value="<?= esc($requestToken, 'attr') ?>">
        <?php if (!empty($cotizacionSeleccionada)): ?><input type="hidden" name="idcotizacion" value="<?= (int) $cotizacionSeleccionada['idcotizacion'] ?>"><?php endif; ?>
        <?php if (!empty($pedidoSeleccionado)): ?>
            <input type="hidden" name="idpedido" value="<?= (int) $pedidoSeleccionado['idpedido'] ?>">
            <?php if (!empty($avisosPedido)): ?><div class="venta-mensaje mb-3" role="alert"><?php foreach ($avisosPedido as $aviso): ?><p class="mb-0"><?= esc($aviso) ?></p><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>
        <div id="mensajeVenta" class="venta-mensaje d-none" role="alert" tabindex="-1"></div>
        <div id="avisoBorrador" class="venta-aviso-borrador" role="status" hidden>
            <div><strong>Artículos pendientes</strong><span id="detalleBorrador">Revisa el carrito.</span></div>
            <button type="button" id="limpiarBorrador" class="btn btn-sm btn-light border">Descartar borrador / Limpiar</button>
            <button type="button" id="cerrarAvisoBorrador" class="btn btn-sm" aria-label="Cerrar aviso de borrador">&times;</button>
        </div>
        <div class="df-venta-grid">
            <div class="venta-principal">
<?= $this->include('ventas/partials/datos_venta') ?>

<?= $this->include('ventas/partials/catalogo_venta') ?>
                <?= view('pedidos/_solicitud_web', ['datosWeb' => $pedidoSeleccionado['datos_web'] ?? null, 'observacionesPedido' => $pedidoSeleccionado['observaciones'] ?? null]) ?>
                <div class="card border shadow-sm rounded-3 mb-3 venta-entrega">
                    <div class="card-header ventas-section-header">
                        <span class="ventas-paso" aria-hidden="true">03</span>
                        <div>
                            <h2>Entrega de la venta</h2>
                            <p>Define cómo recibirá el cliente sus artículos.</p>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <label for="tipoentrega" class="form-label">Modalidad de entrega</label>
                        <select class="form-select mb-3" name="tipoentrega" id="tipoentrega" required>
                            <option value="" <?= $entregaFormulario['tipoentrega'] === '' ? 'selected' : '' ?>>Seleccione la modalidad</option>
                            <option value="RECOJO_TIENDA" <?= $entregaFormulario['tipoentrega'] === 'RECOJO_TIENDA' ? 'selected' : '' ?>>Recojo en tienda</option>
                            <option value="DOMICILIO" <?= $entregaFormulario['tipoentrega'] === 'DOMICILIO' ? 'selected' : '' ?>>Entrega a domicilio (sin costo)</option>
                        </select>
                        <p class="small text-muted">Los artículos quedan reservados al registrar. La salida de stock se confirma en Entregas. Los servicios se programan en Servicios técnicos.</p>
                        <div id="camposEntrega" class="d-none">
                            <div class="mb-2">
                                <label for="direccion_entrega" class="form-label">Dirección</label>
                                <input class="form-control" name="direccion_entrega" id="direccion_entrega" maxlength="150" value="<?= esc($entregaFormulario['direccion_entrega'], 'attr') ?>">
                                <div class="form-text">Máximo 150 caracteres. Si la solicitud web es más extensa, revise la dirección sin perder los datos originales mostrados arriba.</div>
                            </div>
                            <div class="mb-2">
                                <label for="referencia_entrega" class="form-label">Referencia</label>
                                <input class="form-control" name="referencia_entrega" id="referencia_entrega" maxlength="150" value="<?= esc($entregaFormulario['referencia_entrega'], 'attr') ?>">
                            </div>
                            <div class="mb-2">
                                <label for="telefono_entrega" class="form-label">Teléfono</label>
                                <input class="form-control" name="telefono_entrega" id="telefono_entrega" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" title="Revise el teléfono de entrega: debe contener exactamente 9 dígitos." value="<?= esc($entregaFormulario['telefono_entrega'], 'attr') ?>">
                            </div>
                            <div>
                                <label for="idtecnico_entrega" class="form-label">Técnico asignado (opcional)</label>
                                <select class="form-select" name="idtecnico_entrega" id="idtecnico_entrega">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($tecnicosEntrega as $tecnico): ?>
                                        <option value="<?= esc($tecnico['idtecnico']) ?>"><?= esc(trim($tecnico['nombres'] . ' ' . $tecnico['apellidos'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
<?= $this->include('ventas/partials/metodos_pago_venta') ?>
            </div>
<?= $this->include('ventas/partials/resumen_venta') ?>
        </div>
    </form>
</div>

<?= $this->include('ventas/partials/modal_cliente_express') ?>

<div id="ventaConfig" data-cuentas="<?= esc(json_encode($cuentasDestino ?? []), 'attr') ?>" data-precios-editables="1" data-user="<?= (int) session()->get('idusuario') ?>" data-pedido="<?= $pedidoId ?>" data-cotizacion="<?= $cotizacionId ?>" data-iniciar-pedido="<?= !empty($iniciarPedido) ? '1' : '0' ?>" data-pedido-datos="<?= esc(json_encode($datosPedido ?? []), 'attr') ?>" data-old="<?= esc(json_encode($oldVenta), 'attr') ?>" data-stock='<?= esc($stockPorLocal ?? "{}", 'attr') ?>' data-series='<?= esc($seriesDisponibles ?? "{}", 'attr') ?>' data-combos='<?= esc(json_encode($comboComponentes ?? []), 'attr') ?>' data-url-detalle="<?= esc(base_url('admin/ventas/articulo'), 'attr') ?>"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (() => {
        const form = document.getElementById('formClienteExpress');
        if (!form) return;
        const tipo = form.querySelector('[name="tipodoc"]');
        const documento = form.querySelector('[name="documento"]');

        function actualizarDocumento() {
            const longitud = tipo.value === 'RUC' ? 11 : 8;
            documento.setAttribute('maxlength', String(longitud));
            documento.setAttribute('minlength', String(longitud));
            documento.setAttribute('pattern', `[0-9]{${longitud}}`);
            documento.title = `Ingrese exactamente ${longitud} dígitos para ${tipo.value}.`;
        }
        tipo.addEventListener('change', actualizarDocumento);
        form.addEventListener('reset', () => setTimeout(actualizarDocumento, 0));
        actualizarDocumento();
    })();
</script>
<script type="module" src="<?= base_url('js/ventas/crear.js') ?>?v=<?= filemtime(FCPATH . 'js/ventas/crear.js') ?>"></script>
<?= $this->endSection() ?>