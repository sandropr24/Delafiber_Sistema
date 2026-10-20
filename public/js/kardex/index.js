document.addEventListener("DOMContentLoaded", function () {
  const viewContainer = document.getElementById("viewKardex");
  if (!viewContainer) return;

  const urlBuscar = viewContainer.getAttribute("data-url-buscar");
  const inputBuscar = document.getElementById("inputBuscarKardex");
  const selectLocal = document.getElementById("selectLocal");
  const tbodyKardex = document.getElementById("tbodyKardex");
  const baseUrl = typeof BASE_URL !== "undefined" ? BASE_URL : "/";

  let timeoutId = null;

  function ejecutarBusqueda() {
    let q = inputBuscar ? inputBuscar.value.trim() : "";
    let idlocal = selectLocal ? selectLocal.value : "";

    let urlParams = new URLSearchParams({
      q: q,
      idlocal: idlocal,
    });

    fetch(urlBuscar + "?" + urlParams.toString(), {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => {
        if (!res.ok) {
          throw new Error("HTTP error " + res.status);
        }
        return res.json();
      })
      .then((data) => {
        tbodyKardex.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          tbodyKardex.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="my-3">
                                <i class="fas fa-boxes fa-3x text-light mb-3"></i>
                                <p class="mb-0">No se encontraron registros de stock coincidentes.</p>
                            </div>
                        </td>
                    </tr>
                `;
          return;
        }

        data.forEach((item, index) => {
          let stockActual = parseInt(item.stockactual || 0);
          let stockMinima = parseInt(item.minima || 0);
          let esCritico = stockActual <= stockMinima;

          let stockBadgeClass = esCritico
            ? "bg-danger-subtle text-danger border-danger-subtle"
            : "bg-success-subtle text-success border-success-subtle";

          let tr = document.createElement("tr");
          tr.style.borderBottom = "1px solid #f0f0f0";

          tr.innerHTML = `
                    <td class="px-4 text-muted fw-semibold">${index + 1}</td>
                    <td><span class="fw-semibold text-dark">${item.producto || ""}</span></td>
                    <td><span class="text-secondary">${item.local || ""}</span></td>
                    <td class="text-center">
                        <span class="badge ${stockBadgeClass} border px-3 py-2 fw-bold rounded-pill">
                            ${stockActual}
                        </span>
                    </td>
                    <td class="text-end fw-semibold text-dark">S/ ${parseFloat(item.costopromedio || 0).toFixed(2)}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-secondary border px-2 py-1">
                            Min: ${stockMinima} | Máx: ${item.maxima || 0}
                        </span>
                    </td>
                    <td class="text-center px-4 align-middle">
                        <a href="${baseUrl}kardex/historial/${item.idkardex}" class="text-secondary link-primary fs-5" title="Ver Historial de Movimientos en Kardex">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                `;
          tbodyKardex.appendChild(tr);
        });
      })
      .catch((err) => console.error("Error al filtrar kardex:", err));
  }

  if (inputBuscar) {
    inputBuscar.addEventListener("input", function () {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(ejecutarBusqueda, 300);
    });
  }

  if (selectLocal) {
    selectLocal.addEventListener("change", ejecutarBusqueda);
  }
});
