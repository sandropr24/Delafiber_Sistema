import { $, $$, leer, money, alertar } from "./modules/utils.js";
import {
  inicializarInventario,
  mostrarDisponibilidad,
  validarDetalleVenta,
} from "./modules/inventario.js";
import {
  inicializarCarrito,
  filas,
  descuentoVenta,
  totalizar,
} from "./modules/carrito.js";
import { inicializarPagos, actualizarConfirmacion } from "./modules/pagos.js";
import {
  inicializarClienteBorrador,
  restaurarBorrador,
  guardarBorrador,
} from "./modules/cliente-borrador.js";

function iniciarVenta() {
  const combosRaw = leer("combos", []);
  const combos = Array.isArray(combosRaw)
    ? combosRaw.reduce((acc, componente) => {
        (acc[String(componente.idcombo)] ||= []).push(componente);
        return acc;
      }, {})
    : combosRaw || {};
  const estado = {
    stock: leer("stock"),
    series: leer("series"),
    combos,
    activo: null,
    local: $("selectLocal"),
    carrito: $("carritoBody"),
    buscador: $("buscarArticulo"),
    selectores: [$("selectProducto"), $("selectServicio"), $("selectCombo")],
  };
  inicializarInventario(estado, filas);
  inicializarCarrito(estado);
  inicializarPagos({ filas, descuentoVenta });
  inicializarClienteBorrador(estado);
  restaurarBorrador();
  mostrarDisponibilidad();
  totalizar();
  document.addEventListener("keydown", (event) => {
    if (
      event.repeat ||
      event.ctrlKey ||
      event.altKey ||
      event.metaKey ||
      document.querySelector(".modal.show, .swal2-container")
    )
      return;
    if (!["F2", "F4", "F9"].includes(event.key)) return;
    event.preventDefault();
    if (event.key === "F2") estado.buscador.focus();
    if (event.key === "F4") $("buscarClienteVenta").focus();
    if (event.key === "F9") {
      actualizarConfirmacion();
      if (!$("btnConfirmarVenta").disabled)
        $("formVenta").requestSubmit($("btnConfirmarVenta"));
    }
  });
  let enviando = false;
  window.addEventListener("pageshow", () => {
    enviando = false;
    $("btnConfirmarVenta").dataset.sending = "0";
    totalizar();
  });
  $("formVenta")?.addEventListener("submit", (event) => {
    if (enviando || $("btnGuardarClienteExpress")?.disabled) {
      event.preventDefault();
      return;
    }
    $("mensajeVenta").classList.add("d-none");
    if (!validarDetalleVenta(event)) return;
    if (!$("selectCliente")?.value || $("selectCliente").value === "false") {
      event.preventDefault();
      return alertar("Cliente requerido", "Seleccione un cliente válido.");
    }
    const validacionDescuento = descuentoVenta();
    if (validacionDescuento.error) {
      event.preventDefault();
      return alertar("Descuento inválido", validacionDescuento.error);
    }
    const total = Number($("inputTotal").value || 0);
    const pagado = $$(".input-monto-pago").reduce(
      (sum, input) => sum + Number(input.value || 0),
      0,
    );
    if (total <= 0) {
      event.preventDefault();
      return alertar("Carrito vacío", "Debe agregar al menos un artículo.");
    }
    if ($("tipoentrega")?.value === "DOMICILIO") {
      if (!$("direccion_entrega")?.value.trim()) {
        event.preventDefault();
        return alertar(
          "Dirección requerida",
          "Ingrese la dirección de entrega.",
        );
      }
      if (!$("telefono_entrega")?.value.trim()) {
        event.preventDefault();
        return alertar(
          "Teléfono requerido",
          "Ingrese el teléfono de contacto.",
        );
      }
    }
    if (Math.round(pagado * 100) !== Math.round(total * 100)) {
      event.preventDefault();
      return alertar(
        "Pago incorrecto",
        `Los pagos (${money(pagado)}) deben coincidir con el total (${money(total)}).`,
        "error",
      );
    }
    for (const input of $$(".pago-recibido")) {
      if (
        Number(input.value) <
        Number(input.closest("tr").querySelector(".input-monto-pago").value)
      ) {
        event.preventDefault();
        return alertar(
          "Efectivo insuficiente",
          "El recibido debe cubrir el monto aplicado.",
        );
      }
    }
    guardarBorrador();
    enviando = true;
    $("btnConfirmarVenta").dataset.sending = "1";
    $("btnConfirmarVenta").disabled = true;
    $("textoConfirmarVenta").textContent = "Registrando venta…";
  });
}

if (document.readyState === "loading")
  document.addEventListener("DOMContentLoaded", iniciarVenta, { once: true });
else iniciarVenta();
