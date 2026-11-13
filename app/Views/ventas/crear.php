<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/crear.css') ?>?v=<?= filemtime(FCPATH . 'css/ventas/crear.css') ?>">

<div class="container-fluid px-3 px-lg-4 py-3 df-venta">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 venta-cabecera">
        <div>
            <span class="venta-eyebrow">DELAFIBER / PUNTO DE VENTA</span><h1 class="h3 fw-bold text-dark mb-1">Nueva venta</h1>
            <?php if (!empty($pedidoSeleccionado)): ?><span class="small text-muted">Pedido <?= esc($pedidoSeleccionado['codigo']) ?></span><?php endif; ?>
            <?php if (!empty($cotizacionSeleccionada)): ?><span class="small text-muted">Cotización <?= esc($cotizacionSeleccionada['numero']) ?></span><?php endif; ?>
        </div>
        <div>
            <?php if (!empty($pedidoSeleccionado)): ?>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') . '?pedido=' . (int) $pedidoSeleccionado['idpedido'] . '&amp;desde_pedido=1' ?>">Recargar pedido</a>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') ?>">Venta directa</a>
            <?php endif; ?>
            <?php if (empty($pedidoSeleccionado) && !empty($cotizacionSeleccionada)): ?>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') . '?cotizacion=' . (int) $cotizacionSeleccionada['idcotizacion'] . '&amp;desde_cotizacion=1' ?>">Recargar cotización</a>
                <a class="btn btn-light border" href="<?= base_url('admin/ventas/crear') ?>">Venta directa</a>
            <?php endif; ?>
            <button type="button" id="descartarBorrador" class="btn btn-light border me-2">Nueva venta vacía</button>
            <a href="<?= base_url('admin/ventas') ?>" class="btn btn-light border text-secondary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-arrow-left"></i>
                <span class="fw-medium">Volver al listado</span>
            </a>
        </div>
    </div>

    <form action="<?= base_url('admin/ventas/guardar') ?>" method="POST" autocomplete="off" id="formVenta">
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
        <div class="row g-3 align-items-start df-venta-grid">
            <div class="col-12 col-xl-7 venta-principal">
                <div class="card border shadow-sm rounded-3 mb-3 venta-cliente">
                    <div class="card-header bg-light py-3 px-4 border-bottom">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text text-primary"></i>
                            1. Cliente y datos de venta
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Local / Sucursal <span class="text-danger">*</span></label>
                                <select name="idlocal" id="selectLocal" class="form-select" required>
                                    <option value="">Seleccione local...</option>
                                    <?php foreach ($locales ?? [] as $loc): ?>
                                        <option value="<?= $loc['idlocal'] ?>" <?= (int) $loc['idlocal'] === (int) $localInicial ? 'selected' : '' ?>><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-7">
                                <label for="buscarClienteVenta" class="form-label small fw-bold text-secondary text-uppercase">Cliente <span class="text-danger">*</span> <kbd class="venta-atajo">F4</kbd></label>
                                <div class="input-group venta-cliente-busqueda">
                                    <div class="venta-autocomplete">
                                        <input type="search" id="buscarClienteVenta" class="form-control" placeholder="Nombre o documento del cliente" aria-label="Buscar y seleccionar cliente" role="combobox" aria-autocomplete="list" aria-controls="resultadosClientes" aria-expanded="false" autocomplete="off" required>
                                        <div id="resultadosClientes" class="venta-resultados" role="listbox" aria-label="Clientes" hidden></div>
                                    </div>
                                    <select name="idcliente" id="selectCliente" hidden aria-hidden="true" tabindex="-1">
                                        <option value="">Seleccione cliente...</option>
                                        <?php foreach ($clientes ?? [] as $cli): ?>
                                            <option value="<?= $cli['idpersona'] ?>" <?= (int) $cli['idpersona'] === (int) $clienteInicial ? 'selected' : '' ?>>
                                                <?= trim(esc($cli['nombres'] ?? '') . ' ' . esc($cli['apellidos'] ?? '')) ?: esc($cli['razonsocial'] ?? 'Sin Nombre') ?>
                                                · <?= esc($cli['numerodoc'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalClienteExpress" title="Cliente rápido">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <button type="button" id="btnPublicoGeneral" data-cliente="<?= (int) $clienteInicial ?>" class="btn btn-sm btn-outline-secondary venta-publico-general" <?= empty($clienteInicial) ? 'disabled title="Configure un cliente Público General para habilitar este acceso"' : '' ?>><i class="bi bi-people me-1"></i>Público General</button>
                            </div>

                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Línea</label>
                                <select name="idlinea" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($lineas as $x): ?>
                                        <option value="<?= esc($x['idlinea']) ?>"><?= esc($x['nombre'] ?? $x['linea'] ?? $x['idlinea']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Canal</label>
                                <select name="idcanal" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($canales as $x): ?>
                                        <option value="<?= esc($x['idcanal']) ?>"><?= esc($x['nombre'] ?? $x['canal'] ?? $x['idcanal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado</label>
                                <select name="idestado" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($estadosVenta as $x): ?>
                                        <option value="<?= esc($x['idestado']) ?>" <?= (int) $x['idestado'] === (int) $estadoInicial ? 'selected' : '' ?>><?= esc($x['estado']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="venta-comprobante"><span>Nota de venta</span><span>Numeración automática al registrar</span><input type="hidden" name="tipodocumento" value="NOTA_VENTA"></div>
                    </div>
                </div>

                <div class="card border shadow-sm rounded-3 venta-catalogo">
                    <div class="card-header bg-light py-3 px-4 border-bottom">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam text-primary"></i>
                            2. Buscar y agregar artículos <kbd class="venta-atajo">F2</kbd>
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="buscarArticulo" class="form-label small fw-bold text-secondary">Buscar artículo</label>
                            <div class="input-group venta-articulo-busqueda">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <div class="venta-autocomplete">
                                    <input type="search" id="buscarArticulo" class="form-control" placeholder="Nombre o código de barras · Enter para agregar" role="combobox" aria-autocomplete="list" aria-controls="resultadosArticulos" aria-expanded="false" autocomplete="off">
                                    <div id="resultadosArticulos" class="venta-resultados" role="listbox" aria-label="Artículos" hidden></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap mt-2" id="filtrosArticulos">
                                <button type="button" class="btn btn-sm btn-primary" data-tipo="todos">Todos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="producto">Productos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="servicio">Servicios</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="combo">Combos</button>
                            </div>
                            <div id="disponibilidadArticulo" class="small text-primary mt-2" aria-live="polite">Seleccione un local para consultar existencias.</div>
                            <div class="form-text" id="resultadoBusqueda" aria-live="polite">Selecciona el tipo o busca por nombre.</div>
                        </div>
                        <details class="venta-manual"><summary>Selección manual de producto, servicio o combo</summary>
                        <div class="p-2 bg-light rounded-3 mb-2 border">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4 articulo-grupo" data-tipo="producto">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Producto</label>
                                    <select class="form-select" id="selectProducto">
                                        <option value="" selected disabled>Seleccionar producto...</option>
                                        <?php foreach ($productos ?? [] as $prod): ?>
                                            <option value="<?= $prod['idproducto'] ?>"
                                                data-nombre="<?= esc($prod['nombre'] ?? $prod['descripcion']) ?>"
                                                data-precio="<?= $prod['precioventa'] ?? 0 ?>"
                                                data-unidad="<?= esc($prod['unidad'] ?? 'UND', 'attr') ?>" data-codigo="<?= esc($prod['codigobarras'] ?? '', 'attr') ?>"
                                                data-controlaserie="<?= (int) ($prod['controlaserie'] ?? 0) ?>"
                                                data-controlastock="<?= (int) ($prod['controlastock'] ?? 0) ?>">
                                                <?= esc($prod['nombre'] ?? $prod['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 articulo-grupo" data-tipo="servicio">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Servicio</label>
                                    <select class="form-select" id="selectServicio">
                                        <option value="" selected>Seleccionar servicio...</option>
                                        <?php foreach ($servicios ?? [] as $serv): ?>
                                            <option value="<?= (int) $serv['idproducto'] ?>"
                                                data-nombre="<?= esc($serv['nombre'] ?? $serv['descripcion'], 'attr') ?>"
                                                data-precio="<?= esc($serv['precioventa'] ?? 0, 'attr') ?>"
                                                data-controlaserie="0" data-controlastock="0">
                                                <?= esc($serv['nombre'] ?? $serv['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 articulo-grupo" data-tipo="combo">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Combo</label>
                                    <select class="form-select" id="selectCombo">
                                        <option value="">Seleccionar combo...</option>
                                        <?php foreach ($combos ?? [] as $combo): ?>
                                            <option value="<?= (int) $combo['idproducto'] ?>"
                                                data-nombre="<?= esc($combo['nombre'], 'attr') ?>"
                                                data-precio="<?= esc($combo['precioventa'] ?? 0, 'attr') ?>"
                                                data-controlaserie="0" data-controlastock="0" data-combo="1">
                                                <?= esc($combo['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Cantidad</label>
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="1" step="1" value="1">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">P. Venta (S/)</label>
                                    <input type="number" class="form-control text-end" id="inputPrecio" readonly step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-12 col-md-6">
                                    <button type="button" class="btn btn-primary w-100 fw-bold" id="btnAgregarItem" title="Agregar producto">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar al carrito
                                    </button>
                                </div>
                            </div>
                        </div>
                        </details>
                    </div>
                </div>
                <div class="card df-carrito-card mb-3">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold"><i class="bi bi-cart3 text-primary me-2"></i>3. Carrito de venta</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive border rounded-2">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom">
                                    <tr>
                                        <th class="ps-4 py-3">PRODUCTO</th>
                                        <th class="text-center py-3">CANTIDAD</th>
                                        <th class="text-end py-3">PRECIO (S/)</th>
                                        <th class="text-end py-3">SUBTOTAL (S/)</th>
                                        <th class="pe-4 text-center py-3">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="carritoBody">
                                    <tr id="filaVacia">
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="bi bi-cart-x fs-5 text-secondary opacity-50 d-block mb-2"></i>
                                            <p class="mb-0 fw-medium">No se han agregado productos a la venta.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card border shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-light py-3 px-4">
                        <h6 class="m-0 fw-bold"><i class="bi bi-truck me-2"></i>4. Entrega</h6>
                    </div>
                    <div class="card-body p-3">
                        <label class="form-label">Modalidad de entrega</label>
                        <select class="form-select mb-3" name="tipoentrega" id="tipoentrega">
                            <option value="RECOJO_TIENDA">Recojo en tienda</option>
                            <option value="DOMICILIO">Entrega a domicilio (sin costo)</option>
                        </select>
                        <p class="small text-muted">Los artículos quedan reservados al registrar. La salida de stock se confirma en Entregas. Los servicios se programan en Servicios técnicos.</p>
                        <div id="camposEntrega" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Dirección</label>
                                <input class="form-control" name="direccion_entrega" id="direccion_entrega" maxlength="150">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Referencia</label>
                                <input class="form-control" name="referencia_entrega" maxlength="150">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Teléfono</label>
                                <input class="form-control" name="telefono_entrega" id="telefono_entrega" inputmode="numeric" pattern="[0-9]{9}" maxlength="9">
                            </div>
                            <div>
                                <label class="form-label">Técnico asignado (opcional)</label>
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
            </div>
            <div class="col-12 col-xl-5 venta-cobro">
                <div class="card border shadow-sm rounded-3 mb-3 venta-pago">
                    <div class="card-header bg-light py-3 px-4 border-bottom">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-primary"></i>
                            5. Pago
                        </h6>
                    </div>
                    <div class="card-body p-3">
<?php if (!empty($puedeAplicarDescuento)): ?>
<details class="venta-descuento"><summary>Descuento de la venta</summary><div class="venta-descuento-campos">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label for="tipoDescuento" class="form-label">Tipo</label>
                                    <select name="tipo_descuento" id="tipoDescuento" class="form-select">
                                        <option value="NINGUNO">Sin descuento</option>
                                        <option value="PORCENTAJE">Porcentaje (%)</option>
                                        <option value="MONTO">Monto fijo (S/)</option>
                                    </select>
                                </div>
                                <div class="col-md-7" id="campoValorDescuento" hidden>
                                    <label for="valorDescuento" class="form-label">Valor del descuento</label>
                                    <input type="number" name="valor_descuento" id="valorDescuento" class="form-control" min="0" step="0.01" value="0.00" disabled>
                                </div>
                                <div class="col-12" id="campoMotivoDescuento" hidden>
                                    <label for="motivoDescuento" class="form-label">Motivo</label>
                                    <input type="text" name="motivo_descuento" id="motivoDescuento" class="form-control" maxlength="300" minlength="5" placeholder="Ej. Compra por volumen" list="motivosVenta" disabled>
                                    <datalist id="motivosVenta">
                                        <option value="Compra por volumen">
                                        <option value="Promoción">
                                        <option value="Negociación comercial">
                                        <option value="Cliente frecuente">
                                        <option value="Cortesía comercial">
                                    </datalist>
                                </div>
                            </div>
                            <div class="text-danger small mt-2 d-none" id="errorDescuento" role="alert"></div>
</div></details>
<?php endif; ?>
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Método de Pago</label>
                                <select class="form-select form-select-sm" id="selectTipoPago">
                                    <option value="" selected disabled>Seleccionar método...</option>
                                    <?php foreach ($tipospago ?? [] as $tp): ?>
                                        <option value="<?= $tp['idtipopago'] ?>" data-nombre="<?= esc($tp['tipopago']) ?>">
                                            <?= esc($tp['tipopago']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-7 col-md-4">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Monto (S/)</label>
                                <input type="number" class="form-control form-control-sm text-end" id="inputMontoPago" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-5 col-md-3">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100 fw-bold shadow-sm" id="btnAgregarPago">
                                    <i class="bi bi-plus-lg"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6" id="campoReferenciaPago" hidden><label for="inputReferenciaPago" class="form-label">Referencia de operación</label><input id="inputReferenciaPago" class="form-control" maxlength="50" placeholder="Para pagos electrónicos"></div>
                            <div class="col-md-6" id="campoRecibidoPago" hidden><label for="inputRecibidoPago" class="form-label">Efectivo recibido (S/)</label><input id="inputRecibidoPago" type="number" step="0.01" min="0" class="form-control" placeholder="Igual al monto si se deja vacío"></div>
                            <div class="col-12">
                                <div class="venta-pagos-rapidos" aria-label="Pagos rápidos">
                                    <button id="btnCompletarPago" type="button" class="btn btn-sm btn-light border">Monto exacto</button>
                                    <?php foreach ([10, 20, 50, 100] as $billete): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-efectivo="<?= $billete ?>" title="Efectivo recibido: S/ <?= $billete ?>">S/ <?= $billete ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <p id="previewVuelto" hidden class="small text-success fw-semibold mt-2 mb-0" aria-live="polite">Seleccione efectivo para calcular el vuelto.</p>
                            </div>
                        </div>
                        <div class="table-responsive border rounded-2 mb-2">
                            <table class="table table-hover align-middle mb-0" id="tablaPagos">
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom">
                                    <tr>
                                        <th class="ps-3 py-2">MÉTODO</th>
                                        <th class="text-end py-2">MONTO</th>
                                        <th class="pe-3 text-end py-2">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="pagosBody">
                                    <tr id="filaSinPagos">
                                        <td colspan="3" class="text-center text-muted py-3 small">Sin pagos asignados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small px-1 mt-2">
                            <span class="text-secondary">Pagado: <span id="lblTotalPagado" class="text-success fw-bold">S/ 0.00</span></span>
                            <span class="text-secondary"><span id="lblFaltanteVuelto" class="text-danger fw-bold">Faltante: S/ 0.00</span></span>
                        </div>
                    </div>
                </div>
                <div class="card border shadow-sm rounded-3 resumen-venta">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold">6. Resumen de cobro <span id="contadorArticulos" class="badge text-bg-light float-end">0 artículos</span></h6>
                    </div>
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-secondary">Subtotal</span>
                            <span class="fw-semibold" id="lblSubtotal">S/ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                            <span class="text-secondary">Descuento</span>
                            <span class="fw-semibold text-danger" id="lblDescuento">- S/ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <span class="fw-bold text-secondary text-uppercase">Total a pagar</span>
                            <h3 class="fw-bold text-success mb-0 text-nowrap" id="lblTotal">S/ 0.00</h3>
                            <input type="hidden" name="total" id="inputTotal" value="0.00">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded venta-vuelto"><span class="fw-semibold">Vuelto a entregar</span><strong id="lblVuelto" class="text-success fs-5" aria-live="polite">S/ 0.00</strong></div>
                        <p id="estadoCobro" class="small text-muted" aria-live="polite">Agregue artículos y complete el pago.</p><button id="btnConfirmarVenta" type="submit" disabled aria-keyshortcuts="F9" class="btn btn-primary w-100 py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <span class="fw-semibold" id="textoConfirmarVenta">Registrar venta</span>
                            <kbd class="venta-atajo">F9</kbd>
                        </button>
                    </div>
                </div>



            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClienteExpress" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus text-primary"></i>
                    Registrar Cliente Rápido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formClienteExpress" data-url-express="<?= base_url('admin/clientes/guardar-express') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="tipoDocClienteExpress" class="form-label small fw-bold text-secondary text-uppercase">Tipo de documento</label>
                        <select id="tipoDocClienteExpress" name="tipodoc" class="form-select" required>
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">
                            Documento<span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="documento"
                            value="<?= old('documento') ?>"
                            class="form-control"
                            inputmode="numeric"
                            minlength="8"
                            maxlength="8"
                            pattern="[0-9]{8}"
                            title="Ingrese exactamente 8 dígitos para DNI."
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Nombre o Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" maxlength="100" pattern="[^0-9]+" title="El nombre no debe contener números." required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control" inputmode="numeric" pattern="[0-9]{1,9}" maxlength="9" title="Ingrese solo números, hasta 9 dígitos.">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-sm btn-light border text-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm" id="btnGuardarClienteExpress">
                        <i class="bi bi-save me-1"></i> Guardar y Seleccionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pedidoId = (int) ($pedidoSeleccionado['idpedido'] ?? 0);
$cotizacionId = (int) ($cotizacionSeleccionada['idcotizacion'] ?? 0);
$oldVenta = session()->get('_ci_old_input')['post'] ?? [];
if (!empty($iniciarPedido) || (int) ($oldVenta['idpedido'] ?? 0) !== $pedidoId || (int) ($oldVenta['idcotizacion'] ?? 0) !== $cotizacionId || in_array($oldVenta['origen_formulario'] ?? '', ['pedido', 'cotizacion'], true)) $oldVenta = [];
?>
<div id="ventaConfig" data-precios-editables="1" data-user="<?= (int) session()->get('idusuario') ?>" data-pedido="<?= $pedidoId ?>" data-cotizacion="<?= $cotizacionId ?>" data-iniciar-pedido="<?= !empty($iniciarPedido) ? '1' : '0' ?>" data-pedido-datos="<?= esc(json_encode($datosPedido ?? []), 'attr') ?>" data-old="<?= esc(json_encode($oldVenta), 'attr') ?>" data-stock='<?= esc($stockPorLocal ?? "{}", 'attr') ?>' data-series='<?= esc($seriesDisponibles ?? "{}", 'attr') ?>' data-combos='<?= esc(json_encode($comboComponentes ?? []), 'attr') ?>'></div>

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
