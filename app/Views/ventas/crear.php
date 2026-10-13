<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Emisión de comprobantes de pago y salida automática de inventario (Kardex).</p>
        </div>
        <a href="<?= base_url('ventas') ?>" class="btn btn-sm btn-light border text-secondary shadow-sm px-3 py-2">
            <i class="fas fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('ventas/guardar') ?>" method="POST" autocomplete="off" id="formVenta">
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border rounded-3 shadow-sm mb-4" style="border-color: #e0e0e0 !important;">
                    <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-file-invoice me-2"></i>Datos del comprobante
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Local / Sucursal <span class="text-danger">*</span></label>
                            <select name="idlocal" class="form-select" required>
                                <option value="">Seleccione local...</option>
                                <?php if (!empty($locales)): ?>
                                    <?php foreach ($locales as $loc): ?>
                                        <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Cliente <span class="text-danger">*</span></label>
                            <select name="idcliente" class="form-select" required>
                                <option value="">Seleccione cliente...</option>
                                <?php if (!empty($clientes)): ?>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?= $cli['idpersona'] ?>">
                                            <?= trim(esc($cli['nombres'] ?? '') . ' ' . esc($cli['apellidos'] ?? '')) ?: esc($cli['razonsocial'] ?? 'Sin Nombre') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Tipo de Documento <span class="text-danger">*</span></label>
                            <select name="tipodocumento" class="form-select" required>
                                <option value="BOLETA">Boleta de Venta</option>
                                <option value="FACTURA">Factura</option>
                                <option value="TICKET">Ticket de Caja</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold text-secondary">Serie <span class="text-danger">*</span></label>
                                <input type="text" name="serie" class="form-control" placeholder="B001" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold text-secondary">Número <span class="text-danger">*</span></label>
                                <input type="text" name="numero" class="form-control" placeholder="0000123" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border rounded-3 shadow-sm bg-light" style="border-color: #e0e0e0 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-secondary fs-5">Total a Pagar:</span>
                            <h3 class="fw-bold text-primary mb-0" id="lblTotal">S/. 0.00</h3>
                            <input type="hidden" name="total" id="inputTotal" value="0.00">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> Procesar Venta
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
                    <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-boxes me-2"></i>Detalle de productos
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
                                                data-nombre="<?= esc($prod['nombre'] ?? $prod['descripcion']) ?>"
                                                data-precio="<?= $prod['precioventa'] ?? 0 ?>">
                                                <?= esc($prod['nombre'] ?? $prod['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Cantidad</label>
                                    <input type="number" class="form-control text-center" id="inputCantidad" min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">P. Venta (S/)</label>
                                    <input type="number" class="form-control text-end" id="inputPrecio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btnAgregarItem" title="Agregar producto">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <thead class="table-light text-uppercase fs-7 text-secondary">
                                    <tr>
                                        <th>PRODUCTO</th>
                                        <th class="text-center" style="width: 120px;">CANTIDAD</th>
                                        <th class="text-end" style="width: 150px;">PRECIO (S/)</th>
                                        <th class="text-end" style="width: 150px;">SUBTOTAL (S/)</th>
                                        <th class="text-center" style="width: 60px;">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="carritoBody">
                                    <tr id="filaVacia">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            No se han agregado productos a la venta.
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.getElementById('selectProducto').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const precio = option.getAttribute('data-precio') || 0;
        document.getElementById('inputPrecio').value = parseFloat(precio).toFixed(2);
    });

    document.getElementById('btnAgregarItem').addEventListener('click', function() {
        const select = document.getElementById('selectProducto');
        const option = select.options[select.selectedIndex];
        const cantInput = document.getElementById('inputCantidad');
        const precioInput = document.getElementById('inputPrecio');

        if (!select.value) {
            alert('Por favor seleccione un producto.');
            return;
        }

        const idproducto = select.value;
        const nombre = option.getAttribute('data-nombre');
        const cantidad = parseInt(cantInput.value) || 1;
        const precio = parseFloat(precioInput.value) || parseFloat(option.getAttribute('data-precio')) || 0;

        const filaVacia = document.getElementById('filaVacia');
        if (filaVacia) {
            filaVacia.remove();
        }

        const tbody = document.getElementById('carritoBody');

        const filaExistente = document.getElementById(`row-${idproducto}`);
        if (filaExistente) {
            const inputCant = filaExistente.querySelector('.input-cantidad');
            inputCant.value = parseInt(inputCant.value) + cantidad;
            actualizarSubtotal(idproducto, precio);
            cantInput.value = 1;
            select.value = '';
            precioInput.value = '';
            return;
        }

        const tr = document.createElement('tr');
        tr.id = `row-${idproducto}`;
        tr.innerHTML = `
            <td>
                <span class="fw-semibold text-dark">${nombre}</span>
                <input type="hidden" name="detalles[${idproducto}][idproducto]" value="${idproducto}">
            </td>
            <td class="text-center">
                <input type="number" name="detalles[${idproducto}][cantidad]" value="${cantidad}" min="1" class="form-control form-control-sm text-center input-cantidad" onchange="actualizarSubtotal('${idproducto}', ${precio})" onkeyup="actualizarSubtotal('${idproducto}', ${precio})">
            </td>
            <td class="text-end text-secondary">
                S/ ${precio.toFixed(2)}
                <input type="hidden" name="detalles[${idproducto}][precioventa]" value="${precio}">
            </td>
            <td class="text-end fw-bold text-dark lbl-subtotal" id="subtotal-${idproducto}">
                S/ ${(cantidad * precio).toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="eliminarFila('${idproducto}')">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        select.value = '';
        cantInput.value = 1;
        precioInput.value = '';
        recalcularTotal();
    });

    function actualizarSubtotal(idproducto, precio) {
        const row = document.getElementById(`row-${idproducto}`);
        const cantidad = parseInt(row.querySelector('.input-cantidad').value) || 1;
        const subtotal = cantidad * precio;

        row.querySelector('.lbl-subtotal').textContent = `S/ ${subtotal.toFixed(2)}`;
        recalcularTotal();
    }

    function eliminarFila(idproducto) {
        document.getElementById(`row-${idproducto}`).remove();
        recalcularTotal();

        const tbody = document.getElementById('carritoBody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr id="filaVacia">
                    <td colspan="5" class="text-center text-muted py-5">
                        No se han agregado productos a la venta.
                    </td>
                </tr>
            `;
        }
    }

    function recalcularTotal() {
        let total = 0;
        document.querySelectorAll('.lbl-subtotal').forEach(el => {
            const val = parseFloat(el.textContent.replace('S/', '').trim()) || 0;
            total += val;
        });

        document.getElementById('lblTotal').textContent = `S/ ${total.toFixed(2)}`;
        document.getElementById('inputTotal').value = total.toFixed(2);
    }
</script>
<?= $this->endSection() ?>