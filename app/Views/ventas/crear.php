<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Emisión de comprobantes de pago, salida automática de inventario (Kardex) y control de pagos mixtos.</p>
        </div>
        <div>
            <a href="<?= base_url('ventas') ?>" class="btn btn-light border text-secondary shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-arrow-left"></i>
                <span class="fw-medium">Volver al listado</span>
            </a>
        </div>
    </div>

    <form action="<?= base_url('ventas/guardar') ?>" method="POST" autocomplete="off" id="formVenta">
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border shadow-sm rounded-3 mb-4" style="border-color: #e5e7eb !important;">
                    <div class="card-header bg-light py-3 px-4 border-bottom" style="border-color: #e5e7eb !important;">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text text-primary"></i>
                            Datos del comprobante
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Local / Sucursal <span class="text-danger">*</span></label>
                            <select name="idlocal" id="selectLocal" class="form-select" required>
                                <option value="">Seleccione local...</option>
                                <?php foreach ($locales ?? [] as $loc): ?>
                                    <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Cliente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="idcliente" id="selectCliente" class="form-select" required>
                                    <option value="">Seleccione cliente...</option>
                                    <?php foreach ($clientes ?? [] as $cli): ?>
                                        <option value="<?= $cli['idpersona'] ?>">
                                            <?= trim(esc($cli['nombres'] ?? '') . ' ' . esc($cli['apellidos'] ?? '')) ?: esc($cli['razonsocial'] ?? 'Sin Nombre') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalClienteExpress" title="Cliente rápido">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Tipo de Documento <span class="text-danger">*</span></label>
                            <select name="tipodocumento" class="form-select" required>
                                <option value="BOLETA">Boleta de Venta</option>
                                <option value="FACTURA">Factura</option>
                                <option value="TICKET">Ticket de Caja</option>
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-6 mb-0">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Serie <span class="text-danger">*</span></label>
                                <input type="text" name="serie" class="form-control" placeholder="B001" maxlength="4" required>
                            </div>
                            <div class="col-6 mb-0">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Número <span class="text-danger">*</span></label>
                                <input type="text" name="numero" class="form-control" placeholder="00000123" maxlength="8" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm rounded-3 mb-4" style="border-color: #e5e7eb !important;">
                    <div class="card-header bg-light py-3 px-4 border-bottom" style="border-color: #e5e7eb !important;">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-primary"></i>
                            Formas de Pago
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12">
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
                            <div class="col-8">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Monto (S/)</label>
                                <input type="number" class="form-control form-control-sm text-end" id="inputMontoPago" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100 fw-bold shadow-sm" id="btnAgregarPago">
                                    <i class="bi bi-plus-lg"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-2 mb-2">
                            <table class="table table-hover align-middle mb-0" id="tablaPagos">
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                                    <tr>
                                        <th class="ps-3 py-2">MÉTODO</th>
                                        <th class="text-end py-2" style="width: 80px;">MONTO</th>
                                        <th class="pe-3 text-end py-2" style="width: 70px">ACCIONES</th>
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

                <div class="card border shadow-sm rounded-3 bg-light" style="border-color: #e5e7eb !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-secondary fs-6 text-uppercase">Total a Pagar:</span>
                            <h3 class="fw-bold text-success mb-0" id="lblTotal">S/ 0.00</h3>
                            <input type="hidden" name="total" id="inputTotal" value="0.00">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <span class="fw-semibold">Procesar Venta</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card border shadow-sm rounded-3 h-100" style="border-color: #e5e7eb !important;">
                    <div class="card-header bg-light py-3 px-4 border-bottom" style="border-color: #e5e7eb !important;">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam text-primary"></i>
                            Detalle de productos
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-light rounded-3 mb-4 border">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Producto</label>
                                    <select class="form-select" id="selectProducto">
                                        <option value="" selected disabled>Seleccionar producto...</option>
                                        <?php foreach ($productos ?? [] as $prod): ?>
                                            <option value="<?= $prod['idproducto'] ?>"
                                                data-nombre="<?= esc($prod['nombre'] ?? $prod['descripcion']) ?>"
                                                data-precio="<?= $prod['precioventa'] ?? 0 ?>">
                                                <?= esc($prod['nombre'] ?? $prod['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Cantidad</label>
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">P. Venta (S/)</label>
                                    <input type="number" class="form-control text-end" id="inputPrecio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btnAgregarItem" title="Agregar producto">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-2">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom" style="border-color: #e5e7eb !important;">
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 40%;">PRODUCTO</th>
                                        <th class="text-center py-3" style="width: 15%;">CANTIDAD</th>
                                        <th class="text-end py-3" style="width: 15%;">PRECIO (S/)</th>
                                        <th class="text-end py-3" style="width: 15%;">SUBTOTAL (S/)</th>
                                        <th class="pe-4 text-center py-3" style="width: 15%;">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="carritoBody">
                                    <tr id="filaVacia">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-cart-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                            <p class="mb-0 fw-medium">No se han agregado productos a la venta.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
            <form id="formClienteExpress" data-url-express="<?= base_url('clientes/guardar-express') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Documento (DNI / RUC) <span class="text-danger">*</span></label>
                        <input type="text" name="documento" class="form-control" maxlength="11" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Nombre o Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control" maxlength="9">
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

<div id="ventaConfig" data-stock='<?= $stockPorLocal ?? "{}" ?>'></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/ventas/crear.js') ?>"></script>
<?= $this->endSection() ?>