document.addEventListener("DOMContentLoaded", function () {
  let activeTab = localStorage.getItem("activeCatalogoTab");
  if (activeTab) {
    let tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
    if (tabTrigger) {
      new bootstrap.Tab(tabTrigger).show();
    }
  }

  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (element) {
    element.addEventListener("show.bs.tab", function (event) {
      localStorage.setItem("activeCatalogoTab", event.target.getAttribute("data-bs-target"));
    });
  });

  cargarCategorias(1);
  cargarMarcas(1);
});

function cargarCategorias(pagina) {
  cargarTabla({
    url: "buscarCategorias",
    panel: "#categorias-pane",
    paginacionId: "paginacionCategorias",
    infoId: "infoPaginacionCat",
    etiqueta: "categorías principales",
    columnas: 6,
    pagina,
    recargar: cargarCategorias,
  });
}

function cargarMarcas(pagina) {
  cargarTabla({
    url: "buscarMarcas",
    panel: "#marcas-pane",
    paginacionId: "paginacionMarcas",
    infoId: "infoPaginacionMarc",
    etiqueta: "marcas",
    columnas: 5,
    pagina,
    recargar: cargarMarcas,
  });
}

function cargarTabla(cfg) {
  const tbody = document.querySelector(`${cfg.panel} tbody`);
  const info = document.getElementById(cfg.infoId);

  fetch(`${BASE_URL}/catalogos/${cfg.url}?page=${cfg.pagina}`, {
    headers: { "X-Requested-With": "XMLHttpRequest" },
    credentials: "same-origin",
  })
    .then((response) => {
      if (response.redirected && response.url.includes("/login")) {
        window.location.href = response.url;
        throw new Error("Sesión expirada");
      }
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    })
    .then((data) => {
      tbody.innerHTML = data.html || "";
      renderPaginacion(
        cfg.paginacionId,
        data.totalPaginas,
        data.pagina || cfg.pagina,
        cfg.recargar,
        cfg.infoId,
        `${data.totalRegistros ?? 0} ${cfg.etiqueta}`
      );
    })
    .catch((error) => {
      console.error(`Error cargando ${cfg.etiqueta}:`, error);
      mostrarErrorTabla(tbody, cfg, error.message);
      if (info) info.textContent = "Error al cargar";
    });
}

function mostrarErrorTabla(tbody, cfg, mensaje) {
  tbody.innerHTML = "";
  const tr = document.createElement("tr");
  const td = document.createElement("td");
  td.colSpan = cfg.columnas;
  td.className = "text-center text-danger py-4";
  td.textContent = `No se pudo cargar la lista (${mensaje}). `;
  
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "btn btn-link btn-sm p-0 align-baseline";
  boton.textContent = "Reintentar";
  boton.addEventListener("click", () => cfg.recargar(cfg.pagina));
  
  td.appendChild(boton);
  tr.appendChild(td);
  tbody.appendChild(tr);
}

function renderPaginacion(elementId, totalPaginas, paginaActual, recargar, infoId, resumen) {
  const ul = document.getElementById(elementId);
  if (!ul) return;
  ul.innerHTML = "";

  totalPaginas = Number(totalPaginas) || 1;
  paginaActual = Number(paginaActual) || 1;

  const info = document.getElementById(infoId);
  if (info) {
    info.textContent = `Página ${paginaActual} de ${totalPaginas} · ${resumen}`;
  }

  const agregar = (texto, destino, { activo = false, deshabilitado = false } = {}) => {
    const li = document.createElement("li");
    li.className = `page-item${activo ? " active" : ""}${deshabilitado ? " disabled" : ""}`;
    
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "page-link";
    boton.textContent = texto;
    boton.disabled = deshabilitado;
    
    if (!deshabilitado && !activo) {
      boton.addEventListener("click", () => recargar(destino));
    }
    
    li.appendChild(boton);
    ul.appendChild(li);
  };

  agregar("Anterior", paginaActual - 1, { deshabilitado: paginaActual <= 1 });
  
  paginasVisibles(totalPaginas, paginaActual).forEach((p) => {
    if (p === "…") {
      agregar("…", 0, { deshabilitado: true });
    } else {
      agregar(String(p), p, { activo: p === paginaActual });
    }
  });
  
  agregar("Siguiente", paginaActual + 1, { deshabilitado: paginaActual >= totalPaginas });
}

function paginasVisibles(total, actual) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const orden = [...new Set([1, total, actual - 1, actual, actual + 1])]
    .filter((p) => p >= 1 && p <= total)
    .sort((a, b) => a - b);

  const resultado = [];
  orden.forEach((p, i) => {
    if (i > 0 && p - orden[i - 1] > 1) resultado.push("…");
    resultado.push(p);
  });
  return resultado;
}

function resetSelectPadre() {
  const sel = document.getElementById("edit_idcategoriapadre");
  if (!sel) return;
  sel.disabled = false;
  sel.value = "";
  [...sel.options].forEach((o) => (o.disabled = false));
  
  const ayuda = document.getElementById("ayudaPadre");
  if (ayuda) ayuda.textContent = "";
  
  return sel;
}

function limpiarModalCategoria() {
  document.getElementById("modalCategoriaTitulo").textContent = "Nueva Categoría";
  document.getElementById("edit_idcategoria").value = "";
  document.getElementById("edit_nombre_categoria").value = "";
  resetSelectPadre();
}

function nuevaSubcategoria(idPadre) {
  limpiarModalCategoria();
  document.getElementById("modalCategoriaTitulo").textContent = "Nueva Subcategoría";
  document.getElementById("edit_idcategoriapadre").value = idPadre;
  new bootstrap.Modal(document.getElementById("modalNuevaCategoria")).show();
}

function editarCategoria(cat) {
  document.getElementById("modalCategoriaTitulo").textContent = "Editar Categoría";
  document.getElementById("edit_idcategoria").value = cat.idcategoria;
  document.getElementById("edit_nombre_categoria").value = cat.categoria || "";

  const sel = resetSelectPadre();
  if (sel) {
    [...sel.options].forEach((o) => (o.disabled = o.value == cat.idcategoria));
    sel.value = cat.idcategoriapadre || "";

    if (Number(cat.total_hijos) > 0) {
      sel.disabled = true;
      document.getElementById("ayudaPadre").textContent = "Tiene subcategorías, por eso debe seguir siendo principal.";
    }
  }

  new bootstrap.Modal(document.getElementById("modalNuevaCategoria")).show();
}

function limpiarModalMarca() {
  document.getElementById("modalMarcaTitulo").textContent = "Nueva Marca";
  document.getElementById("edit_idmarca").value = "";
  document.getElementById("edit_nombre_marca").value = "";
}

function editarMarca(mar) {
  document.getElementById("modalMarcaTitulo").textContent = "Editar Marca";
  document.getElementById("edit_idmarca").value = mar.idmarca;
  document.getElementById("edit_nombre_marca").value = mar.marca || "";

  new bootstrap.Modal(document.getElementById("modalNuevaMarca")).show();
}

document.addEventListener("click", function (event) {
  const boton = event.target.closest(".btn-toggle-hijos");
  if (!boton) return;

  const filaPadre = boton.closest("tr");
  const idPadre = filaPadre.dataset.id;
  const filasHijas = document.querySelectorAll(`.fila-hija.hijo-de-${idPadre}`);
  const expandir = boton.textContent.trim() === "▸";

  filasHijas.forEach(function (fila) {
    fila.classList.toggle("d-none", !expandir);
  });

  boton.textContent = expandir ? "▾" : "▸";
});