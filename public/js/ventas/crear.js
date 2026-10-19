document.addEventListener("DOMContentLoaded", function () {
  const configEl = document.getElementById("ventaConfig");
  const stockPorLocal = JSON.parse(
    configEl ? configEl.getAttribute("data-stock") : "{}",
  );

  const selectProducto = document.getElementById("selectProducto");
  if (selectProducto) {
    selectProducto.addEventListener("change", function () {
      const option = this.options[this.selectedIndex];
      const precio = option.getAttribute("data-precio") || 0;
      document.getElementById("inputPrecio").value =
        parseFloat(precio).toFixed(2);
    });
  }

  const btnAgregarItem = document.getElementById("btnAgregarItem");
  if (btnAgregarItem) {
    btnAgregarItem.addEventListener("click", function () {
      const selectLocal = document.getElementById("selectLocal");
      const option = selectProducto.options[selectProducto.selectedIndex];
      const cantInput = document.getElementById("inputCantidad");
      const precioInput = document.getElementById("inputPrecio");

      if (!selectLocal.value) {
        Swal.fire({
          icon: "warning",
          title: "Local requerido",
          text: "Por favor, seleccione un Local / Sucursal antes de agregar productos.",
          confirmButtonColor: "#4f46e5",
        });
        selectLocal.focus();
        return;
      }

      if (!selectProducto.value) {
        Swal.fire({
          icon: "warning",
          title: "Producto requerido",
          text: "Por favor seleccione un producto.",
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const idlocal = selectLocal.value;
      const idproducto = selectProducto.value;
      const nombre = option.getAttribute("data-nombre");
      const cantidadAgregar = parseInt(cantInput.value) || 1;
      const precio =
        parseFloat(precioInput.value) ||
        parseFloat(option.getAttribute("data-precio")) ||
        0;

      // Búsqueda flexible de stock para evitar problemas de tipos (string/int)
      let stockDisponible = 0;
      if (stockPorLocal[idlocal]) {
        if (stockPorLocal[idlocal][idproducto] !== undefined) {
          stockDisponible = Number(stockPorLocal[idlocal][idproducto]);
        } else if (stockPorLocal[idlocal][String(idproducto)] !== undefined) {
          stockDisponible = Number(stockPorLocal[idlocal][String(idproducto)]);
        } else if (stockPorLocal[idlocal][Number(idproducto)] !== undefined) {
          stockDisponible = Number(stockPorLocal[idlocal][Number(idproducto)]);
        }
      }

      if (cantidadAgregar > stockDisponible) {
        Swal.fire({
          icon: "error",
          title: "Stock insuficiente",
          text: `El producto "${nombre}" solo cuenta con ${stockDisponible} unidades disponibles en este local.`,
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const filaExistente = document.getElementById(`row-${idproducto}`);
      let cantidadActualEnTabla = 0;
      if (filaExistente) {
        cantidadActualEnTabla =
          parseInt(filaExistente.querySelector(".input-cantidad").value) || 0;
      }

      const totalAcumulado = cantidadActualEnTabla + cantidadAgregar;
      if (totalAcumulado > stockDisponible) {
        Swal.fire({
          icon: "warning",
          title: "Límite de stock alcanzado",
          text: `El stock máximo disponible en este local es ${stockDisponible} y ya tienes ${cantidadActualEnTabla} en la lista.`,
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const filaVacia = document.getElementById("filaVacia");
      if (filaVacia) {
        filaVacia.remove();
      }

      const tbody = document.getElementById("carritoBody");

      if (filaExistente) {
        const inputCant = filaExistente.querySelector(".input-cantidad");
        inputCant.value = totalAcumulado;
        actualizarSubtotal(idproducto, precio);
        cantInput.value = 1;
        selectProducto.value = "";
        precioInput.value = "";
        return;
      }

      const tr = document.createElement("tr");
      tr.id = `row-${idproducto}`;
      tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark">${nombre}</span>
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
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila" data-id="${idproducto}">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </td>
            `;

      tbody.appendChild(tr);

      const inputCantidadAgregado = tr.querySelector(".input-cantidad");
      inputCantidadAgregado.addEventListener("change", () =>
        actualizarSubtotal(idproducto, precio),
      );
      inputCantidadAgregado.addEventListener("keyup", () =>
        actualizarSubtotal(idproducto, precio),
      );

      tr.querySelector(".btn-eliminar-fila").addEventListener("click", () =>
        eliminarFila(idproducto),
      );

      selectProducto.value = "";
      cantInput.value = 1;
      precioInput.value = "";
      recalcularTotal();
    });
  }

  function actualizarSubtotal(idproducto, precio) {
    const row = document.getElementById(`row-${idproducto}`);
    if (!row) return;

    const inputCant = row.querySelector(".input-cantidad");
    const selectLocal = document.getElementById("selectLocal");
    const idlocal = selectLocal.value;

    let stockDisponible = 999999;
    if (stockPorLocal[idlocal]) {
      if (stockPorLocal[idlocal][idproducto] !== undefined) {
        stockDisponible = Number(stockPorLocal[idlocal][idproducto]);
      } else if (stockPorLocal[idlocal][String(idproducto)] !== undefined) {
        stockDisponible = Number(stockPorLocal[idlocal][String(idproducto)]);
      } else if (stockPorLocal[idlocal][Number(idproducto)] !== undefined) {
        stockDisponible = Number(stockPorLocal[idlocal][Number(idproducto)]);
      }
    }

    let cantidad = parseInt(inputCant.value) || 1;

    if (cantidad > stockDisponible) {
      Swal.fire({
        icon: "warning",
        title: "Stock excedido",
        text: `La cantidad ingresada supera el stock disponible (${stockDisponible}).`,
        confirmButtonColor: "#4f46e5",
      });
      inputCant.value = stockDisponible;
      cantidad = stockDisponible;
    }

    if (cantidad < 1) {
      inputCant.value = 1;
      cantidad = 1;
    }

    const subtotal = cantidad * precio;
    row.querySelector(".lbl-subtotal").textContent =
      `S/ ${subtotal.toFixed(2)}`;
    recalcularTotal();
  }

  function eliminarFila(idproducto) {
    const row = document.getElementById(`row-${idproducto}`);
    if (row) {
      row.remove();
    }
    recalcularTotal();

    const tbody = document.getElementById("carritoBody");
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
    document.querySelectorAll(".lbl-subtotal").forEach((el) => {
      const val = parseFloat(el.textContent.replace("S/", "").trim()) || 0;
      total += val;
    });

    document.getElementById("lblTotal").textContent = `S/ ${total.toFixed(2)}`;
    document.getElementById("inputTotal").value = total.toFixed(2);
    recalcularPagos();
  }

  const btnAgregarPago = document.getElementById("btnAgregarPago");
  if (btnAgregarPago) {
    btnAgregarPago.addEventListener("click", function () {
      const selectTp = document.getElementById("selectTipoPago");
      const option = selectTp.options[selectTp.selectedIndex];
      const inputMonto = document.getElementById("inputMontoPago");

      if (!selectTp.value) {
        Swal.fire({
          icon: "warning",
          title: "Método requerido",
          text: "Seleccione un método de pago.",
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const idtipopago = selectTp.value;
      const nombreMetodo = option.getAttribute("data-nombre");
      const montoPago = parseFloat(inputMonto.value) || 0;

      if (montoPago <= 0) {
        Swal.fire({
          icon: "warning",
          title: "Monto inválido",
          text: "Ingrese un monto válido mayor a 0.",
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const filaSinPagos = document.getElementById("filaSinPagos");
      if (filaSinPagos) {
        filaSinPagos.remove();
      }

      const tbody = document.getElementById("pagosBody");
      const idFilaPago = `pago-${idtipopago}`;
      if (document.getElementById(idFilaPago)) {
        Swal.fire({
          icon: "info",
          title: "Método ya agregado",
          text: "Este método de pago ya fue agregado. Modifique su monto directamente en la tabla si lo requiere.",
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      const tr = document.createElement("tr");
      tr.id = idFilaPago;
      tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark small">${nombreMetodo}</span>
                    <input type="hidden" name="pagos[${idtipopago}][idtipopago]" value="${idtipopago}">
                </td>
                <td class="text-end">
                    <input type="number" name="pagos[${idtipopago}][monto]" value="${montoPago.toFixed(2)}" step="0.01" class="form-control form-control-sm text-end input-monto-pago">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-eliminar-pago">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </td>
            `;

      tbody.appendChild(tr);

      const inputMontoAgregado = tr.querySelector(".input-monto-pago");
      inputMontoAgregado.addEventListener("change", recalcularPagos);
      inputMontoAgregado.addEventListener("keyup", recalcularPagos);

      tr.querySelector(".btn-eliminar-pago").addEventListener("click", () =>
        eliminarFilaPago(idFilaPago),
      );

      selectTp.value = "";
      inputMonto.value = "";
      recalcularPagos();
    });
  }

  function recalcularPagos() {
    let totalVenta =
      parseFloat(document.getElementById("inputTotal").value) || 0;
    let totalPagado = 0;

    document.querySelectorAll(".input-monto-pago").forEach((input) => {
      totalPagado += parseFloat(input.value) || 0;
    });

    document.getElementById("lblTotalPagado").textContent =
      `S/ ${totalPagado.toFixed(2)}`;

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
    const row = document.getElementById(idFila);
    if (row) {
      row.remove();
    }
    recalcularPagos();

    const tbody = document.getElementById("pagosBody");
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

  const formExpress = document.getElementById("formClienteExpress");
  if (formExpress) {
    formExpress.addEventListener("submit", function (e) {
      e.preventDefault();

      const urlExpress = this.getAttribute("data-url-express");
      let formData = new FormData(this);
      let btn = document.getElementById("btnGuardarClienteExpress");
      btn.disabled = true;
      btn.innerHTML =
        '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

      fetch(urlExpress, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            let select = document.getElementById("selectCliente");

            let option = document.createElement("option");
            option.value = data.idcliente;
            option.text = data.nombre + " (" + data.documento + ")";
            option.selected = true;

            select.appendChild(option);
            select.value = data.idcliente;

            let modalEl = document.getElementById("modalClienteExpress");
            let modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            formExpress.reset();

            Swal.fire({
              toast: true,
              position: "top-end",
              icon: "success",
              title: "Cliente registrado con éxito",
              showConfirmButton: false,
              timer: 2500,
              timerProgressBar: true,
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: data.message,
              confirmButtonColor: "#4f46e5",
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error de servidor",
            text: "Ocurrió un error al registrar el cliente.",
            confirmButtonColor: "#4f46e5",
          });
        })
        .finally(() => {
          btn.disabled = false;
          btn.innerHTML =
            '<i class="fas fa-save me-1"></i> Guardar y Seleccionar';
        });
    });
  }

  const formVenta = document.getElementById("formVenta");
  if (formVenta) {
    formVenta.addEventListener("submit", function (e) {
      const selectCliente = document.getElementById("selectCliente");

      if (
        !selectCliente.value ||
        selectCliente.value === "false" ||
        selectCliente.value === false
      ) {
        e.preventDefault();
        Swal.fire({
          icon: "warning",
          title: "Cliente requerido",
          text: "Por favor, seleccione un cliente válido de la lista.",
          confirmButtonColor: "#4f46e5",
        });
        selectCliente.focus();
        return;
      }

      let totalVenta =
        parseFloat(document.getElementById("inputTotal").value) || 0;
      let totalPagado = 0;

      document.querySelectorAll(".input-monto-pago").forEach((input) => {
        totalPagado += parseFloat(input.value) || 0;
      });

      if (totalVenta <= 0) {
        e.preventDefault();
        Swal.fire({
          icon: "warning",
          title: "Carrito vacío",
          text: "Debe agregar al menos un producto a la venta.",
          confirmButtonColor: "#4f46e5",
        });
        return;
      }

      if (totalPagado < totalVenta) {
        e.preventDefault();
        Swal.fire({
          icon: "error",
          title: "Pago insuficiente",
          text: `El monto total pagado (S/ ${totalPagado.toFixed(2)}) es menor al total de la venta (S/ ${totalVenta.toFixed(2)}). Falta cubrir dinero.`,
          confirmButtonColor: "#4f46e5",
        });
        return;
      }
    });
  }
});
