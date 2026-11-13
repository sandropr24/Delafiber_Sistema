import { $, money, cantidadCorrecta } from "../ventas/modules/utils.js";
import { crearBusqueda } from "../ventas/modules/buscador.js";

const config = $("pedidoConfig");
const form = $("formPedido");
const productos = JSON.parse(config.dataset.productos);
const catalogo = new Map(
  productos.map((producto) => [String(producto.idproducto), producto]),
);
const filas = new Map();
const esAdmin = config.dataset.admin === "1";
const draftKey = "pedido-borrador-" + config.dataset.user;
let enviando = false;
let modificado = false;
let restaurando = true;
let calculoValido = false;

function error(texto) {
  $("errorPedido").textContent = texto;
  $("errorPedido").classList.remove("d-none");
  $("errorPedido").focus();
}
const decimal = (producto) =>
  producto.tipo === "PRODUCTO" &&
  producto.unidad?.toUpperCase() === "M" &&
  Number(producto.controlaserie) !== 1;
function clienteElegido() {
  const cliente = $("clientePedido");
  $("buscarClientePedido").value = cliente.value
    ? cliente.selectedOptions[0].textContent.trim()
    : "";
  $("buscarClientePedido").setCustomValidity(
    cliente.value ? "" : "Seleccione un cliente de los resultados.",
  );
}
crearBusqueda(
  $("buscarClientePedido"),
  $("resultadosClientesPedido"),
  () =>
    [...$("clientePedido").options]
      .filter((opcion) => opcion.value)
      .map((opcion) => ({
        value: opcion.value,
        label: opcion.textContent.trim(),
      })),
  (opcion) => {
    $("clientePedido").value = opcion.value;
    clienteElegido();
    guardarBorrador();
    totalizar();
  },
  true,
);
$("buscarClientePedido").addEventListener("input", () => {
  $("clientePedido").value = "";
  $("buscarClientePedido").setCustomValidity(
    "Seleccione un cliente de los resultados.",
  );
});
crearBusqueda(
  $("buscarArticuloPedido"),
  $("resultadosArticulosPedido"),
  () =>
    productos.map((producto) => ({
      value: String(producto.idproducto),
      codigo: producto.codigobarras || String(producto.idproducto),
      label: `${producto.nombre} · ${producto.tipo} · ${money(producto.precioventa)}`,
    })),
  (opcion) => {
    agregar(opcion.value);
    $("buscarArticuloPedido").value = "";
    $("buscarArticuloPedido").focus();
  },
);

function agregar(id, cantidad = null, precio = null) {
  id = String(id);
  const producto = catalogo.get(id);
  if (!producto && !restaurando) return;
  if (!filas.has(id) && filas.size >= 200)
    return error("El pedido admite hasta 200 artículos.");
  if (filas.has(id)) {
    const fila = filas.get(id);
    fila.querySelector(".pedido-cantidad").value =
      Number(fila.querySelector(".pedido-cantidad").value) +
      Number(cantidad || 1);
  } else {
    const row = document.createElement("tr");
    const fraccion = producto && decimal(producto);
    row.dataset.id = id;
    row.dataset.disponible = producto ? "1" : "0";
    row.innerHTML = `<td><strong class="pedido-nombre"></strong><small class="d-block text-muted pedido-tipo"></small><input type="hidden" name="detalles[${Number(id)}][idproducto]" value="${Number(id)}"></td><td><input aria-label="Cantidad" class="form-control pedido-cantidad" type="number" name="detalles[${Number(id)}][cantidad]" min="${fraccion ? "0.01" : "1"}" max="99999999.99" step="${fraccion ? "0.01" : "1"}" required></td><td><input aria-label="Precio unitario" class="form-control pedido-precio" type="number" name="detalles[${Number(id)}][precioventa]" step="0.01" max="99999999.99" required></td><td class="text-end text-nowrap pedido-importe-fila"></td><td><button class="btn btn-sm btn-outline-danger quitar-articulo" type="button" aria-label="Retirar artículo">×</button></td>`;
    row.querySelector(".pedido-nombre").textContent =
      producto?.nombre || `Artículo no disponible (#${id})`;
    row.querySelector(".pedido-tipo").textContent = producto
      ? producto.tipo + (producto.unidad ? " · " + producto.unidad : "")
      : "Retira este artículo o reactívalo antes de guardar.";
    row.querySelector(".pedido-cantidad").value = cantidad ?? 1;
    row.querySelector(".pedido-precio").value = Number(
      precio ?? producto?.precioventa ?? 0,
    ).toFixed(2);
    row.querySelector(".pedido-precio").min = esAdmin
      ? "0.01"
      : Number(producto?.precioventa || 0.01).toFixed(2);
    filas.set(id, row);
    $("detallePedido").appendChild(row);
  }
  totalizar();
  guardarBorrador();
}

function descuento() {
  const tipo = $("tipoDescuentoPedido")?.value || "NINGUNO";
  const valor = tipo === "NINGUNO" ? "0" : $("valorDescuentoPedido").value;
  const motivo =
    tipo === "NINGUNO" ? "" : $("motivoDescuentoPedido").value.trim();
  return { tipo, valor, motivo };
}
function totalizar() {
  let subtotal = 0;
  let valido = filas.size > 0;
  for (const [id, row] of filas) {
    const cantidad = Number(row.querySelector(".pedido-cantidad").value);
    const precioTexto = row.querySelector(".pedido-precio").value;
    const precio = Math.round(Number(precioTexto) * 100);
    const producto = catalogo.get(id);
    if (
      !producto ||
      !cantidadCorrecta(cantidad, decimal(producto)) ||
      !/^\d{1,8}(?:\.\d{1,2})?$/.test(precioTexto) ||
      precio <= 0
    )
      valido = false;
    const cantidadCentimos = Math.round(cantidad * 100);
    const importe = Number.isFinite(cantidadCentimos * precio)
      ? Math.floor((cantidadCentimos * precio + 50) / 100)
      : 0;
    subtotal += importe;
    row.querySelector(".pedido-importe-fila").textContent = money(
      importe / 100,
    );
  }
  const d = descuento();
  let rebaja = 0;
  if (d.tipo !== "NINGUNO") {
    const valor = Math.round(Number(d.valor) * 100);
    if (
      !/^\d{1,8}(?:\.\d{1,2})?$/.test(d.valor) ||
      valor <= 0 ||
      d.motivo.length < 5 ||
      d.motivo.length > 300 ||
      (d.tipo === "PORCENTAJE" && valor > 10000)
    )
      valido = false;
    rebaja =
      d.tipo === "PORCENTAJE"
        ? Math.floor((subtotal * valor + 5000) / 10000)
        : valor;
    if (!Number.isFinite(rebaja)) rebaja = 0;
  }
  if (subtotal > 9999999999 || rebaja >= subtotal) valido = false;
  $("subtotalPedido").textContent = money(subtotal / 100);
  $("descuentoPedido").textContent = "- " + money(rebaja / 100);
  $("totalPedido").textContent = money(Math.max(0, subtotal - rebaja) / 100);
  $("pedidoVacio").hidden = filas.size > 0;
  calculoValido = valido;
  $("guardarPedido").disabled = enviando || !valido || !form.checkValidity();
}

function datosActuales() {
  return {
    idpersona: $("clientePedido").value,
    idlocal: $("localPedido").value,
    observaciones: $("observacionesPedido").value,
    tipodescuento: descuento().tipo,
    valordescuento: descuento().valor,
    motivodescuento: descuento().motivo,
    detalles: [...filas].map(([id, row]) => ({
      idproducto: id,
      cantidad: row.querySelector(".pedido-cantidad").value,
      precioventa: row.querySelector(".pedido-precio").value,
    })),
  };
}
function guardarBorrador() {
  if (restaurando) return;
  modificado = true;
  if (config.dataset.id !== "0") return;
  try {
    sessionStorage.setItem(
      draftKey,
      JSON.stringify({ fecha: Date.now(), datos: datosActuales() }),
    );
  } catch (e) {
    console.warn(e);
  }
}
function cambiarDescuento() {
  if (!$("tipoDescuentoPedido")) return;
  const activo = $("tipoDescuentoPedido").value !== "NINGUNO";
  $("camposDescuentoPedido").hidden = !activo;
  $("valorDescuentoPedido").disabled = !activo;
  $("motivoDescuentoPedido").disabled = !activo;
  $("valorDescuentoPedido").required = activo;
  $("motivoDescuentoPedido").required = activo;
  if (!activo) {
    $("valorDescuentoPedido").value = "0";
    $("motivoDescuentoPedido").value = "";
  }
  if (activo) $("apartadoDescuentoPedido").open = true;
}
$("tipoDescuentoPedido")?.addEventListener("change", cambiarDescuento);
$("detallePedido").addEventListener("click", (event) => {
  const boton = event.target.closest(".quitar-articulo");
  if (!boton) return;
  const row = boton.closest("tr");
  filas.delete(row.dataset.id);
  row.remove();
  totalizar();
  guardarBorrador();
});
["input", "change"].forEach((tipo) =>
  form.addEventListener(tipo, () => {
    totalizar();
    guardarBorrador();
  }),
);
let inicial = JSON.parse(config.dataset.inicial);
if (!Object.keys(inicial).length && config.dataset.id === "0") {
  try {
    const draft = JSON.parse(sessionStorage.getItem(draftKey) || "null");
    if (draft && Date.now() - draft.fecha < 86400000 && draft.datos) {
      inicial = draft.datos;
      $("avisoBorradorPedido").hidden = false;
    }
  } catch (e) {
    console.warn(e);
  }
}
for (const [nombre, id] of Object.entries({
  idpersona: "clientePedido",
  idlocal: "localPedido",
  observaciones: "observacionesPedido",
  tipodescuento: "tipoDescuentoPedido",
  valordescuento: "valorDescuentoPedido",
  motivodescuento: "motivoDescuentoPedido",
})) {
  if ($(id) && inicial[nombre] != null) $(id).value = inicial[nombre];
}
clienteElegido();
cambiarDescuento();
for (const linea of Object.values(inicial.detalles || {}))
  agregar(linea.idproducto, linea.cantidad, linea.precioventa);
restaurando = false;
totalizar();
$("limpiarPedido").addEventListener("click", () => {
  sessionStorage.removeItem(draftKey);
  modificado = false;
  window.location.assign(window.location.pathname);
});
form.addEventListener("submit", (event) => {
  if (enviando || !calculoValido || !filas.size) {
    event.preventDefault();
    return error("Revisa los artículos, precios y descuento antes de guardar.");
  }
  enviando = true;
  $("guardarPedido").disabled = true;
  $("guardarPedido").textContent = "Guardando pedido…";
});
window.addEventListener("beforeunload", (event) => {
  if (modificado && !enviando) {
    event.preventDefault();
    event.returnValue = "";
  }
});
window.addEventListener("pageshow", () => {
  enviando = false;
  $("guardarPedido").innerHTML = "Guardar pedido <kbd>F9</kbd>";
  totalizar();
});
document.addEventListener("keydown", (event) => {
  if (
    event.repeat ||
    event.ctrlKey ||
    event.altKey ||
    event.metaKey ||
    !["F2", "F4", "F9"].includes(event.key)
  )
    return;
  event.preventDefault();
  if (event.key === "F2") $("buscarArticuloPedido").focus();
  if (event.key === "F4") $("buscarClientePedido").focus();
  if (event.key === "F9" && !$("guardarPedido").disabled)
    form.requestSubmit($("guardarPedido"));
});
