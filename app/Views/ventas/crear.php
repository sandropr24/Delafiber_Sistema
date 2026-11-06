<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<link rel="stylesheet" href="<?= base_url('css/ventas/crear.css') ?>">

<div class="container-fluid px-3 px-lg-4 py-3 df-venta">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Busca artículos, prepara el carrito y confirma la venta en pocos pasos.</p>
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
        <div class="row g-3 align-items-start df-venta-grid">
            <div class="col-12 col-xl-7">
                <div class="card border shadow-sm rounded-3 mb-3">
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
                                    <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                            <div class="col-12 col-md-7">
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
                                        <option value="<?= esc($x['idestado']) ?>"><?= esc($x['estado']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Tipo de Documento <span class="text-danger">*</span></label>
                            <select name="tipodocumento" class="form-select" required>
                                <option value="NOTA_VENTA">Nota de venta</option>
                            </select>
                        </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Número <span class="text-danger">*</span></label>
                                <input type="text" name="numero" class="form-control" placeholder="00000123" maxlength="8" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm rounded-3">
                    <div class="card-header bg-light py-3 px-4 border-bottom">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam text-primary"></i>
                            2. Buscar y agregar artículos
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="buscarArticulo" class="form-label small fw-bold text-secondary">Buscar artículo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="search" id="buscarArticulo" class="form-control" placeholder="Escribe el nombre de un producto, servicio o combo..." autocomplete="off">
                            </div>
                            <div class="d-flex gap-2 flex-wrap mt-2" id="filtrosArticulos">
                                <button type="button" class="btn btn-sm btn-primary" data-tipo="todos">Todos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="producto">Productos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="servicio">Servicios</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="combo">Combos</button>
                            </div>
                            <div class="form-text" id="resultadoBusqueda" aria-live="polite">Selecciona el tipo o busca por nombre.</div>
                        </div>
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
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="1" value="1">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase">P. Venta (S/)</label>
                                    <input type="number" class="form-control text-end" id="inputPrecio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-12 col-md-6">
                                    <button type="button" class="btn btn-primary w-100 fw-bold" id="btnAgregarItem" title="Agregar producto">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar al carrito
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card border shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-light py-3 px-4">
                        <h6 class="m-0 fw-bold"><i class="bi bi-truck me-2"></i>3. Entrega</h6>
                    </div>
                    <div class="card-body p-3">
                        <label class="form-label">Modalidad de entrega</label>
                        <select class="form-select mb-3" name="tipoentrega" id="tipoentrega">
                            <option value="RECOJO_TIENDA">Recojo en tienda</option>
                            <option value="DOMICILIO">Entrega a domicilio (sin costo)</option>
                        </select>
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
                                <input class="form-control" name="telefono_entrega" id="telefono_entrega" maxlength="9">
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
            <div class="col-12 col-xl-5">
                <div class="card df-carrito-card mb-3">
                    <div class="card-header"><h6 class="m-0 fw-bold"><i class="bi bi-cart3 text-primary me-2"></i>Carrito de venta</h6></div>
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
                    <div class="card-header bg-light py-3 px-4 border-bottom">
                        <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-primary"></i>
                            4. Pago
                        </h6>
                    </div>
                    <div class="card-body p-3">
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
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-secondary fs-6 text-uppercase">Total a pagar:</span>
                            <h3 class="fw-bold text-success mb-0" id="lblTotal">S/ 0.00</h3>
                            <input type="hidden" name="total" id="inputTotal" value="0.00">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <span class="fw-semibold">Confirmar venta</span>
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

<div id="ventaConfig" data-stock='<?= esc($stockPorLocal ?? "{}", 'attr') ?>' data-series='<?= esc($seriesDisponibles ?? "{}", 'attr') ?>' data-combos='<?= esc(json_encode($comboComponentes ?? []), 'attr') ?>'></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/ventas/crear.js') ?>"></script>
<?= $this->endSection() ?>
