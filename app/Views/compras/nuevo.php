<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/compras/nuevo.css') ?>?v=<?= filemtime(FCPATH . 'css/compras/nuevo.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('contenido') ?>

<div class="container-fluid compra-nueva">
    <nav class="compra-breadcrumb" aria-label="Ruta de navegación">
        <a href="<?= base_url('admin/compras') ?>">Compras</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Nueva compra</span>
    </nav>
    <header class="compra-page-heading">
        <div>
            <h1>Nueva compra</h1>
            <p>Registra tu comprobante y los productos que ingresan al almacén.</p>
        </div>
        <a href="<?= base_url('admin/compras') ?>" class="btn compra-btn-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al historial</a>
    </header>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('admin/compras/guardar') ?>" method="POST" id="formCompra">
        <?= csrf_field() ?>
        <div class="compra-layout">
            <div class="compra-principal">
                <section class="compra-section" aria-labelledby="tituloDatosCompra">
                    <header class="compra-section-header">
                        <span class="compra-paso" aria-hidden="true">01</span>
                        <div>
                            <h2 id="tituloDatosCompra">Datos de la compra</h2>
                            <p>Proveedor, comprobante y almacén de destino.</p>
                        </div>
                        <span class="compra-campos-nota"><span class="compra-required">*</span> Obligatorios</span>
                    </header>
                    <div class="compra-section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="idproveedor" class="form-label">Proveedor <span class="compra-required">*</span></label>
                                <select class="form-select" name="idproveedor" id="idproveedor" required>
                                    <option value="" selected disabled>Selecciona un proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= esc($prov['idproveedor'], 'attr') ?>"><?= esc($prov['razonsocial']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="idlocal" class="form-label">Almacén de destino <span class="compra-required">*</span></label>
                                <select class="form-select" name="idlocal" id="idlocal" required>
                                    <?php foreach ($locales as $loc): ?>
                                        <option value="<?= esc($loc['idlocal'], 'attr') ?>"><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="compra-separador"></div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-4">
                                <label for="tipocomprobante" class="form-label">Comprobante <span class="compra-required">*</span></label>
                                <select class="form-select" name="tipocomprobante" id="tipocomprobante" required>
                                    <option value="FACTURA">Factura</option>
                                    <option value="BOLETA">Boleta</option>
                                    <option value="GUIA">Guía de remisión</option>
                                </select>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label for="serie" class="form-label">Serie <span class="compra-required">*</span></label>
                                <input type="text" class="form-control" name="serie" id="serie" placeholder="Ej. F001" maxlength="20" required>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label for="numero" class="form-label">Número <span class="compra-required">*</span></label>
                                <input type="text" class="form-control" name="numero" id="numero" placeholder="Ej. 000123" maxlength="20" required>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="fechacompra" class="form-label">Fecha de compra <span class="compra-required">*</span></label>
                                <input type="date" class="form-control" name="fechacompra" id="fechacompra" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label for="moneda" class="form-label">Moneda</label>
                                <select name="moneda" id="moneda" class="form-select">
                                    <option value="PEN">Soles (PEN)</option>
                                    <option value="USD">Dólares (USD)</option>
                                </select>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label for="tipocambio" class="form-label">Tipo de cambio</label>
                                <input type="number" class="form-control" name="tipocambio" id="tipocambio" min="0.0001" step="0.0001" value="1.0000" aria-describedby="ayudaTipoCambio" required readonly>
                            </div>
                        </div>
                        <p class="compra-ayuda" id="ayudaTipoCambio"><i class="bi bi-info-circle" aria-hidden="true"></i> En dólares, indica cuántos soles equivalen a 1 USD.</p>
                    </div>
                </section>

                <section class="compra-section" aria-labelledby="tituloProductosCompra">
                    <header class="compra-section-header">
                        <span class="compra-paso" aria-hidden="true">02</span>
                        <div>
                            <h2 id="tituloProductosCompra">Productos de la compra</h2>
                            <p>Agrega cantidades, costos y números de serie.</p>
                        </div>
                        <span class="compra-contador" id="contadorItemsCompra">0 artículos</span>
                    </header>
                    <div class="compra-section-body">
                        <div class="compra-agregar-panel">
                            <div class="compra-agregar-titulo"><i class="bi bi-plus-circle" aria-hidden="true"></i> Agregar producto</div>
                            <div class="compra-agregar-grid">
                                <div class="compra-selector-producto">
                                    <label for="selectProducto" class="form-label">Producto</label>
                                    <select class="form-select" id="selectProducto">
                                        <option value="" selected disabled>Selecciona un producto</option>
                                        <?php foreach ($productos as $prod): ?>
                                            <option value="<?= esc($prod['idproducto'], 'attr') ?>"
                                                data-nombre="<?= esc($prod['nombre'] ?? $prod['descripcion'], 'attr') ?>"
                                                data-unidad="<?= esc($prod['abreviaturaunidad'] ?? 'UND', 'attr') ?>"
                                                data-serie="<?= (int) ($prod['controlaserie'] ?? 0) ?>"
                                                data-precio="<?= esc($prod['precioventa'] ?? 0, 'attr') ?>">
                                                <?= esc($prod['nombre'] ?? $prod['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="inputCantidad" class="form-label">Cantidad <span id="etiquetaUnidad" class="compra-unidad"></span></label>
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="0.01" step="0.01" value="1">
                                </div>
                                <div>
                                    <label for="inputPrecio" class="form-label">Costo unitario</label>
                                    <div class="input-group compra-precio-input">
                                        <span class="input-group-text" id="simboloPrecioCompra">S/</span>
                                        <input type="number" class="form-control text-end" id="inputPrecio" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                </div>
                                <button type="button" class="btn compra-btn-agregar" id="btnAgregarItem"><i class="bi bi-plus-lg" aria-hidden="true"></i> <span>Agregar</span></button>
                            </div>
                        </div>
                        <div class="compra-series-panel d-none" id="panelSeriesCompra">
                            <div class="compra-series-titulo"><i class="bi bi-upc-scan" aria-hidden="true"></i> Números de serie</div>
                            <p>Ingresa una serie diferente por cada unidad antes de agregar el producto.</p>
                            <div class="row g-2" id="camposSeriesCompra"></div>
                        </div>
                        <div class="compra-tabla-cabecera">
                            <span>Detalle de productos</span>
                            <span class="compra-tabla-nota">Importes en la moneda seleccionada</span>
                        </div>
                        <div class="table-responsive compra-tabla-wrap">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <caption class="visually-hidden">Productos que se registrarán en esta compra</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col" class="text-center">Cantidad</th>
                                        <th scope="col" class="text-end">Costo unit.</th>
                                        <th scope="col" class="text-end">Importe</th>
                                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="filaVacia">
                                        <td colspan="5" class="compra-estado-vacio">
                                            <div class="compra-vacio-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                                            <strong>Tu compra empieza aquí</strong>
                                            <span>Selecciona un producto, indica su cantidad y costo,<br class="d-none d-sm-block"> y pulsa <b>Agregar</b> para incluirlo en el detalle.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                <p class="compra-nota-final"><i class="bi bi-info-circle" aria-hidden="true"></i> Revisa las cantidades y los costos antes de guardar la compra.</p>
            </div>
            <aside class="compra-lateral">
                <section class="compra-resumen" aria-labelledby="tituloResumenCompra">
                    <div class="compra-resumen-encabezado">
                        <span class="compra-resumen-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="tituloResumenCompra">Resumen de compra</h2>
                            <p>Verifica los datos del ingreso</p>
                        </div>
                    </div>
                    <div class="compra-resumen-cuerpo">
                        <dl class="compra-resumen-datos">
                            <div><dt>Proveedor</dt><dd id="resumenProveedor" class="compra-sin-dato">Sin seleccionar</dd></div>
                            <div><dt>Almacén de destino</dt><dd id="resumenAlmacen">Sin seleccionar</dd></div>
                        </dl>
                        <div class="compra-resumen-desglose">
                            <div><span>Moneda</span><strong id="resumenMoneda">Soles · PEN</strong></div>
                            <div><span>Líneas de detalle</span><strong id="resumenLineas">0</strong></div>
                            <div><span>Series registradas</span><strong id="resumenSeries">0</strong></div>
                        </div>
                        <div class="compra-total-panel">
                            <label for="totalGeneral">Total de la compra</label>
                            <div class="compra-total-row">
                                <span id="simboloTotalCompra">S/</span>
                                <input type="text" id="totalGeneral" value="0.00" readonly aria-label="Total de la compra">
                            </div>
                            <span class="compra-total-nota">En la moneda del comprobante</span>
                        </div>
                        <button type="submit" class="btn compra-btn-guardar w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i> Guardar compra <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                        <p class="compra-guardar-nota">La compra se registrará al guardar.</p>
                    </div>
                </section>
                <div class="compra-inventario-nota">
                    <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
                    <div><strong>Ingreso al inventario</strong><p>Al guardar, el stock y el Kardex se actualizarán en el almacén seleccionado.</p></div>
                </div>
                <div class="visually-hidden" id="anuncioDetalleCompra" role="status" aria-live="polite" aria-atomic="true"></div>
            </aside>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/compras/nuevo.js') ?>"></script>
<?= $this->endSection() ?>
