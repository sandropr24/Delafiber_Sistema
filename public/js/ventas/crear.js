document.addEventListener("DOMContentLoaded", function () {
    const tipoEntrega = document.getElementById("tipoentrega");
    const camposEntrega = document.getElementById("camposEntrega");
    const direccionEntrega = document.getElementById("direccion_entrega");
    const telefonoEntrega = document.getElementById("telefono_entrega");

    function actualizarEntrega() {
        if (!tipoEntrega || !camposEntrega) return;
        const esDomicilio = tipoEntrega.value === "DOMICILIO";
        camposEntrega.classList.toggle("d-none", !esDomicilio);
        if (direccionEntrega) direccionEntrega.required = esDomicilio;
        if (telefonoEntrega) telefonoEntrega.required = esDomicilio;
    }

    if (tipoEntrega && camposEntrega) {
        tipoEntrega.addEventListener("change", actualizarEntrega);
        actualizarEntrega();
    }

    const configEl = document.getElementById("ventaConfig");
    let stockPorLocal = {};

    try {
        stockPorLocal = JSON.parse(configEl ? configEl.getAttribute("data-stock") || "{}" : "{}");
    } catch (error) {
        console.error("Error al cargar stock:", error);
    }

    const selectProducto = document.getElementById("selectProducto");

    if (selectProducto) {
        selectProducto.addEventListener("change", function () {
            const option = this.options[this.selectedIndex];
            const precio = option.getAttribute("data-precio") || 0;
            document.getElementById("inputPrecio").value = parseFloat(precio).toFixed(2);
        });
    }

    const btnAgregarItem = document.getElementById("btnAgregarItem");

    if (btnAgregarItem) {
        btnAgregarItem.addEventListener("click", function () {
            const selectLocal = document.getElementById("selectLocal");
            const cantInput = document.getElementById("inputCantidad");
            const precioInput = document.getElementById("inputPrecio");

            if (!selectLocal.value) {
                Swal.fire({
                    icon: "warning",
                    title: "Local requerido",
                    text: "Seleccione un local antes de agregar productos.",
                    confirmButtonColor: "#4f46e5"
                });
                selectLocal.focus();
                return;
            }

            if (!selectProducto.value) {
                Swal.fire({
                    icon: "warning",
                    title: "Producto requerido",
                    text: "Seleccione un producto.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            const option = selectProducto.options[selectProducto.selectedIndex];
            const idlocal = selectLocal.value;
            const idproducto = selectProducto.value;
            const nombre = option.getAttribute("data-nombre");
            const cantidadAgregar = Number(cantInput.value);
            const precio = Number(precioInput.value);

            if (!Number.isInteger(cantidadAgregar) || cantidadAgregar < 1) {
                Swal.fire({
                    icon: "warning",
                    title: "Cantidad inválida",
                    text: "Ingrese una cantidad entera mayor que cero."
                });
                return;
            }

            if (!Number.isFinite(precio) || precio <= 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Precio inválido",
                    text: "Ingrese un precio válido mayor que cero."
                });
                return;
            }

            const stockDisponible = Number(stockPorLocal[idlocal]?.[idproducto] ?? 0);
            const filaExistente = document.getElementById(`row-${idproducto}`);
            const cantidadActual = filaExistente ? Number(filaExistente.querySelector(".input-cantidad").value) : 0;
            const totalAcumulado = cantidadActual + cantidadAgregar;

            if (totalAcumulado > stockDisponible) {
                Swal.fire({
                    icon: "warning",
                    title: "Stock insuficiente",
                    text: `El producto "${nombre}" tiene ${stockDisponible} unidades disponibles. Ya agregaste ${cantidadActual}.`,
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            document.getElementById("filaVacia")?.remove();

            if (filaExistente) {
                const inputCant = filaExistente.querySelector(".input-cantidad");
                inputCant.value = totalAcumulado;
                actualizarSubtotal(idproducto);
                cantInput.value = 1;
                selectProducto.value = "";
                precioInput.value = "";
                return;
            }

            const tbody = document.getElementById("carritoBody");
            const tr = document.createElement("tr");

            tr.id = `row-${idproducto}`;
            tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark nombre-producto"></span>
                    <input type="hidden" name="detalles[${idproducto}][idproducto]" value="${idproducto}">
                </td>
                <td class="text-center">
                    <input type="number" name="detalles[${idproducto}][cantidad]" value="${cantidadAgregar}" min="1" max="${stockDisponible}" class="form-control form-control-sm text-center input-cantidad">
                </td>
                <td class="text-end text-secondary">
                    S/ ${precio.toFixed(2)}
                    <input type="hidden" name="detalles[${idproducto}][precioventa]" value="${precio}">
                </td>
                <td class="text-end fw-bold text-dark lbl-subtotal" id="subtotal-${idproducto}">
                    S/ ${(cantidadAgregar * precio).toFixed(2)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </td>
            `;

            tr.querySelector(".nombre-producto").textContent = nombre;
            tbody.appendChild(tr);

            const inputCantidadAgregado = tr.querySelector(".input-cantidad");
            inputCantidadAgregado.addEventListener("change", () => actualizarSubtotal(idproducto));
            inputCantidadAgregado.addEventListener("input", () => actualizarSubtotal(idproducto));
            tr.querySelector(".btn-eliminar-fila").addEventListener("click", () => eliminarFila(idproducto));

            selectProducto.value = "";
            cantInput.value = 1;
            precioInput.value = "";
            recalcularTotal();
        });
    }

    function actualizarSubtotal(idproducto) {
        const row = document.getElementById(`row-${idproducto}`);
        if (!row) return;

        const inputCant = row.querySelector(".input-cantidad");
        const selectLocal = document.getElementById("selectLocal");
        const precio = Number(row.querySelector('input[name$="[precioventa]"]').value);
        const stockDisponible = Number(stockPorLocal[selectLocal.value]?.[idproducto] ?? 0);
        let cantidad = Number(inputCant.value);

        if (!Number.isInteger(cantidad) || cantidad < 1) cantidad = 1;

        if (cantidad > stockDisponible) {
            Swal.fire({
                icon: "warning",
                title: "Stock excedido",
                text: `Solo hay ${stockDisponible} unidades disponibles.`,
                confirmButtonColor: "#4f46e5"
            });
            cantidad = stockDisponible;
        }

        if (cantidad < 1) {
            eliminarFila(idproducto);
            return;
        }

        inputCant.value = cantidad;
        row.querySelector(".lbl-subtotal").textContent = `S/ ${(cantidad * precio).toFixed(2)}`;
        recalcularTotal();
    }

    function eliminarFila(idproducto) {
        document.getElementById(`row-${idproducto}`)?.remove();
        const tbody = document.getElementById("carritoBody");

        if (!tbody.querySelector(".input-cantidad")) {
            tbody.innerHTML = `
                <tr id="filaVacia">
                    <td colspan="5" class="text-center text-muted py-5">
                        No se han agregado productos a la venta.
                    </td>
                </tr>
            `;
        }

        recalcularTotal();
    }

    function recalcularTotal() {
        let total = 0;

        document.querySelectorAll("#carritoBody tr").forEach(row => {
            const cantidad = row.querySelector(".input-cantidad");
            const precio = row.querySelector('input[name$="[precioventa]"]');
            if (cantidad && precio) {
                total += Number(cantidad.value) * Number(precio.value);
            }
        });

        document.getElementById("lblTotal").textContent = `S/ ${total.toFixed(2)}`;
        document.getElementById("inputTotal").value = total.toFixed(2);
        recalcularPagos();
    }

    const btnAgregarPago = document.getElementById("btnAgregarPago");

    if (btnAgregarPago) {
        btnAgregarPago.addEventListener("click", function () {
            const selectTp = document.getElementById("selectTipoPago");
            const inputMonto = document.getElementById("inputMontoPago");

            if (!selectTp.value) {
                Swal.fire({
                    icon: "warning",
                    title: "Método requerido",
                    text: "Seleccione un método de pago.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            const option = selectTp.options[selectTp.selectedIndex];
            const idtipopago = selectTp.value;
            const nombreMetodo = option.getAttribute("data-nombre");
            const montoPago = Number(inputMonto.value);

            if (!Number.isFinite(montoPago) || montoPago <= 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Monto inválido",
                    text: "Ingrese un monto mayor que cero.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            const idFilaPago = `pago-${idtipopago}`;

            if (document.getElementById(idFilaPago)) {
                Swal.fire({
                    icon: "info",
                    title: "Método ya agregado",
                    text: "Este método ya está agregado. Puede modificar su monto en la tabla.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            document.getElementById("filaSinPagos")?.remove();

            const tbody = document.getElementById("pagosBody");
            const tr = document.createElement("tr");

            tr.id = idFilaPago;
            tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark small nombre-metodo"></span>
                    <input type="hidden" name="pagos[${idtipopago}][idtipopago]" value="${idtipopago}">
                </td>
                <td class="text-end">
                    <input type="number" name="pagos[${idtipopago}][monto]" value="${montoPago.toFixed(2)}" min="0.01" step="0.01" class="form-control form-control-sm text-end input-monto-pago">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-eliminar-pago">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </td>
            `;

            tr.querySelector(".nombre-metodo").textContent = nombreMetodo;
            tbody.appendChild(tr);

            const inputMontoAgregado = tr.querySelector(".input-monto-pago");
            inputMontoAgregado.addEventListener("change", recalcularPagos);
            inputMontoAgregado.addEventListener("input", recalcularPagos);
            tr.querySelector(".btn-eliminar-pago").addEventListener("click", () => eliminarFilaPago(idFilaPago));

            selectTp.value = "";
            inputMonto.value = "";
            recalcularPagos();
        });
    }

    function recalcularPagos() {
        const totalVenta = Number(document.getElementById("inputTotal").value) || 0;
        let totalPagado = 0;

        document.querySelectorAll(".input-monto-pago").forEach(input => {
            totalPagado += Number(input.value) || 0;
        });

        document.getElementById("lblTotalPagado").textContent = `S/ ${totalPagado.toFixed(2)}`;

        const diferencia = totalPagado - totalVenta;
        const lblFaltanteVuelto = document.getElementById("lblFaltanteVuelto");

        if (diferencia >= 0) {
            lblFaltanteVuelto.className = "text-info fw-bold";
            lblFaltanteVuelto.textContent = `Vuelto: S/ ${diferencia.toFixed(2)}`;
        } else {
            lblFaltanteVuelto.className = "text-danger fw-bold";
            lblFaltanteVuelto.textContent = `Faltante: S/ ${Math.abs(diferencia).toFixed(2)}`;
        }
    }

    function eliminarFilaPago(idFila) {
        document.getElementById(idFila)?.remove();
        const tbody = document.getElementById("pagosBody");

        if (!tbody.querySelector(".input-monto-pago")) {
            tbody.innerHTML = `
                <tr id="filaSinPagos">
                    <td colspan="3" class="text-center text-muted py-2 small">
                        Sin pagos asignados.
                    </td>
                </tr>
            `;
        }

        recalcularPagos();
    }

    const formExpress = document.getElementById("formClienteExpress");

    if (formExpress) {
        formExpress.addEventListener("submit", async function (e) {
            e.preventDefault();

            const btn = document.getElementById("btnGuardarClienteExpress");
            const urlExpress = this.getAttribute("data-url-express");
            const formData = new FormData(this);
            const documento = String(formData.get("documento") || "").trim();
            const nombre = String(formData.get("nombre") || "").trim();

            if (!/^(?:\d{8}|\d{11})$/.test(documento)) {
                Swal.fire({
                    icon: "warning",
                    title: "Documento inválido",
                    text: "Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            if (nombre.length < 2) {
                Swal.fire({
                    icon: "warning",
                    title: "Nombre requerido",
                    text: "Ingrese el nombre o razón social del cliente.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Verificando...';

            try {
                const response = await fetch(urlExpress, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json"
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || "No se pudo completar la solicitud.");
                }

                if (data.status === "error") {
                    Swal.fire({
                        icon: "error",
                        title: "No se pudo registrar",
                        text: data.message || "Verifique los datos ingresados.",
                        confirmButtonColor: "#4f46e5"
                    });
                    return;
                }

                if (data.status !== "success" && data.status !== "exists") {
                    throw new Error("Respuesta inesperada del servidor.");
                }

                if (!data.idcliente) {
                    throw new Error("No se recibió el identificador del cliente.");
                }

                if (data.status === "exists") {
                    const confirmacion = await Swal.fire({
                        icon: "question",
                        title: "Cliente ya registrado",
                        html: `
                            <p>Este documento ya pertenece a un cliente.</p>
                            <p><strong>Cliente:</strong> <span id="nombreClienteEncontrado"></span></p>
                            <p><strong>Documento:</strong> <span id="documentoClienteEncontrado"></span></p>
                            <p>¿Deseas seleccionarlo para esta venta?</p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: "Sí, seleccionar cliente",
                        cancelButtonText: "Cancelar",
                        confirmButtonColor: "#ff254b",
                        cancelButtonColor: "#6c757d",
                        reverseButtons: true,
                        didOpen: () => {
                            document.getElementById("nombreClienteEncontrado").textContent = data.nombre || "Sin nombre";
                            document.getElementById("documentoClienteEncontrado").textContent = data.documento || documento;
                        }
                    });

                    if (!confirmacion.isConfirmed) return;
                }

                const selectCliente = document.getElementById("selectCliente");
                if (!selectCliente) throw new Error("No se encontró el selector de clientes.");

                const idCliente = String(data.idcliente);
                const nombreCliente = String(data.nombre || nombre);
                const documentoCliente = String(data.documento || documento);

                let option = Array.from(selectCliente.options).find(item => item.value === idCliente);

                if (!option) {
                    option = document.createElement("option");
                    option.value = idCliente;
                    selectCliente.appendChild(option);
                }

                option.textContent = `${nombreCliente} (${documentoCliente})`;
                selectCliente.value = idCliente;
                selectCliente.dispatchEvent(new Event("change", { bubbles: true }));

                const modalEl = document.getElementById("modalClienteExpress");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                formExpress.reset();
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: data.status === "exists" ? "info" : "success",
                    title: data.status === "exists" ? "Cliente existente seleccionado" : "Cliente registrado con éxito",
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });

            } catch (error) {
                console.error("Error en registro rápido:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error de registro",
                    text: "No se pudo completar la operación. Inténtelo nuevamente.",
                    confirmButtonColor: "#4f46e5"
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar y Seleccionar';
            }
        });
    }

    const formVenta = document.getElementById("formVenta");

    if (formVenta) {
        formVenta.addEventListener("submit", function (e) {
            const selectCliente = document.getElementById("selectCliente");

            if (!selectCliente.value || selectCliente.value === "false") {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Cliente requerido",
                    text: "Seleccione un cliente válido de la lista.",
                    confirmButtonColor: "#4f46e5"
                });
                selectCliente.focus();
                return;
            }

            const totalVenta = Number(document.getElementById("inputTotal").value) || 0;
            let totalPagado = 0;

            document.querySelectorAll(".input-monto-pago").forEach(input => {
                totalPagado += Number(input.value) || 0;
            });

            if (totalVenta <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Carrito vacío",
                    text: "Debe agregar al menos un producto a la venta.",
                    confirmButtonColor: "#4f46e5"
                });
                return;
            }

            if (tipoEntrega?.value === "DOMICILIO") {
                if (!direccionEntrega?.value.trim()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: "warning",
                        title: "Dirección requerida",
                        text: "Ingrese la dirección de entrega.",
                        confirmButtonColor: "#4f46e5"
                    });
                    return;
                }

                if (!telefonoEntrega?.value.trim()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: "warning",
                        title: "Teléfono requerido",
                        text: "Ingrese el teléfono de contacto.",
                        confirmButtonColor: "#4f46e5"
                    });
                    return;
                }
            }

            if (Math.round(totalPagado * 100) < Math.round(totalVenta * 100)) {
                e.preventDefault();
                Swal.fire({
                    icon: "error",
                    title: "Pago insuficiente",
                    text: `El monto pagado (S/ ${totalPagado.toFixed(2)}) es menor al total de la venta (S/ ${totalVenta.toFixed(2)}).`,
                    confirmButtonColor: "#4f46e5"
                });
            }
        });
    }
});