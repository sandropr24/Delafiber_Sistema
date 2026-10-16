<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Emisión de comprobantes de pago, salida automática de inventario (Kardex) y control de pagos mixtos.</p>
        </div>
        <a href="<?= base_url('ventas') ?>" class="btn btn-sm btn-light border text-secondary shadow-sm px-3 py-2">
            <i class="fas fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

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
                            <select name="idlocal" id="selectLocal" class="form-select" required>
                                <option value="">Seleccione local...</option>
                                <?php if (!empty($locales)): ?>
                                    <?php foreach ($locales as$loc): ?>
                                        <option value="<?= $loc['idlocal'] ?>"><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Cliente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="idcliente" id="selectCliente" class="form-select" required>
                                    <option value="">Seleccione cliente...</option>
                                    <?php if (!empty($clientes)): ?>
                                        <?php foreach ($clientes as$cli): ?>
                                            <option value="<?= $cli['idpersona'] ?>">
                                                <?= trim(esc($cli['nombres'] ?? '') . ' ' . esc($cli['apellidos'] ?? '')) ?: esc($cli['razonsocial'] ?? 'Sin Nombre') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalClienteExpress" title="Registrar nuevo cliente rápido">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
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
                                <input type="text" name="serie" class="form-control" placeholder="B001" maxlength="4" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold text-secondary">Número <span class="text-danger">*</span></label>
                                <input type="text" name="numero" class="form-control" placeholder="00000123" maxlength="8" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border rounded-3 shadow-sm mb-4" style="border-color: #e0e0e0 !important;">
                    <div class="card-header py-3 px-4" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-money-bill-wave me-2"></i>Formas de Pago
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary">Método de Pago</label>
                                <select class="form-select form-select-sm" id="selectTipoPago">
                                    <option value="" selected disabled>Seleccionar método...</option>
                                    <?php if (!empty($tipospago)): ?>
                                        <?php foreach ($tipospago as$tp): ?>
                                            <option value="<?= $tp['idtipopago'] ?>" data-nombre="<?= esc($tp['tipopago']) ?>">
                                                <?= esc($tp['tipopago']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label small fw-bold text-secondary">Monto (S/)</label>
                                <input type="number" class="form-control form-control-sm text-end" id="inputMontoPago" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100 fw-bold shadow-sm" id="btnAgregarPago">
                                    <i class="fas fa-plus"></i> +
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaPagos">
                                <thead class="table-light text-uppercase fs-7 text-secondary">
                                    <tr>
                                        <th>MÉTODO</th>
                                        <th class="text-end" style="width: 80px;">MONTO</th>
                                        <th class="text-end" style="width: 70px">ACCION</th>
                                    </tr>
                                </thead>
                                <tbody id="pagosBody">
                                    <tr id="filaSinPagos">
                                        <td colspan="3" class="text-center text-muted py-2 small">
                                            Sin pagos asignados.
                                        </td>
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
                                        <?php foreach ($productos as$prod): ?>
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
                                        <th class="text-start" style="width: 40%;">PRODUCTO</th>
                                        <th class="text-center" style="width: 15%;">CANTIDAD</th>
                                        <th class="text-center" style="width: 15%;">PRECIO (S/)</th>
                                        <th class="text-center" style="width: 15%;">SUBTOTAL (S/)</th>
                                        <th class="text-center" style="width: 15%;">ACCIÓN</th>
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

<div class="modal fade" id="modalClienteExpress" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Registrar Cliente Rápido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formClienteExpress">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Documento (DNI / RUC) <span class="text-danger">*</span></label>
                        <input type="text" name="documento" id="express_documento" class="form-control" maxlength="11" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre o Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="express_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Teléfono / Celular</label>
                        <input type="text" name="telefono" id="express_telefono" class="form-control" maxlength="9">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnGuardarClienteExpress">
                        <i class="fas fa-save me-1"></i> Guardar y Seleccionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const stockPorLocal = <?= $stockPorLocal ?? '{}' ?>;

    document.getElementById('selectProducto').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const precio = option.getAttribute('data-precio') || 0;
        document.getElementById('inputPrecio').value = parseFloat(precio).toFixed(2);
    });

    document.getElementById('btnAgregarItem').addEventListener('click', function() {
        const selectLocal = document.getElementById('selectLocal');
        const selectProducto = document.getElementById('selectProducto');
        const option = selectProducto.options[selectProducto.selectedIndex];
        const cantInput = document.getElementById('inputCantidad');
        const precioInput = document.getElementById('inputPrecio');

        if (!selectLocal.value) {
            alert('Por favor, seleccione un Local / Sucursal antes de agregar productos.');
            selectLocal.focus();
            return;
        }

        if (!selectProducto.value) {
            alert('Por favor seleccione un producto.');
            return;
        }

        const idlocal = selectLocal.value;
        const idproducto = selectProducto.value;
        const nombre = option.getAttribute('data-nombre');
        const cantidadAgregar = parseInt(cantInput.value) || 1;
        const precio = parseFloat(precioInput.value) || parseFloat(option.getAttribute('data-precio')) || 0;

        const stockDisponible = (stockPorLocal[idlocal] && stockPorLocal[idlocal][idproducto] !== undefined) ?
            stockPorLocal[idlocal][idproducto] :
            0;

        if (cantidadAgregar > stockDisponible) {
            alert(`¡Stock bajo / insuficiente!\nEl producto "${nombre}" solo cuenta con ${stockDisponible} unidades disponibles en este local.`);
            return;
        }

        const filaExistente = document.getElementById(`row-${idproducto}`);
        let cantidadActualEnTabla = 0;
        if (filaExistente) {
            cantidadActualEnTabla = parseInt(filaExistente.querySelector('.input-cantidad').value) || 0;
        }

        const totalAcumulado = cantidadActualEnTabla + cantidadAgregar;
        if (totalAcumulado > stockDisponible) {
            alert(`¡No puedes agregar más!\nEl stock máximo disponible en este local es ${stockDisponible} y ya tienes ${cantidadActualEnTabla} en la lista.`);
            return;
        }

        const filaVacia = document.getElementById('filaVacia');
        if (filaVacia) {
            filaVacia.remove();
        }

        const tbody = document.getElementById('carritoBody');

        if (filaExistente) {
            const inputCant = filaExistente.querySelector('.input-cantidad');
            inputCant.value = totalAcumulado;
            actualizarSubtotal(idproducto, precio);
            cantInput.value = 1;
            selectProducto.value = '';
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
                <input type="number" name="detalles[${idproducto}][cantidad]" value="${cantidadAgregar}" min="1" max="${stockDisponible}" class="form-control form-control-sm text-center input-cantidad" onchange="actualizarSubtotal('${idproducto}', ${precio})" onkeyup="actualizarSubtotal('${idproducto}', ${precio})">
            </td>
            <td class="text-end text-secondary">
                S/ ${precio.toFixed(2)}
                <input type="hidden" name="detalles[${idproducto}][precioventa]" value="${precio}">
            </td>
            <td class="text-end fw-bold text-dark lbl-subtotal" id="subtotal-${idproducto}">
                S/ ${(cantidadAgregar * precio).toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="eliminarFila('${idproducto}')">
                    <i class="bi bi-trash3 fs-5"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        selectProducto.value = '';
        cantInput.value = 1;
        precioInput.value = '';
        recalcularTotal();
    });

    function actualizarSubtotal(idproducto, precio) {
        const row = document.getElementById(`row-${idproducto}`);
        const inputCant = row.querySelector('.input-cantidad');
        const selectLocal = document.getElementById('selectLocal');
        const idlocal = selectLocal.value;

        const stockDisponible = (stockPorLocal[idlocal] && stockPorLocal[idlocal][idproducto] !== undefined) ?
            stockPorLocal[idlocal][idproducto] :
            999999;

        let cantidad = parseInt(inputCant.value) || 1;

        if (cantidad > stockDisponible) {
            alert(`La cantidad ingresada supera el stock disponible (${stockDisponible}).`);
            inputCant.value = stockDisponible;
            cantidad = stockDisponible;
        }

        if (cantidad < 1) {
            inputCant.value = 1;
            cantidad = 1;
        }

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
        recalcularPagos();
    }

    document.getElementById('btnAgregarPago').addEventListener('click', function() {
        const selectTp = document.getElementById('selectTipoPago');
        const option = selectTp.options[selectTp.selectedIndex];
        const inputMonto = document.getElementById('inputMontoPago');

        if (!selectTp.value) {
            alert('Seleccione un método de pago.');
            return;
        }

        const idtipopago = selectTp.value;
        const nombreMetodo = option.getAttribute('data-nombre');
        const montoPago = parseFloat(inputMonto.value) || 0;

        if (montoPago <= 0) {
            alert('Ingrese un monto válido mayor a 0.');
            return;
        }

        const filaSinPagos = document.getElementById('filaSinPagos');
        if (filaSinPagos) {
            filaSinPagos.remove();
        }

        const tbody = document.getElementById('pagosBody');
        const idFilaPago = `pago-${idtipopago}`;
        if (document.getElementById(idFilaPago)) {
            alert('Este método de pago ya fue agregado. Modifique su monto directamente en la tabla si lo requiere.');
            return;
        }

        const tr = document.createElement('tr');
        tr.id = idFilaPago;
        tr.innerHTML = `
            <td>
                <span class="fw-semibold text-dark small">${nombreMetodo}</span>
                <input type="hidden" name="pagos[${idtipopago}][idtipopago]" value="${idtipopago}">
            </td>
            <td class="text-end">
                <input type="number" name="pagos[${idtipopago}][monto]" value="${montoPago.toFixed(2)}" step="0.01" class="form-control form-control-sm text-end input-monto-pago" onchange="recalcularPagos()" onkeyup="recalcularPagos()">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="eliminarFilaPago('${idFilaPago}')">
                    <i class="bi bi-trash3 fs-5"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        selectTp.value = '';
        inputMonto.value = '';
        recalcularPagos();
    });

    function recalcularPagos() {
        let totalVenta = parseFloat(document.getElementById('inputTotal').value) || 0;
        let totalPagado = 0;

        document.querySelectorAll('.input-monto-pago').forEach(input => {
            totalPagado += parseFloat(input.value) || 0;
        });

        document.getElementById('lblTotalPagado').textContent = `S/ ${totalPagado.toFixed(2)}`;

        const diferencia = totalPagado - totalVenta;
        const lblFaltanteVuelto = document.getElementById('lblFaltanteVuelto');

        if (diferencia >= 0) {
            lblFaltanteVuelto.className = 'text-info fw-bold';
            lblFaltanteVuelto.textContent = `Vuelto: S/ ${diferencia.toFixed(2)}`;
        } else {
            lblFaltanteVuelto.className = 'text-danger fw-bold';
            lblFaltanteVuelto.textContent = `Faltante: S/ ${Math.abs(diferencia).toFixed(2)}`;
        }
    }

    function eliminarFilaPago(idFila) {
        document.getElementById(idFila).remove();
        recalcularPagos();

        const tbody = document.getElementById('pagosBody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr id="filaSinPagos">
                    <td colspan="3" class="text-center text-muted py-2 small">
                        Sin pagos asignados.
                    </td>
                </tr>
            `;
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const formExpress = document.getElementById('formClienteExpress');
        if (formExpress) {
            formExpress.addEventListener('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(this);
                let btn = document.getElementById('btnGuardarClienteExpress');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

                fetch('<?= base_url('clientes/guardar-express') ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            let select = document.getElementById('selectCliente');
                            
                            let option = document.createElement('option');
                            option.value = data.idcliente;
                            option.text = data.nombre + ' (' + data.documento + ')';
                            option.selected = true;

                            select.appendChild(option);
                            select.value = data.idcliente;

                            let modalEl = document.getElementById('modalClienteExpress');
                            let modal = bootstrap.Modal.getInstance(modalEl);
                            modal.hide();
                            formExpress.reset();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ocurrió un error al registrar el cliente.');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar y Seleccionar';
                    });
            });
        }
    });

    document.getElementById('formVenta').addEventListener('submit', function(e) {
        const selectCliente = document.getElementById('selectCliente');
        
        if (!selectCliente.value || selectCliente.value === 'false' || selectCliente.value === false) {
            e.preventDefault();
            alert('Por favor, seleccione un cliente válido de la lista.');
            selectCliente.focus();
            return;
        }

        let totalVenta = parseFloat(document.getElementById('inputTotal').value) || 0;
        let totalPagado = 0;

        document.querySelectorAll('.input-monto-pago').forEach(input => {
            totalPagado += parseFloat(input.value) || 0;
        });

        if (totalVenta <= 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto a la venta.');
            return;
        }

        if (totalPagado < totalVenta) {
            e.preventDefault();
            alert(`El monto total pagado (S/ ${totalPagado.toFixed(2)}) es menor al total de la venta (S/ ${totalVenta.toFixed(2)}). Falta cubrir dinero.`);
            return;
        }
    });
</script>
<?= $this->endSection() ?>