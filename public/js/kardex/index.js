document.addEventListener("DOMContentLoaded", function () {
  const view = document.getElementById("viewKardex");
  if (!view) return;

  const urlBuscar = view.dataset.urlBuscar;
  const inputBuscar = document.getElementById("inputBuscarKardex");
  const selectLocal = document.getElementById("selectLocal");
  const tbody = document.getElementById("tbodyKardex");
  if (!urlBuscar || !tbody) return;

  const baseUrl = typeof BASE_URL !== "undefined" ? BASE_URL : "/";
  let temporizador;
  let solicitudActual = null;
  let numeroSolicitud = 0;

  function numero(valor) {
    const n = Number(valor);
    return Number.isFinite(n) ? n : 0;
  }

  function cantidad(valor, unidad) {
    const n = numero(valor);
    const u = String(unidad || "").trim().toUpperCase();
    if (["UND", "PAR", "CJA", "KIT", "ROL", "JGO", "PQT"].includes(u)) {
      return n.toFixed(0);
    }
    if (u === "M") return n.toFixed(2);
    return n.toFixed(2).replace(/\.?0+$/, "");
  }

  function moneda(valor) {
    return numero(valor).toLocaleString("es-PE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function escapar(valor) {
    const div = document.createElement("div");
    div.textContent = String(valor ?? "");
    return div.innerHTML;
  }

  function mensaje(texto) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">${escapar(texto)}</td></tr>`;
  }

  function dibujar(registros) {
    if (!Array.isArray(registros) || registros.length === 0) {
      mensaje("No se encontraron registros de stock coincidentes.");
      return;
    }

    tbody.innerHTML = "";
    const fragmento = document.createDocumentFragment();

    registros.forEach((item, index) => {
      const stock = numero(item.stockactual);
      const minimo = numero(item.minima);
      const maximo = numero(item.maxima);
      const costo = numero(item.costopromedio);
      const unidad = item.unidad || "";
      const critico = stock <= minimo;
      const clase = critico ? "danger" : "success";
      const id = encodeURIComponent(String(item.idkardex));
      const urlHistorial = `${String(baseUrl).replace(/\/+$/, "")}/kardex/historial/${id}`;

      const fila = document.createElement("tr");
      fila.style.borderBottom = "1px solid #f0f0f0";
      fila.innerHTML = `
        <td class="px-4 text-muted fw-semibold">${index + 1}</td>
        <td><span class="fw-semibold text-dark">${escapar(item.producto)}</span></td>
        <td><span class="text-secondary">${escapar(item.local)}</span></td>
        <td class="text-center">
          <span class="badge bg-${clase}-subtle text-${clase} border border-${clase}-subtle px-3 py-2 fw-bold rounded-pill">
            ${cantidad(stock, unidad)}
          </span>
        </td>
        <td class="text-end fw-semibold text-dark" style="white-space:nowrap;">S/ ${moneda(costo)}</td>
        <td class="text-end fw-bold text-secondary" style="white-space:nowrap;">S/ ${moneda(stock * costo)}</td>
        <td class="text-center">
          <span class="badge bg-light text-secondary border px-2 py-1">
            Min: ${cantidad(minimo, unidad)} | Máx: ${cantidad(maximo, unidad)}
          </span>
        </td>
        <td class="text-center px-4 align-middle">
          <a href="${urlHistorial}" class="btn btn-sm btn-light text-primary border-0 shadow-sm" title="Ver Historial de Movimientos en Kardex">
            <i class="bi bi-eye fs-5"></i>
          </a>
        </td>`;
      fragmento.appendChild(fila);
    });
    tbody.appendChild(fragmento);
  }

  async function buscar() {
    if (solicitudActual) solicitudActual.abort();
    solicitudActual = new AbortController();
    const actual = ++numeroSolicitud;
    const params = new URLSearchParams({
      q: inputBuscar ? inputBuscar.value.trim() : "",
      idlocal: selectLocal ? selectLocal.value : "",
    });

    try {
      const separador = urlBuscar.includes("?") ? "&" : "?";
      const respuesta = await fetch(urlBuscar + separador + params.toString(), {
        headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        signal: solicitudActual.signal,
      });
      if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);
      const datos = await respuesta.json();
      if (actual === numeroSolicitud) dibujar(datos);
    } catch (error) {
      if (error.name === "AbortError" || actual !== numeroSolicitud) return;
      console.error("Error al filtrar Kardex:", error);
      mensaje("No se pudo consultar el inventario. Intenta nuevamente.");
    }
  }

  if (inputBuscar) {
    inputBuscar.addEventListener("input", function () {
      clearTimeout(temporizador);
      temporizador = setTimeout(buscar, 300);
    });
  }
  if (selectLocal) {
    selectLocal.addEventListener("change", function () {
      clearTimeout(temporizador);
      buscar();
    });
  }
});
