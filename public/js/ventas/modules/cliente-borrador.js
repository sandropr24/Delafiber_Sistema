import { $, $$, leer, alertarCliente } from './utils.js';
import { crearBusqueda } from './buscador.js';
import { fila, entrega, totalizar, restaurarArticulo } from './carrito.js';
import { actualizarConfirmacion } from './pagos.js';

let draftKey, selectores, carrito, buscador;
let restaurando = false;
let pedidoInicializado = false;
export function sincronizarCliente() {
  const cliente = $("selectCliente");
  $("buscarClienteVenta").value = cliente.value
    ? cliente.selectedOptions[0].textContent.trim().replace(/\s+/g, " ")
    : "";
  $("buscarClienteVenta").setCustomValidity(
    cliente.value ? "" : "Seleccione un cliente de los resultados.",
  );
}
export function configurarDocumentoExpress() {
  const form = $("formClienteExpress");
  if (!form) return;
  const tipo = form.elements.tipodoc.value;
  const longitud = tipo === "RUC" ? 11 : 8;
  const documento = form.elements.documento;
  documento.minLength = longitud;
  documento.maxLength = longitud;
  documento.pattern = `[0-9]{${longitud}}`;
  documento.title = `Ingrese exactamente ${longitud} dígitos para ${tipo}.`;
}

export function guardarBorrador() {
  if (restaurando) return;
  try {
    const datos = [...new FormData($("formVenta")).entries()].filter(
      ([name]) => name !== "csrf_test_name" && !name.startsWith("csrf_"),
    );
    sessionStorage.setItem(
      draftKey,
      JSON.stringify({ fecha: Date.now(), datos, pedidoInicializado }),
    );
  } catch (error) {
    console.warn("No se pudo conservar el borrador.", error);
  }
}
export function restaurarBorrador() {
  let entradas = [];
  const esPedido = Number($('ventaConfig').dataset.pedido) > 0 || Number($('ventaConfig').dataset.cotizacion) > 0;
  const old = leer("old", {});
  const aplanar = (objeto, prefijo = "") =>
    Object.entries(objeto).flatMap(([clave, valor]) => {
      const nombre = prefijo ? `${prefijo}[${clave}]` : clave;
      return valor && typeof valor === "object"
        ? aplanar(valor, nombre)
        : [[nombre, String(valor ?? "")]];
    });
  try {
    if (Object.keys(old).length) entradas = aplanar(old);
    else if ($('ventaConfig').dataset.iniciarPedido !== '1') {
      const borrador = JSON.parse(sessionStorage.getItem(draftKey) || "null");
      if (
        borrador &&
        Date.now() - borrador.fecha < 86400000 &&
        Array.isArray(borrador.datos)
      ) {
        const tieneArticulos = borrador.datos.some(([name]) => /^detalles\[[^\]]+\]\[idproducto\]$/.test(name));
        // Un borrador antiguo vacío no debe ocultar los artículos de una conversión nueva.
        if (!esPedido || tieneArticulos || borrador.pedidoInicializado === true) {
          entradas = borrador.datos;
          pedidoInicializado = borrador.pedidoInicializado === true;
        }
      }
    }
  } catch (error) {
    console.warn("Borrador no disponible.", error);
  }
  if (!entradas.length && esPedido) {
    entradas = aplanar(leer('pedido-datos', {}));
  }
  if (!entradas.length) return;
  restaurando = true;
  const datos = new Map(entradas);
  const formulario = $("formVenta");
  for (const [name, value] of entradas) {
    if (
      name.startsWith("csrf_") ||
      name === 'idpedido' ||
      name === 'idcotizacion' ||
      name.startsWith("detalles[") ||
      name.startsWith("pagos[")
    )
      continue;
    const campo = formulario.elements.namedItem(name);
    if (campo && "value" in campo) campo.value = value;
  }
  sincronizarCliente();
  entrega();
  $("tipoDescuento")?.dispatchEvent(new Event("change"));
  let omitidos = 0;
  for (const [name, value] of entradas.filter(([name]) =>
    /^detalles\[[^\]]+\]\[idproducto\]$/.test(name),
  )) {
    const base = name.slice(0, -"[idproducto]".length);
    const selector = selectores.find((select) =>
      [...select.options].some((opt) => opt.value === value),
    );
    if (!selector) {
      omitidos++;
      continue;
    }
    selector.value = value;
    restaurarArticulo(selector, datos.get(base + "[cantidad]") || "1");
    const row = fila(value);
    if (!row) { omitidos++; continue; }
    const precioGuardado = datos.get(base + "[precioventa]");
    if (row && precioGuardado) {
      const input = row.querySelector(".input-precio");
      input.value = precioGuardado;
      // Un precio previo bajo el catálogo queda visible para corregirlo, sin sustituirlo silenciosamente.
      if ((!Number($('ventaConfig').dataset.pedido) && !Number($('ventaConfig').dataset.cotizacion)) || Number(input.value) >= Number(input.min)) {
        input.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }
  }
  for (const [name, value] of entradas.filter(([name]) =>
    /^pagos\[[^\]]+\]\[idtipopago\]$/.test(name),
  )) {
    const base = name.slice(0, -"[idtipopago]".length);
    $("selectTipoPago").value = value;
    $("selectTipoPago").dispatchEvent(new Event("change"));
    $("inputMontoPago").value = datos.get(base + "[monto]") || "";
    $("inputReferenciaPago").value = datos.get(base + "[referencia]") || "";
    $("inputRecibidoPago").value = datos.get(base + "[montorecibido]") || "";
    $("btnAgregarPago").click();
  }
  for (const input of $$(".selector-serie", carrito)) {
    if (datos.has(input.name)) input.value = datos.get(input.name);
  }
  restaurando = false;
  if (esPedido && carrito.querySelector('.input-cantidad')) pedidoInicializado = true;
  totalizar();
  const mensajeBorrador = "No se pudieron cargar algunos artículos. Revisa el carrito.";
  $("detalleBorrador").textContent = mensajeBorrador;
  $("avisoBorrador").hidden = omitidos === 0;
}
export function limpiarBorrador() {
  restaurando = true;
  try {
    sessionStorage.removeItem(draftKey);
  } catch (error) {
    console.warn(error);
  }
  window.location.assign(window.location.pathname);
}

export function inicializarClienteBorrador(estado) {
  ({ selectores, carrito, buscador } = estado);
  draftKey = 'venta-borrador-' + $('ventaConfig').dataset.user;
  if (Number($('ventaConfig').dataset.pedido)) draftKey += '-pedido-' + $('ventaConfig').dataset.pedido;
  else if (Number($('ventaConfig').dataset.cotizacion)) draftKey += '-cotizacion-' + $('ventaConfig').dataset.cotizacion;
  if ($('ventaConfig').dataset.iniciarPedido === '1') {
    try { sessionStorage.removeItem(draftKey); } catch (error) { console.warn(error); }
    const url = new URL(window.location.href);
    url.searchParams.delete('desde_pedido');
    url.searchParams.delete('desde_cotizacion');
    window.history.replaceState(null, '', url);
  }
  crearBusqueda(
    $("buscarClienteVenta"),
    $("resultadosClientes"),
    () =>
      [...$("selectCliente").options]
        .filter((x) => x.value)
        .map((x) => ({
          value: x.value,
          label: x.textContent.trim().replace(/\s+/g, " "),
        })),
    (item) => {
      $("selectCliente").value = item.value;
      $("selectCliente").dispatchEvent(new Event("change", { bubbles: true }));
    },
    true,
  );
  $("buscarClienteVenta").addEventListener("input", () => {
    $("selectCliente").value = "";
    $("buscarClienteVenta").setCustomValidity(
      "Seleccione un cliente de los resultados.",
    );
  });
  $("selectCliente").addEventListener("change", sincronizarCliente);
  $("btnPublicoGeneral")?.addEventListener("click", () => {
    const id = $("btnPublicoGeneral").dataset.cliente;
    if (!id || id === "0") return;
    $("selectCliente").value = id;
    $("selectCliente").dispatchEvent(new Event("change", { bubbles: true }));
    $("resultadosClientes").hidden = true;
    $("buscarClienteVenta").setAttribute("aria-expanded", "false");
    buscador.focus();
  });
  sincronizarCliente();
  $("tipoDocClienteExpress")?.addEventListener(
    "change",
    configurarDocumentoExpress,
  );
  configurarDocumentoExpress();

  $("formClienteExpress")?.elements.nombre.addEventListener(
    "input",
    (event) => {
      event.target.value = event.target.value.replace(/\p{N}/gu, "");
    },
  );
  $("formClienteExpress")?.elements.telefono.addEventListener(
    "input",
    (event) => {
      event.target.value = event.target.value.replace(/[^0-9]/g, "");
    },
  );

  $("formClienteExpress")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const datos = new FormData(form);
    const documento = String(datos.get("documento") || "").trim();
    const nombre = String(datos.get("nombre") || "").trim();
    const telefono = String(datos.get("telefono") || "").trim();
    if (/\p{N}/u.test(nombre))
      return alertarCliente("Nombre inválido", "El nombre no debe contener números.");
    if (telefono && !/^[0-9]{1,9}$/.test(telefono)) {
      return alertarCliente(
        "Teléfono inválido",
        "Ingrese solo números, hasta 9 dígitos.",
      );
    }

    const tipoDoc = String(datos.get("tipodoc") || "");
    const patronDocumento =
      tipoDoc === "DNI" ? /^\d{8}$/ : tipoDoc === "RUC" ? /^\d{11}$/ : null;
    if (!patronDocumento || !patronDocumento.test(documento)) {
      return alertarCliente(
        "Documento inválido",
        "Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.",
      );
    }
    if (nombre.length < 2)
      return alertarCliente("Nombre requerido", "Ingrese el nombre o razón social.");

    const boton = $("btnGuardarClienteExpress");
    const original = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i> Verificando...';

    try {
      const respuesta = await fetch(form.dataset.urlExpress, {
        method: "POST",
        body: datos,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      });
      const token = respuesta.headers.get("X-CSRF-TOKEN");
      const tokenName = respuesta.headers.get("X-CSRF-NAME");
      if (token && tokenName) {
        document
          .querySelectorAll(
            '#formVenta input[type="hidden"], #formClienteExpress input[type="hidden"]',
          )
          .forEach((input) => {
            if (input.name === tokenName) {
              input.value = token;
              // form.reset() debe conservar el token recién recibido.
              input.defaultValue = token;
            }
          });
      }
      const resultado = await respuesta.json();

      if (
        !respuesta.ok ||
        resultado.status !== "success" ||
        !resultado.idcliente
      ) {
        throw new Error(
          resultado.message || "No se pudo registrar el cliente.",
        );
      }

      const cliente = $("selectCliente");
      const id = String(resultado.idcliente);
      let opcion = [...cliente.options].find((opt) => opt.value === id);
      if (!opcion) {
        opcion = new Option("", id);
        cliente.add(opcion);
      }

      opcion.textContent = `${resultado.nombre || nombre} (${resultado.documento || documento})`;
      cliente.value = id;
      cliente.dispatchEvent(new Event("change", { bubbles: true }));
      bootstrap.Modal.getInstance($("modalClienteExpress"))?.hide();
      form.reset();
      configurarDocumentoExpress();

      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: "Cliente seleccionado correctamente",
        showConfirmButton: false,
        timer: 2500,
      });
    } catch (error) {
      console.error("Error en registro rápido:", error);
      await alertarCliente("No se pudo registrar el cliente", error.message);
    } finally {
      boton.disabled = false;
      boton.innerHTML = original;
    }
  });

  $("formVenta").addEventListener("input", () => {
    guardarBorrador();
    actualizarConfirmacion();
  });
  $("formVenta").addEventListener("change", () => {
    guardarBorrador();
    actualizarConfirmacion();
  });
  $("formVenta").addEventListener("click", () => {
    if (!restaurando) setTimeout(guardarBorrador, 0);
  });
  $("limpiarBorrador")?.addEventListener("click", limpiarBorrador);
  $("cerrarAvisoBorrador")?.addEventListener("click", () => {
    $("avisoBorrador").hidden = true;
  });
  $("descartarBorrador")?.addEventListener("click", () => {
    if (
      !window.confirm("¿Descartar los datos de esta venta y comenzar de nuevo?")
    )
      return;
    limpiarBorrador();
  });
}
