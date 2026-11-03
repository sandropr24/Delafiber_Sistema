const $ = (id) => document.getElementById(id);

const AYUDA_TIPO = {
  PRODUCTO: "Equipo físico: puede controlar stock y número de serie.",
  SERVICIO:
    "Servicio técnico: solo necesita nombre, categoría, unidad; precio sugerido opcional.",
  COMBO: "Kit o combo: no maneja stock ni serie propios y requiere una marca.",
};

function mostrarVistaPrevia(src) {
  const img = $("previewImg");
  const icon = $("previewIcon");

  if (!img || !icon) return;

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

    reader.onload = (e) => {
      mostrarVistaPrevia(e.target.result);
    };

    reader.readAsDataURL(input.files[0]);
  } else {
    mostrarVistaPrevia(null);
  }
}

function asegurarOpcion(select, valor, texto) {
  if (!select || !valor) return;

  const existe = [...select.options].some((opcion) => opcion.value == valor);

  if (!existe) {
    select.add(
      new Option((texto || "Registro #" + valor) + " (inactiva)", valor),
    );
  }
}

function asignarValor(id, valor) {
  const campo = $(id);

  if (campo) {
    campo.value = valor ?? "";
  }
}

function asignarCheckbox(id, valor) {
  const campo = $(id);

  if (campo) {
    campo.checked = String(valor) === "1";
  }
}

function aplicarTipo(reiniciar = true) {
  const tipo = $("tipo")?.value || "PRODUCTO";

  document.querySelectorAll("[data-oculto-en]").forEach((el) => {
    const ocultar = el.dataset.ocultoEn.split(",").includes(tipo);

    el.classList.toggle("d-none", ocultar);

    el.querySelectorAll("input, select").forEach((campo) => {
      campo.disabled = ocultar;

      if (!ocultar || !reiniciar) return;

      if (campo.type === "checkbox") {
        campo.checked = false;
      } else if (campo.type !== "file") {
        campo.value = "";
      }
    });
  });

  if (tipo === "SERVICIO" || tipo === "COMBO") {
    mostrarVistaPrevia(null);
  }

  if (tipo === "PRODUCTO" && reiniciar && $("controlastock")) {
    $("controlastock").checked = true;
  }

  if (tipo === "SERVICIO" && reiniciar && !$("idunidad")?.value) {
    const opcion = [...$("idunidad").options].find((o) =>
      /servicio/i.test(o.text),
    );

    if (opcion) {
      $("idunidad").value = opcion.value;
    }
  }

  if ($("tipoAyuda")) {
    $("tipoAyuda").textContent = AYUDA_TIPO[tipo] || "";
  }
}

function actualizarCampoSerie() {
  const contenedor = $("contenedorSerieInicial");
  const campo = $("serieinicial");
  const visible =
    $("tipo")?.value === "PRODUCTO" && $("controlaserie")?.checked;
  if (contenedor) contenedor.classList.toggle("d-none", !visible);
  if (campo) {
    campo.disabled = !visible;
    if (!visible) campo.value = "";
  }
}

function limpiarFormulario() {
  const modal = $("modalProducto");
  const formulario = modal?.querySelector("form");

  if (formulario) {
    formulario.reset();
  }

  if ($("modalTitulo")) {
    $("modalTitulo").textContent = "Nuevo Producto";
  }

  asignarValor("idproducto", "");
  asignarValor("tipo", "PRODUCTO");
  asignarValor("idcategoria", "");
  asignarValor("idmarca", "");
  asignarValor("nombre", "");
  asignarValor("descripcion", "");
  asignarValor("modelo", "");
  asignarValor("codigobarras", "");
  asignarValor("idunidad", "");
  asignarValor("preciocosto", "0");
  asignarValor("monedacosto", "USD");
  asignarValor("precioventa", "");
  asignarValor("garantiameses", "0");
  asignarValor("estado", "1");

  asignarCheckbox("controlastock", 1);
  asignarCheckbox("controlaserie", 0);

  if ($("imagen")) {
    $("imagen").value = "";
  }

  if ($("divEstado")) {
    $("divEstado").style.display = "none";
  }

  mostrarVistaPrevia(null);
  aplicarTipo(true);
  actualizarCampoSerie();
}

function cargarFormulario(d) {
  if (!d || typeof d !== "object") {
    console.error("Datos del producto no válidos.");
    return;
  }

  const esEdicion = Boolean(d.idproducto);

  if ($("modalTitulo")) {
    $("modalTitulo").textContent = esEdicion
      ? "Editar Producto"
      : "Nuevo Producto";
  }

  asignarValor("idproducto", d.idproducto || "");
  asignarValor("tipo", d.tipo || "PRODUCTO");

  asegurarOpcion($("idcategoria"), d.idcategoria, d.categoria);
  asegurarOpcion($("idmarca"), d.idmarca, d.marca);

  asignarValor("idcategoria", d.idcategoria || "");
  asignarValor("idmarca", d.idmarca || "");

  asignarValor("nombre", d.nombre || "");
  asignarValor("descripcion", d.descripcion || "");
  asignarValor("modelo", d.modelo || "");
  asignarValor("codigobarras", d.codigobarras || "");
  asignarValor("idunidad", d.idunidad || "");

  asignarValor("preciocosto", d.preciocosto ?? "0");
  asignarValor("monedacosto", d.monedacosto || "USD");
  asignarValor("precioventa", d.precioventa ?? "");
  asignarValor("garantiameses", d.garantiameses ?? "0");

  if ($("imagen")) {
    $("imagen").value = "";
  }

  aplicarTipo(false);

  asignarCheckbox("controlastock", d.controlastock);
  asignarCheckbox("controlaserie", d.controlaserie);
  asignarValor("serieinicial", d.serieinicial || "");
  actualizarCampoSerie();

  if (d.tipo === "SERVICIO") {
    asignarValor("idmarca", "");
  }

  if ($("divEstado")) {
    $("divEstado").style.display = esEdicion ? "block" : "none";
  }

  asignarValor("estado", d.estado ?? "1");

  const rutaImagen = d.imagen
    ? BASE_URL.replace(/\/?$/, "/") +
      "uploads/productos/" +
      encodeURIComponent(d.imagen)
    : null;

  mostrarVistaPrevia(rutaImagen);
}

function abrirModalProducto() {
  const modal = $("modalProducto");

  if (!modal) {
    console.error("No se encontró el modal de productos.");
    return;
  }

  bootstrap.Modal.getOrCreateInstance(modal).show();
}

function editarProducto(producto) {
  try {
    cargarFormulario(producto);
    abrirModalProducto();
  } catch (error) {
    console.error("Error al abrir la edición:", error);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const viewContainer = $("viewProductos");

  if (!viewContainer) return;

  const tbody = $("tbodyProductos");

  $("tipo")?.addEventListener("change", () => {
    aplicarTipo(true);
  });

  aplicarTipo(true);
  actualizarCampoSerie();
  $("controlaserie")?.addEventListener("change", actualizarCampoSerie);
  $("tipo")?.addEventListener("change", actualizarCampoSerie);

  if (typeof FORM_PREVIO !== "undefined" && FORM_PREVIO) {
    cargarFormulario(FORM_PREVIO);
    abrirModalProducto();
  }

  if (tbody) {
    tbody.addEventListener("click", function (event) {
      const boton = event.target.closest(".btn-editar-producto");

      if (!boton || !tbody.contains(boton)) return;

      try {
        const producto = JSON.parse(boton.dataset.producto);

        editarProducto(producto);
      } catch (error) {
        console.error("No se pudieron cargar los datos del producto:", error);
      }
    });
  }

  const urlBuscar = viewContainer.getAttribute("data-url-buscar");

  let temporizador = null;
  let ultimaPeticion = 0;

  function buscar() {
    if (!tbody || !urlBuscar) return;

    const params = new URLSearchParams({
      q: $("inputBuscarTablaProductos")?.value.trim() || "",
      categoria: $("filtroCategoria")?.value || "",
      tipo: $("filtroTipo")?.value || "",
      estado: $("filtroEstado")?.value || "",
    });

    const numero = ++ultimaPeticion;

    fetch(urlBuscar + "?" + params.toString(), {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((res) => {
        if (!res.ok) {
          throw new Error("HTTP " + res.status);
        }

        return res.text();
      })
      .then((html) => {
        if (numero === ultimaPeticion) {
          tbody.innerHTML = html;
        }
      })
      .catch((error) => {
        console.error("Error al buscar productos:", error);
      });
  }

  $("inputBuscarTablaProductos")?.addEventListener("input", () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(buscar, 300);
  });

  ["filtroCategoria", "filtroTipo", "filtroEstado"].forEach((id) => {
    $(id)?.addEventListener("change", buscar);
  });
});
