const $ = (id) => document.getElementById(id);

const AYUDA_TIPO = {
  PRODUCTO: "Equipo físico: puede controlar stock y número de serie.",
  SERVICIO: "Servicio técnico: solo necesita nombre, categoría, unidad y precios.",
  COMBO: "Kit o combo: no maneja stock ni serie propios y requiere una marca.",
};

function mostrarVistaPrevia(src) {
  const img = $("previewImg");
  const icon = $("previewIcon");

  if (src) {
    img.src = src;
    img.style.display = "block";
    icon.style.display = "none";
  } else {
    img.src = "";
    img.style.display = "none";
    icon.style.display = "block";
  }
}

function previsualizarImagen(event) {
  const input = event.target;

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => mostrarVistaPrevia(e.target.result);
    reader.readAsDataURL(input.files[0]);
  } else {
    mostrarVistaPrevia(null);
  }
}

function asegurarOpcion(select, valor, texto) {
  if (!valor) return;
  const existe = [...select.options].some((o) => o.value == valor);
  if (!existe) {
    select.add(new Option((texto || "Registro #" + valor) + " (inactiva)", valor));
  }
}


function aplicarTipo(reiniciar = true) {
  const tipo = $("tipo").value;

  document.querySelectorAll("[data-oculto-en]").forEach((el) => {
    const ocultar = el.dataset.ocultoEn.split(",").includes(tipo);
    el.classList.toggle("d-none", ocultar);

    el.querySelectorAll("input, select").forEach((campo) => {
      campo.disabled = ocultar;
      if (!ocultar) return;
      if (campo.type === "checkbox") campo.checked = false;
      else campo.value = "";
    });
  });

  if (tipo === "SERVICIO") mostrarVistaPrevia(null);

  if (tipo === "PRODUCTO" && reiniciar) {
    $("controlastock").checked = true;
  }

  if (tipo === "SERVICIO" && reiniciar && !$("idunidad").value) {
    const opcion = [...$("idunidad").options].find((o) => /servicio/i.test(o.text));
    if (opcion) $("idunidad").value = opcion.value;
  }

  $("tipoAyuda").textContent = AYUDA_TIPO[tipo] || "";
}

function limpiarFormulario() {
  $("modalTitulo").textContent = "Nuevo Producto";
  $("idproducto").value = "";
  $("tipo").value = "PRODUCTO";
  $("idcategoria").value = "";
  $("idmarca").value = "";
  $("nombre").value = "";
  $("descripcion").value = "";
  $("modelo").value = "";
  $("codigobarras").value = "";
  $("idunidad").value = "";
  $("preciocosto").value = "0";
  $("monedacosto").value = "USD";
  $("precioventa").value = "";
  $("garantiameses").value = "0";
  $("controlaserie").checked = false;
  $("imagen").value = "";
  $("divEstado").style.display = "none";
  $("estado").value = "1";

  mostrarVistaPrevia(null);
  aplicarTipo(true);
}

function cargarFormulario(d) {
  const esEdicion = !!d.idproducto;

  $("modalTitulo").textContent = esEdicion ? "Editar Producto" : "Nuevo Producto";
  $("idproducto").value = d.idproducto || "";
  $("tipo").value = d.tipo || "PRODUCTO";

  asegurarOpcion($("idcategoria"), d.idcategoria, d.categoria);
  asegurarOpcion($("idmarca"), d.idmarca, d.marca);
  $("idcategoria").value = d.idcategoria || "";
  $("idmarca").value = d.idmarca || "";

  $("nombre").value = d.nombre || "";
  $("descripcion").value = d.descripcion || "";
  $("modelo").value = d.modelo || "";
  $("codigobarras").value = d.codigobarras || "";
  $("idunidad").value = d.idunidad || "";
  $("preciocosto").value = d.preciocosto ?? "0";
  $("monedacosto").value = d.monedacosto || "USD";
  $("precioventa").value = d.precioventa ?? "";
  $("garantiameses").value = d.garantiameses ?? "0";
  $("imagen").value = "";

  aplicarTipo(false);
  $("controlastock").checked = String(d.controlastock) === "1";
  $("controlaserie").checked = String(d.controlaserie) === "1";
  if (d.tipo === "SERVICIO") $("idmarca").value = "";

  $("divEstado").style.display = esEdicion ? "block" : "none";
  $("estado").value = d.estado ?? "1";

  mostrarVistaPrevia(d.imagen ? BASE_URL + "uploads/productos/" + d.imagen : null);
}

function abrirModalProducto() {
  bootstrap.Modal.getOrCreateInstance($("modalProducto")).show();
}

function editarProducto(p) {
  cargarFormulario(p);
  abrirModalProducto();
}

document.addEventListener("DOMContentLoaded", function () {
  const viewContainer = $("viewProductos");
  if (!viewContainer) return;

  $("tipo").addEventListener("change", () => aplicarTipo(true));
  aplicarTipo(true);

  if (typeof FORM_PREVIO !== "undefined" && FORM_PREVIO) {
    cargarFormulario(FORM_PREVIO);
    abrirModalProducto();
  }

  const urlBuscar = viewContainer.getAttribute("data-url-buscar");
  const tbody = $("tbodyProductos");
  let temporizador = null;
  let ultimaPeticion = 0;

  function buscar() {
    const params = new URLSearchParams({
      q: $("inputBuscarTablaProductos").value.trim(),
      categoria: $("filtroCategoria").value,
      tipo: $("filtroTipo").value,
      estado: $("filtroEstado").value,
    });

    const numero = ++ultimaPeticion;

    fetch(urlBuscar + "?" + params.toString(), {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.text();
      })
      .then((html) => {
        if (numero === ultimaPeticion) tbody.innerHTML = html;
      })
      .catch((err) => console.error("Error al buscar productos:", err));
  }

  $("inputBuscarTablaProductos").addEventListener("input", () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(buscar, 300);
  });

  ["filtroCategoria", "filtroTipo", "filtroEstado"].forEach((id) => {
    $(id).addEventListener("change", buscar);
  });
});