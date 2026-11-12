const filtrosCatalogos = {
  categorias: { buscar: "", estado: "todos" },
  marcas: { buscar: "", estado: "todos" },
};

const peticionesCatalogos = {};

document.addEventListener("DOMContentLoaded", function () {
  let activeTab = localStorage.getItem("activeCatalogoTab");

  if (activeTab) {
    let tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);

    if (tabTrigger) {
      new bootstrap.Tab(tabTrigger).show();
    }
  }

  document
    .querySelectorAll('button[data-bs-toggle="tab"]')
    .forEach(function (element) {
      element.addEventListener("show.bs.tab", function (event) {
        localStorage.setItem(
          "activeCatalogoTab",
          event.target.getAttribute("data-bs-target"),
        );
      });
    });

  configurarFiltrosCatalogo("Categorias", "categorias", cargarCategorias);
  configurarFiltrosCatalogo("Marcas", "marcas", cargarMarcas);

  cargarCategorias(1);
  cargarMarcas(1);

  document.addEventListener("submit", function (event) {
    const form = event.target.closest(".form-cambiar-estado");

    if (!form) return;

    event.preventDefault();

    if (form.dataset.confirmando === "true") return;

    if (typeof Swal === "undefined") {
      console.error(
        "SweetAlert2 no está disponible. Se canceló el cambio de estado.",
      );
      return;
    }

    const accion = form.dataset.accion === "activar" ? "activar" : "desactivar";
    const elemento = form.dataset.elemento || "registro";
    const nombre = form.dataset.nombre || "este registro";

    Swal.fire({
      title: `¿${accion === "activar" ? "Activar" : "Desactivar"} ${elemento}?`,
      text: `¿Deseas ${accion} la ${elemento} «${nombre}»?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: `Sí, ${accion}`,
      cancelButtonText: "Cancelar",
      confirmButtonColor: accion === "activar" ? "#198754" : "#e11d48",
      cancelButtonColor: "#64748b",
      reverseButtons: true,
      focusCancel: true,
    }).then((resultado) => {
      if (!resultado.isConfirmed || !form.isConnected) return;

      form.dataset.confirmando = "true";

      const boton = form.querySelector('button[type="submit"]');

      if (boton) boton.disabled = true;

      HTMLFormElement.prototype.submit.call(form);
    });
  });
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
    filtro: filtrosCatalogos.categorias,
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
    filtro: filtrosCatalogos.marcas,
  });
}

function cargarTabla(cfg) {
  const tbody = document.querySelector(`${cfg.panel} tbody`);
  const info = document.getElementById(cfg.infoId);

  if (peticionesCatalogos[cfg.url]) {
    peticionesCatalogos[cfg.url].abort();
  }

  const controller = new AbortController();
  peticionesCatalogos[cfg.url] = controller;

  const params = new URLSearchParams({
    page: cfg.pagina,
    buscar: cfg.filtro.buscar,
    estado: cfg.filtro.estado,
  });

  tbody.innerHTML = `<tr><td colspan="${cfg.columnas}" class="text-center text-muted py-4">Cargando registros...</td></tr>`;

  fetch(`${BASE_URL}/admin/catalogos/${cfg.url}?${params.toString()}`, {
    signal: controller.signal,
    headers: { "X-Requested-With": "XMLHttpRequest" },
    credentials: "same-origin",
  })
    .then((response) => {
      if (response.redirected && response.url.includes("/login")) {
        window.location.href = response.url;
        throw new Error("Sesión expirada");
      }

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

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
        `${data.totalRegistros ?? 0} ${cfg.etiqueta}`,
      );
    })
    .catch((error) => {
      if (error.name === "AbortError") return;

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

function renderPaginacion(
  elementId,
  totalPaginas,
  paginaActual,
  recargar,
  infoId,
  resumen,
) {
  const ul = document.getElementById(elementId);

  if (!ul) return;

  ul.innerHTML = "";

  totalPaginas = Number(totalPaginas) || 1;
  paginaActual = Number(paginaActual) || 1;

  const info = document.getElementById(infoId);

  if (info) {
    info.textContent = `Página ${paginaActual} de ${totalPaginas} · ${resumen}`;
  }

  const agregar = (
    texto,
    destino,
    { activo = false, deshabilitado = false } = {},
  ) => {
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

  if (totalPaginas === 1) return;

  agregar("Anterior", paginaActual - 1, {
    deshabilitado: paginaActual <= 1,
  });

  paginasVisibles(totalPaginas, paginaActual).forEach((p) => {
    if (p === "…") {
      agregar("…", 0, { deshabilitado: true });
    } else {
      agregar(String(p), p, { activo: p === paginaActual });
    }
  });

  agregar("Siguiente", paginaActual + 1, {
    deshabilitado: paginaActual >= totalPaginas,
  });
}

function paginasVisibles(total, actual) {
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const orden = [...new Set([1, total, actual - 1, actual, actual + 1])]
    .filter((p) => p >= 1 && p <= total)
    .sort((a, b) => a - b);

  const resultado = [];

  orden.forEach((p, i) => {
    if (i > 0 && p - orden[i - 1] > 1) {
      resultado.push("…");
    }

    resultado.push(p);
  });

  return resultado;
}

function resetSelectPadre() {
  const sel = document.getElementById("edit_idcategoriapadre");

  if (!sel) return;

  sel.disabled = false;
  sel.value = "";

  [...sel.options].forEach((o) => {
    o.disabled = false;
  });

  const ayuda = document.getElementById("ayudaPadre");

  if (ayuda) ayuda.textContent = "";

  return sel;
}

function limpiarModalCategoria() {
  document.getElementById("modalCategoriaTitulo").textContent =
    "Nueva Categoría";
  document.getElementById("edit_idcategoria").value = "";
  document.getElementById("edit_nombre_categoria").value = "";

  resetSelectPadre();
}

function nuevaSubcategoria(idPadre) {
  limpiarModalCategoria();

  document.getElementById("modalCategoriaTitulo").textContent =
    "Nueva Subcategoría";
  document.getElementById("edit_idcategoriapadre").value = idPadre;

  new bootstrap.Modal(document.getElementById("modalNuevaCategoria")).show();
}

function editarCategoria(cat) {
  document.getElementById("modalCategoriaTitulo").textContent =
    "Editar Categoría";
  document.getElementById("edit_idcategoria").value = cat.idcategoria;
  document.getElementById("edit_nombre_categoria").value = cat.categoria || "";

  const sel = resetSelectPadre();

  if (sel) {
    [...sel.options].forEach((o) => {
      o.disabled = o.value == cat.idcategoria;
    });

    sel.value = cat.idcategoriapadre || "";

    if (Number(cat.total_hijos) > 0) {
      sel.disabled = true;

      document.getElementById("ayudaPadre").textContent =
        "Tiene subcategorías, por eso debe seguir siendo principal.";
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
  const expandir = boton.getAttribute("aria-expanded") !== "true";

  filasHijas.forEach(function (fila) {
    fila.classList.toggle("d-none", !expandir);
  });

  boton.textContent = expandir ? "▾" : "▸";
  boton.setAttribute("aria-expanded", String(expandir));
});

function configurarFiltrosCatalogo(sufijo, tipo, recargar) {
  const buscar = document.getElementById(`buscar${sufijo}Input`);
  const estado = document.getElementById(`estado${sufijo}Filtro`);
  const limpiar = document.getElementById(`limpiar${sufijo}Filtro`);

  let espera;

  buscar.addEventListener("input", () => {
    clearTimeout(espera);

    espera = setTimeout(() => {
      filtrosCatalogos[tipo].buscar = buscar.value.trim();
      recargar(1);
    }, 300);
  });

  estado.addEventListener("change", () => {
    filtrosCatalogos[tipo].estado = estado.value;
    recargar(1);
  });

  limpiar.addEventListener("click", () => {
    clearTimeout(espera);

    buscar.value = "";
    estado.value = "todos";

    filtrosCatalogos[tipo] = {
      buscar: "",
      estado: "todos",
    };

    recargar(1);
    buscar.focus();
  });
}
