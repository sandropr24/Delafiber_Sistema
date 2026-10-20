function previsualizarImagen(event) {
  const input = event.target;
  const img = document.getElementById("previewImg");
  const icon = document.getElementById("previewIcon");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      img.src = e.target.result;
      img.style.display = "block";
      icon.style.display = "none";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function limpiarFormulario() {
  document.getElementById("modalTitulo").textContent = "Nuevo Producto";
  document.getElementById("idproducto").value = "";
  document.getElementById("nombre").value = "";
  document.getElementById("descripcion").value = "";
  document.getElementById("idcategoria").value = "";
  document.getElementById("idmarca").value = "";
  document.getElementById("modelo").value = "";
  document.getElementById("codigobarras").value = "";
  document.getElementById("precioventa").value = "";
  document.getElementById("imagen").value = "";
  document.getElementById("divEstado").style.display = "none";
  document.getElementById("estado").value = "1";

  document.getElementById("previewImg").src = "";
  document.getElementById("previewImg").style.display = "none";
  document.getElementById("previewIcon").style.display = "block";
}

function editarProducto(p) {
  document.getElementById("modalTitulo").textContent = "Editar Producto";
  document.getElementById("idproducto").value = p.idproducto;
  document.getElementById("nombre").value = p.nombre || "";
  document.getElementById("descripcion").value = p.descripcion || "";
  document.getElementById("idcategoria").value = p.idcategoria;
  document.getElementById("idmarca").value = p.idmarca;
  document.getElementById("modelo").value = p.modelo || "";
  document.getElementById("codigobarras").value = p.codigobarras || "";
  document.getElementById("precioventa").value = p.precioventa;
  document.getElementById("imagen").value = "";
  document.getElementById("divEstado").style.display = "block";
  document.getElementById("estado").value = p.estado;

  const img = document.getElementById("previewImg");
  const icon = document.getElementById("previewIcon");
  if (p.imagen) {
    img.src = BASE_URL + "uploads/productos/" + p.imagen;
    img.style.display = "block";
    icon.style.display = "none";
  } else {
    img.src = "";
    img.style.display = "none";
    icon.style.display = "block";
  }

  new bootstrap.Modal(document.getElementById("modalProducto")).show();
}

document.addEventListener("DOMContentLoaded", function () {
  const viewContainer = document.getElementById("viewProductos");
  if (!viewContainer) return;

  const urlBuscar = viewContainer.getAttribute("data-url-buscar");
  const inputBuscar = document.getElementById("inputBuscarTablaProductos");
  const tbodyProductos = document.getElementById("tbodyProductos");

  if (inputBuscar) {
    inputBuscar.addEventListener("input", function () {
      let q = this.value.trim();

      fetch(urlBuscar + "?q=" + encodeURIComponent(q), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((res) => res.json())
        .then((data) => {
          tbodyProductos.innerHTML = "";

          if (data.length === 0) {
            tbodyProductos.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No se encontraron productos coincidentes.</td>
                        </tr>
                    `;
            return;
          }

          data.forEach((p) => {
            let imagenHtml = "";
            if (p.imagen) {
              imagenHtml = `<img src="${BASE_URL}uploads/productos/${p.imagen}" alt="${p.nombre}" class="rounded border object-fit-cover shadow-sm" style="width: 44px; height: 44px;">`;
            } else {
              imagenHtml = `<div class="bg-light rounded border d-flex align-items-center justify-content-center text-secondary" style="width: 44px; height: 44px;"><i class="bi bi-image fs-5"></i></div>`;
            }

            let estadoBadge =
              parseInt(p.estado) === 1
                ? `<a href="${BASE_URL}productos/cambiarestado/${p.idproducto}" class="badge bg-success-subtle text-success text-decoration-none" title="Clic para cambiar estado">Activo</a>`
                : `<a href="${BASE_URL}productos/cambiarestado/${p.idproducto}" class="badge bg-danger-subtle text-danger text-decoration-none" title="Clic para cambiar estado">Inactivo</a>`;

            let tr = document.createElement("tr");
            tr.innerHTML = `
                        <td class="ps-4">${imagenHtml}</td>
                        <td class="fw-semibold text-dark">${p.nombre}</td>
                        <td class="text-muted small">${p.descripcion || "-"}</td>
                        <td><span class="badge bg-light text-dark border">${p.categoria || ""}</span></td>
                        <td>${p.marca || ""}</td>
                        <td class="text-muted small">${p.modelo || "-"}</td>
                        <td class="text-muted small font-monospace">${p.codigobarras || "-"}</td>
                        <td class="text-end fw-bold text-dark">S/ ${parseFloat(p.precioventa).toFixed(2)}</td>
                        <td class="text-center">${estadoBadge}</td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-light text-primary border-0 me-1" onclick='editarProducto(${JSON.stringify(p)})' title="Editar Producto">
                                <i class="bi bi-pencil-square fs-5"></i>
                            </button>
                        </td>
                    `;
            tbodyProductos.appendChild(tr);
          });
        })
        .catch((err) => console.error("Error al buscar productos:", err));
    });
  }
});
