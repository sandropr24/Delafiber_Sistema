<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Registrar ingreso de mercadería y actualizar automáticamente el Kardex.</p>
        </div>
        <div>
            <a href="<?= base_url('compras') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a Compras
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('compras/guardar') ?>" method="POST" id="formCompra">
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-file-invoice me-2"></i>Información General
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="idproveedor" class="form-label fw-semibold small text-uppercase text-muted">Proveedor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-truck"></i></span>
                                <select class="form-select" name="idproveedor" id="idproveedor" required>
                                    <option value="" selected disabled>Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= $prov['idproveedor'] ?>"><?= esc($prov['razonsocial']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tipocomprobante" class="form-label fw-semibold small text-uppercase text-muted">Tipo Comprobante <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-receipt"></i></span>
                                <select class="form-select" name="tipocomprobante" id="tipocomprobante" required>
                                    <option value="FACTURA">FACTURA</option>
                                    <option value="BOLETA">BOLETA</option>
                                    <option value="GUIA">GUÍA DE REMISIÓN</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="serie" class="form-label fw-semibold small text-uppercase text-muted">Serie / Nro <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-hashtag"></i></span>
                                <input type="text" class="form-control" name="serie" id="serie" placeholder="Ej. F001-000123" maxlength="10" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fechacompra" class="form-label fw-semibold small text-uppercase text-muted">Fecha de Compra <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" name="fechacompra" id="fechacompra" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="idlocal" class="form-label fw-semibold small text-uppercase text-muted">Almacén / Destino <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-warehouse"></i></span>
                                <select class="form-select" name="idlocal" id="idlocal" required>
                                    <?php foreach ($locales as $loc): ?>
                                        <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4 text-center border">
                            <span class="text-uppercase small text-muted d-block fw-semibold mb-1">Total Compra</span>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white fw-bold border-0">S/</span>
                                <input type="text" class="form-control form-control-lg fw-bold text-success text-end bg-white border-0" id="totalGeneral" value="0.00" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm rounded-3 fw-bold">
                            <i class="fas fa-save me-1"></i> Guardar Compra
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-boxes me-2"></i>Detalle de Ítems
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded-3 mb-4 border">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary">Producto</label>
                                    <select class="form-select" id="selectProducto">
                                        <option value="" selected disabled>Seleccionar producto...</option>
                                        <?php foreach ($productos as $prod): ?>
                                            <option value="<?= $prod['idproducto'] ?>"
                                                data-nombre="<?= esc($prod['descripcion'] ?? $prod['nombre']) ?>"
                                                data-precio="<?= $prod['precioventa'] ?? 0 ?>">
                                                <?= esc($prod['descripcion'] ?? $prod['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Cantidad</label>
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">P. Compra (S/)</label>
                                    <input type="number" class="form-control text-end" id="inputPrecio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btnAgregarItem" title="Agregar producto">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <thead class="table-light text-uppercase fs-7 text-secondary">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center" style="width: 120px;">Cantidad</th>
                                        <th class="text-end" style="width: 150px;">Precio (S/)</th>
                                        <th class="text-end" style="width: 150px;">Subtotal (S/)</th>
                                        <th class="text-center" style="width: 60px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="filaVacia">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fas fa-cart-arrow-down fa-2x mb-2 text-black-50 d-block"></i>
                                            No se han agregado productos a la compra.
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnAgregar = document.getElementById('btnAgregarItem');
        const selectProducto = document.getElementById('selectProducto');
        const inputCantidad = document.getElementById('inputCantidad');
        const inputPrecio = document.getElementById('inputPrecio');
        const tablaDetalle = document.getElementById('tablaDetalle').querySelector('tbody');
        const filaVacia = document.getElementById('filaVacia');
        const totalGeneral = document.getElementById('totalGeneral');

        selectProducto.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const precioVenta = selectedOption.getAttribute('data-precio');
            if (precioVenta) {
                inputPrecio.value = parseFloat(precioVenta).toFixed(2);
            } else {
                inputPrecio.value = '';
            }
        });

        btnAgregar.addEventListener('click', function() {
            const idProd = selectProducto.value;
            const nombreProd = selectProducto.options[selectProducto.selectedIndex]?.getAttribute('data-nombre');
            const cantidad = parseInt(inputCantidad.value);
            const precio = parseFloat(inputPrecio.value);

            if (!idProd) {
                alert('Por favor seleccione un producto.');
                return;
            }
            if (isNaN(cantidad) || cantidad <= 0) {
                alert('Ingrese una cantidad válida.');
                return;
            }
            if (isNaN(precio) || precio < 0) {
                alert('Ingrese un precio unitario válido.');
                return;
            }

            const elementoVacio = document.getElementById('filaVacia');
            if (elementoVacio) {
                elementoVacio.remove();
            }

            const subtotal = (cantidad * precio).toFixed(2);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="productos[]" value="${idProd}">
                    <span class="fw-medium text-dark">${nombreProd}</span>
                </td>
                <td class="text-center">
                    <input type="hidden" name="cantidades[]" value="${cantidad}">
                    <span class="badge bg-secondary px-2 py-1 fs-6">${cantidad}</span>
                </td>
                <td class="text-end">
                    <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
                    S/ ${precio.toFixed(2)}
                </td>
                <td class="text-end fw-bold text-success class-subtotal">
                    S/ ${subtotal}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btnEliminar" title="Eliminar ítem">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;

            tablaDetalle.appendChild(tr);

            selectProducto.value = '';
            inputCantidad.value = '1';
            inputPrecio.value = '';

            calcularTotal();
        });

        tablaDetalle.addEventListener('click', function(e) {
            if (e.target.closest('.btnEliminar')) {
                e.target.closest('tr').remove();

                if (tablaDetalle.children.length === 0) {
                    tablaDetalle.innerHTML = `
                    <tr id="filaVacia">
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fas fa-cart-arrow-down fa-2x mb-2 text-black-50 d-block"></i>
                            No se han agregado productos a la compra.
                        </td>
                    </tr>
                `;
                }
                calcularTotal();
            }
        });

        function calcularTotal() {
            let total = 0;
            const subtotales = tablaDetalle.querySelectorAll('.class-subtotal');
            subtotales.forEach(td => {
                const valor = parseFloat(td.textContent.replace('S/', '').trim());
                if (!isNaN(valor)) total += valor;
            });
            totalGeneral.value = total.toFixed(2);
        }
    });
</script>

<?= $this->endSection() ?>