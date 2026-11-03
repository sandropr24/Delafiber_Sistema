document.addEventListener("DOMContentLoaded", function () {
  const btnAgregar = document.getElementById("btnAgregarItem");
  const selectProducto = document.getElementById("selectProducto");
  const inputCantidad = document.getElementById("inputCantidad");
  const inputPrecio = document.getElementById("inputPrecio");
  const tablaDetalle = document
    .getElementById("tablaDetalle")
    .querySelector("tbody");
  const filaVacia = document.getElementById("filaVacia");
  const panelSeries = document.getElementById("panelSeriesCompra");
  const camposSeries = document.getElementById("camposSeriesCompra");
  function controlaSerie() {
    return selectProducto.selectedOptions[0]?.dataset.serie === "1";
  }
  function actualizarSeries() {
    const activo = controlaSerie();
    panelSeries.classList.toggle("d-none", !activo);
    if (!activo) {
      camposSeries.innerHTML = "";
      return;
    }
    const cantidad = Number(inputCantidad.value);
    if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 100) {
      camposSeries.innerHTML =
        '<div class="small text-danger">Ingrese entre 1 y 100 unidades por línea.</div>';
      return;
    }
    const anteriores = [...camposSeries.querySelectorAll("input")].map(
      (i) => i.value,
    );
    camposSeries.innerHTML = "";
    for (let i = 0; i < cantidad; i++) {
      const div = document.createElement("div");
      div.className = "col-md-6";
      const label = document.createElement("label");
      label.className = "form-label small mb-1";
      label.textContent = `Serie unidad ${i + 1}`;
      const input = document.createElement("input");
      input.type = "text";
      input.className = "form-control form-control-sm serie-compra";
      input.maxLength = 60;
      input.placeholder = "Número de serie";
      input.value = anteriores[i] || "";
      div.append(label, input);
      camposSeries.appendChild(div);
    }
  }
  inputCantidad.addEventListener("input", actualizarSeries);
  const totalGeneral = document.getElementById("totalGeneral");

  const monedaInput = document.getElementById("moneda");
  const cambioInput = document.getElementById("tipocambio");
  const simboloMoneda = () => (monedaInput.value === "USD" ? "US$" : "S/");
  let simbolo = simboloMoneda();
  monedaInput.addEventListener("change", () => {
    if (tablaDetalle.querySelector('input[name="productos[]"]')) {
      monedaInput.value = monedaInput.dataset.anterior || "PEN";
      alert("Quite los productos antes de cambiar la moneda.");
      return;
    }
    monedaInput.dataset.anterior = monedaInput.value;
    cambioInput.readOnly = monedaInput.value === "PEN";
    cambioInput.value = monedaInput.value === "PEN" ? "1.0000" : "";
    simbolo = simboloMoneda();
    document.getElementById("simboloTotalCompra").textContent = simbolo;
  });
  monedaInput.dataset.anterior = "PEN";
  const etiquetaUnidad = document.getElementById("etiquetaUnidad");
  function unidadSeleccionada() {
    return (
      selectProducto.selectedOptions[0]?.dataset.unidad || "UND"
    ).toUpperCase();
  }
  function actualizarUnidad() {
    const unidad = unidadSeleccionada();
    const decimal = unidad === "M"; // Metro: fracciones; demás unidades actuales: enteros.
    inputCantidad.step = decimal ? "0.01" : "1";
    inputCantidad.min = decimal ? "0.01" : "1";
    etiquetaUnidad.textContent = selectProducto.value ? "(" + unidad + ")" : "";
    if (
      !decimal &&
      (!Number.isInteger(Number(inputCantidad.value)) ||
        Number(inputCantidad.value) < 1)
    )
      inputCantidad.value = "1";
  }
  actualizarUnidad();
  inputCantidad.addEventListener("change", function () {
    if (!selectProducto.value) return;
    const valor = Number(this.value);
    const decimal = unidadSeleccionada() === "M";
    if (
      !Number.isFinite(valor) ||
      valor <= 0 ||
      (!decimal && !Number.isInteger(valor)) ||
      (decimal && Math.round(valor * 100) !== valor * 100)
    ) {
      this.setCustomValidity(
        decimal
          ? "Ingrese hasta dos decimales."
          : "Ingrese una cantidad entera.",
      );
    } else {
      this.setCustomValidity("");
    }
    this.reportValidity();
  });
  selectProducto.addEventListener("change", function () {
    inputCantidad.setCustomValidity("");
    actualizarUnidad();
    const selectedOption = this.options[this.selectedIndex];
    inputPrecio.value = "";
    actualizarSeries();
  });

  document
    .getElementById("formCompra")
    .addEventListener("submit", function (e) {
      if (
        !tablaDetalle.querySelector('input[name="productos[]"]') ||
        (monedaInput.value === "USD" && !(parseFloat(cambioInput.value) > 0))
      ) {
        e.preventDefault();
        alert("Agregue productos y revise el tipo de cambio.");
      }
    });
  btnAgregar.addEventListener("click", function () {
    const idProd = selectProducto.value;
    const nombreProd =
      selectProducto.options[selectProducto.selectedIndex]?.getAttribute(
        "data-nombre",
      );
    const cantidad = parseFloat(inputCantidad.value);
    const precio = parseFloat(inputPrecio.value);

    if (!idProd) {
      alert("Por favor seleccione un producto.");
      return;
    }
    if (
      !Number.isFinite(cantidad) ||
      cantidad <= 0 ||
      (unidadSeleccionada() !== "M" && !Number.isInteger(cantidad)) ||
      (unidadSeleccionada() === "M" &&
        Math.abs(cantidad * 100 - Math.round(cantidad * 100)) > 1e-7)
    ) {
      alert("Ingrese una cantidad válida.");
      return;
    }
    if (isNaN(precio) || precio < 0) {
      alert("Ingrese un precio unitario válido.");
      return;
    }

    let series = [];
    if (controlaSerie()) {
      series = [...camposSeries.querySelectorAll("input.serie-compra")].map(
        (i) => i.value.trim(),
      );
      if (
        series.length !== cantidad ||
        series.some((s) => !s || s.length > 60) ||
        new Set(series.map((s) => s.toUpperCase())).size !== series.length
      ) {
        alert("Ingrese un número de serie único por cada unidad.");
        return;
      }
      const yaRegistradas = [
        ...tablaDetalle.querySelectorAll('input[name="series_items[]"]'),
      ]
        .flatMap((i) => JSON.parse(i.value || "[]"))
        .map((s) => s.toUpperCase());
      if (series.some((s) => yaRegistradas.includes(s.toUpperCase()))) {
        alert("Hay números de serie repetidos en la compra.");
        return;
      }
    }
    const elementoVacio = document.getElementById("filaVacia");
    if (elementoVacio) {
      elementoVacio.remove();
    }

    const unidad = unidadSeleccionada();
    const cantidadVisible =
      unidad === "M" ? cantidad.toFixed(2) : String(cantidad);
    const subtotal = (cantidad * precio).toFixed(2);

    const tr = document.createElement("tr");
    tr.innerHTML = `
                <td>
                    <input type="hidden" name="productos[]" value="${idProd}">
                    <span class="fw-medium text-dark"></span>
                    <input type="hidden" name="series_items[]">
                    <div class="small text-muted series-resumen"></div>
                </td>
                <td class="text-center">
                    <input type="hidden" name="cantidades[]" value="${cantidad}">
                    <span class="badge bg-secondary px-2 py-1 fs-6">${cantidadVisible}</span>
                </td>
                <td class="text-end">
                    <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
                    ${simbolo} ${precio.toFixed(2)}
                </td>
                <td class="text-end fw-bold text-success class-subtotal">
                    ${simbolo} ${subtotal}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btnEliminar" title="Eliminar ítem">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

    tr.querySelector("span.fw-medium").textContent = nombreProd || "";
    tr.querySelector('input[name="series_items[]"]').value =
      JSON.stringify(series);
    tr.querySelector(".series-resumen").textContent = series.length
      ? `Series: ${series.join(", ")}`
      : "";
    tablaDetalle.appendChild(tr);

    selectProducto.value = "";
    inputCantidad.value = "1";
    actualizarUnidad();
    inputPrecio.value = "";
    actualizarSeries();

    calcularTotal();
  });

  tablaDetalle.addEventListener("click", function (e) {
    if (e.target.closest(".btnEliminar")) {
      e.target.closest("tr").remove();

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
    tablaDetalle.querySelectorAll("tr").forEach((fila) => {
      const cantidadInput = fila.querySelector('input[name="cantidades[]"]');
      const precioInput = fila.querySelector('input[name="precios[]"]');
      if (!cantidadInput || !precioInput) return;
      const cantidad = Number(cantidadInput.value);
      const precio = Number(precioInput.value);
      if (Number.isFinite(cantidad) && Number.isFinite(precio)) {
        total += cantidad * precio;
      }
    });
    totalGeneral.value = total.toFixed(2);
  }
});
