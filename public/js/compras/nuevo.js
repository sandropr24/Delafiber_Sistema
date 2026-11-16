document.addEventListener("DOMContentLoaded", function () {
  function mostrarAviso(titulo, mensaje, campo, icono = "warning") {
    document.getElementById("avisoCompra")?.remove();
    const destino = campo || document.activeElement;

    if (typeof window.Swal?.fire === "function") {
      return window.Swal.fire({
        icon: icono,
        iconColor: "#8b67a7",
        title: titulo,
        text: mensaje,
        confirmButtonText: "Entendido",
        buttonsStyling: false,
        customClass: {
          popup: "compra-alerta",
          confirmButton: "compra-alerta-confirmar",
        },
        returnFocus: false,
        didClose: () => destino?.focus(),
      });
    }

    // Si el CDN no está disponible, conserva el aviso dentro de la pantalla.
    const aviso = document.createElement("div");
    aviso.id = "avisoCompra";
    aviso.className = "alert alert-warning compra-alerta-inline";
    aviso.setAttribute("role", "alert");
    aviso.tabIndex = -1;

    const contenido = document.createElement("div");
    const encabezado = document.createElement("strong");
    encabezado.textContent = titulo;
    const texto = document.createElement("p");
    texto.textContent = mensaje;
    contenido.append(encabezado, texto);

    const cerrar = document.createElement("button");
    cerrar.type = "button";
    cerrar.className = "btn-close";
    cerrar.setAttribute("aria-label", "Cerrar aviso");
    cerrar.addEventListener("click", () => {
      aviso.remove();
      destino?.focus();
    });
    aviso.append(contenido, cerrar);
    document.querySelector(".compra-page-heading").after(aviso);
    aviso.focus();
  }

  const btnAgregar = document.getElementById("btnAgregarItem");
  const selectProducto = document.getElementById("selectProducto");
  const inputCantidad = document.getElementById("inputCantidad");
  const inputPrecio = document.getElementById("inputPrecio");
  const tablaDetalle = document
    .getElementById("tablaDetalle")
    .querySelector("tbody");
  const filaVacia = document.getElementById("filaVacia");
  const plantillaVacia = filaVacia.cloneNode(true);
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
      input.id = `serieCompra-${i}`;
      label.htmlFor = input.id;
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
      mostrarAviso(
        "La compra ya tiene productos",
        "Quita los productos del detalle antes de cambiar la moneda.",
        monedaInput,
        "info",
      );
      return;
    }
    monedaInput.dataset.anterior = monedaInput.value;
    cambioInput.readOnly = monedaInput.value === "PEN";
    cambioInput.value = monedaInput.value === "PEN" ? "1.0000" : "";
    simbolo = simboloMoneda();
    document.getElementById("simboloTotalCompra").textContent = simbolo;
    document.getElementById("simboloPrecioCompra").textContent = simbolo;
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
        mostrarAviso(
          "Revisa la compra",
          "Agrega al menos un producto y, si compras en dólares, indica un tipo de cambio mayor que cero.",
          tablaDetalle.querySelector('input[name="productos[]"]')
            ? cambioInput
            : selectProducto,
        );
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
      mostrarAviso(
        "Selecciona un producto",
        "Elige el producto que deseas agregar al detalle de la compra.",
        selectProducto,
      );
      return;
    }
    if (
      !Number.isFinite(cantidad) ||
      cantidad <= 0 ||
      (unidadSeleccionada() !== "M" && !Number.isInteger(cantidad)) ||
      (unidadSeleccionada() === "M" &&
        Math.abs(cantidad * 100 - Math.round(cantidad * 100)) > 1e-7)
    ) {
      mostrarAviso(
        "Cantidad inválida",
        unidadSeleccionada() === "M"
          ? "Ingresa una cantidad mayor que cero, con hasta dos decimales."
          : "Este producto requiere una cantidad entera mayor que cero.",
        inputCantidad,
      );
      return;
    }
    if (isNaN(precio) || precio < 0) {
      mostrarAviso(
        "Costo unitario inválido",
        "Ingresa un costo unitario mayor o igual a cero para continuar.",
        inputPrecio,
      );
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
        mostrarAviso(
          "Revisa los números de serie",
          "Ingresa una serie diferente por cada unidad, sin campos vacíos y con un máximo de 60 caracteres.",
          camposSeries.querySelector("input.serie-compra") || inputCantidad,
        );
        return;
      }
      const yaRegistradas = [
        ...tablaDetalle.querySelectorAll('input[name="series_items[]"]'),
      ]
        .flatMap((i) => JSON.parse(i.value || "[]"))
        .map((s) => s.toUpperCase());
      if (series.some((s) => yaRegistradas.includes(s.toUpperCase()))) {
        mostrarAviso(
          "Número de serie repetido",
          "Una de las series ya está en el detalle de esta compra. Revísala antes de agregar el producto.",
          camposSeries.querySelector("input.serie-compra") || inputCantidad,
        );
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
                    <span class="compra-cantidad">${cantidadVisible}</span>
                </td>
                <td class="text-end">
                    <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
                    ${simbolo} ${precio.toFixed(2)}
                </td>
                <td class="text-end class-subtotal">
                    ${simbolo} ${subtotal}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btnEliminar" title="Quitar producto">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </td>
            `;

    tr.querySelector("span.fw-medium").textContent = nombreProd || "";
    tr.querySelector(".btnEliminar").setAttribute(
      "aria-label",
      `Quitar ${nombreProd || "producto"} de la compra`,
    );
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
    document.getElementById("anuncioDetalleCompra").textContent =
      `${nombreProd} agregado a la compra.`;
  });

  tablaDetalle.addEventListener("click", function (e) {
    if (e.target.closest(".btnEliminar")) {
      e.target.closest("tr").remove();

      if (tablaDetalle.children.length === 0) {
        tablaDetalle.appendChild(plantillaVacia.cloneNode(true));
      }
      calcularTotal();
      document.getElementById("anuncioDetalleCompra").textContent =
        "Producto retirado de la compra.";
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
    actualizarResumen();
  }

  function actualizarResumen() {
    const proveedor = document.getElementById("idproveedor");
    const almacen = document.getElementById("idlocal");
    const resumenProveedor = document.getElementById("resumenProveedor");
    resumenProveedor.textContent = proveedor.value
      ? proveedor.selectedOptions[0].textContent.trim()
      : "Sin seleccionar";
    resumenProveedor.classList.toggle("compra-sin-dato", !proveedor.value);
    document.getElementById("resumenAlmacen").textContent =
      almacen.selectedOptions[0]?.textContent.trim() || "Sin seleccionar";
    document.getElementById("resumenMoneda").textContent =
      monedaInput.value === "USD" ? "Dólares · USD" : "Soles · PEN";

    const productos = [
      ...tablaDetalle.querySelectorAll('input[name="productos[]"]'),
    ];
    const articulos = new Set(productos.map((input) => input.value)).size;
    document.getElementById("contadorItemsCompra").textContent =
      `${articulos} ${articulos === 1 ? "artículo" : "artículos"}`;
    document.getElementById("resumenLineas").textContent = productos.length;
    document.getElementById("resumenSeries").textContent = [
      ...tablaDetalle.querySelectorAll('input[name="series_items[]"]'),
    ].reduce((total, input) => total + JSON.parse(input.value || "[]").length, 0);
  }

  document.getElementById("formCompra").addEventListener("change", actualizarResumen);
  actualizarResumen();
});
