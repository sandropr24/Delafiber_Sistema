import { $, $$, money, normalizar, alertar } from './utils.js';
import { cantidadValida } from './inventario.js';

let filas, descuentoVenta;
const avisoPagos =
  '<tr id="filaSinPagos"><td colspan="3" class="text-center text-muted py-2 small">Sin pagos asignados.</td></tr>';

export function totalizarPagos() {
  const total = $$(".input-monto-pago").reduce(
    (sum, input) => sum + Number(input.value || 0),
    0,
  );
  $("lblTotalPagado").textContent = money(total);
  const diferencia = total - Number($("inputTotal").value || 0);
  const texto = $("lblFaltanteVuelto");
  texto.className =
    diferencia >= 0 ? "text-info fw-bold" : "text-danger fw-bold";
  texto.textContent =
    diferencia > 0
      ? "Exceso aplicado: " + money(diferencia)
      : diferencia < 0
        ? "Pendiente: " + money(-diferencia)
        : "Pago completo";
  const vuelto = $$(".pago-recibido").reduce(
    (sum, input) =>
      sum +
      Math.max(
        0,
        Number(input.value || 0) -
          Number(
            input.closest("tr").querySelector(".input-monto-pago").value || 0,
          ),
      ),
    0,
  );
  $("lblVuelto").textContent = money(vuelto);
  previsualizarVuelto();
  $("estadoCobro").textContent =
    diferencia === 0 && total > 0
      ? "Pago completo. Vuelto en efectivo: " + money(vuelto)
      : "Complete el monto exacto antes de registrar.";
  actualizarConfirmacion();
}
export function actualizarConfirmacion() {
  const form = $("formVenta");
  const total = Math.round(Number($("inputTotal").value || 0) * 100);
  const pagado = $$(".input-monto-pago").reduce(
    (sum, input) => sum + Math.round(Number(input.value || 0) * 100),
    0,
  );
  const recibidoValido = $$(".pago-recibido").every(
    (input) =>
      Number(input.value) >=
      Number(input.closest("tr").querySelector(".input-monto-pago").value),
  );
  const descuentoError = descuentoVenta().error;
  const errorInventario = filas().map(row => cantidadValida(row, Number(row.querySelector('.input-cantidad').value))).find(Boolean);
  const formularioValido = form.checkValidity();
  $("btnConfirmarVenta").disabled =
    $("btnConfirmarVenta").dataset.sending === "1" ||
    !filas().length ||
    total <= 0 ||
    pagado !== total ||
    !recibidoValido ||
    !formularioValido ||
    !!descuentoError ||
    !!errorInventario;
  const etiquetas = { idlinea: 'Línea', idcanal: 'Canal', idestado: 'Estado', idcliente: 'Cliente', buscarClienteVenta: 'Cliente', direccion_entrega: 'Dirección de entrega', telefono_entrega: 'Teléfono de entrega' };
  const pendientes = [...new Set([...form.elements]
    .filter(campo => campo.willValidate && !campo.validity.valid)
    .map(campo => etiquetas[campo.name] || etiquetas[campo.id] ||
      (campo.classList.contains('selector-serie') ? 'Series de los artículos' : campo.labels?.[0]?.textContent.trim() || 'Datos del pago o artículo')))];
  const avisos = [];
  if (pendientes.length) avisos.push('Falta completar o revisar: ' + pendientes.join(', ') + '.');
  if (descuentoError) avisos.push(descuentoError);
  if (errorInventario) avisos.push(errorInventario);
  if (!recibidoValido) avisos.push('El efectivo recibido debe cubrir el monto aplicado.');
  let estado;
  if (!filas().length || total <= 0) estado = 'Agregue artículos para registrar la venta.';
  else {
    estado = pagado === total
      ? 'Pago completo. Vuelto en efectivo: ' + $('lblVuelto').textContent + '.'
      : pagado < total ? 'Pendiente de pago: ' + money((total - pagado) / 100) + '. Agregue el pago para continuar.'
      : 'Los pagos superan el total. Revise los montos aplicados.';
    if (avisos.length) estado += ' ' + avisos.join(' ');
  }
  $('estadoCobro').textContent = estado;
  $('btnConfirmarVenta').title = $('btnConfirmarVenta').disabled ? estado : 'Registrar la venta y su pago';
}

export function saldoPendienteCentimos() {
  const pagado = $$(".input-monto-pago").reduce(
    (sum, x) => sum + Math.round(Number(x.value || 0) * 100),
    0,
  );
  return Math.max(
    0,
    Math.round(Number($("inputTotal").value || 0) * 100) - pagado,
  );
}
export function previsualizarVuelto() {
  const efectivo =
    normalizar($("selectTipoPago").selectedOptions[0]?.dataset.nombre) ===
    "efectivo";
  const seleccionado = !!$("selectTipoPago").value;
  $("campoReferenciaPago").hidden = !seleccionado || efectivo;
  $("campoRecibidoPago").hidden = !seleccionado || !efectivo;
  $("previewVuelto").hidden = !seleccionado || !efectivo;
  const monto = Math.round(Number($("inputMontoPago").value || 0) * 100);
  const aplicado = Math.min(monto, saldoPendienteCentimos());
  const recibido =
    $("inputRecibidoPago").value === ""
      ? monto
      : Math.round(Number($("inputRecibidoPago").value) * 100);
  $("previewVuelto").textContent = !efectivo
    ? "Solo el efectivo genera vuelto."
    : recibido < aplicado
      ? "El efectivo recibido no cubre el monto aplicado."
      : `Se aplicará ${money(aplicado / 100)} · Vuelto: ${money((recibido - aplicado) / 100)}`;
}

export function inicializarPagos(dependencias) {
  ({ filas, descuentoVenta } = dependencias);
  $("inputMontoPago").addEventListener("input", previsualizarVuelto);
  $("inputRecibidoPago").addEventListener("input", previsualizarVuelto);
  $("btnCompletarPago")?.addEventListener("click", () => {
    const pagado = $$(".input-monto-pago").reduce(
      (sum, x) => sum + Math.round(Number(x.value || 0) * 100),
      0,
    );
    $("inputMontoPago").value = (
      Math.max(0, Math.round(Number($("inputTotal").value) * 100) - pagado) /
      100
    ).toFixed(2);
    $("inputRecibidoPago").value = $("inputMontoPago").value;
    previsualizarVuelto();
  });
  $$('[data-efectivo]').forEach((boton) => {
    boton.addEventListener("click", () => {
      const efectivo = [...$("selectTipoPago").options].find(
        (opcion) => normalizar(opcion.dataset.nombre) === "efectivo",
      );
      if (!efectivo) return alertar("Pago en efectivo", "No hay un método de efectivo configurado.");
      $("selectTipoPago").value = efectivo.value;
      $("selectTipoPago").dispatchEvent(new Event("change"));
      $("inputMontoPago").value = (saldoPendienteCentimos() / 100).toFixed(2);
      $("inputRecibidoPago").value = Number(boton.dataset.efectivo).toFixed(2);
      previsualizarVuelto();
    });
  });
  $("selectTipoPago")?.addEventListener("change", () => {
    const efectivo =
      normalizar($("selectTipoPago").selectedOptions[0]?.dataset.nombre) ===
      "efectivo";
    $("inputReferenciaPago").disabled = efectivo;
    $("inputRecibidoPago").disabled = !efectivo;
    $("btnCompletarPago").click();
    $("inputRecibidoPago").value = "";
    previsualizarVuelto();
  });
  $("btnAgregarPago")?.addEventListener("click", () => {
    const tipo = $("selectTipoPago");
    let monto = Number($("inputMontoPago").value);
    if (!tipo.value)
      return alertar("Método requerido", "Seleccione un método de pago.");
    if (!Number.isFinite(monto) || monto <= 0)
      return alertar("Monto inválido", "Ingrese un monto mayor que cero.");
    if ($(`pago-${tipo.value}`))
      return alertar(
        "Método ya agregado",
        "Este método ya está agregado.",
        "info",
      );

    const efectivo =
      normalizar(tipo.selectedOptions[0].dataset.nombre) === "efectivo";
    const referencia = $("inputReferenciaPago").value.trim();
    const recibido =
      efectivo && $("inputRecibidoPago").value !== ""
        ? Number($("inputRecibidoPago").value)
        : monto;
    if (efectivo)
      monto = Math.min(Math.round(monto * 100), saldoPendienteCentimos()) / 100;
    if (monto <= 0)
      return alertar("Pago completo", "La venta ya no tiene saldo pendiente.");
    if (!efectivo && !referencia)
      return alertar(
        "Referencia requerida",
        "Ingrese la referencia de la operación.",
      );
    if (!Number.isFinite(recibido) || recibido < monto)
      return alertar(
        "Efectivo insuficiente",
        "El recibido debe cubrir el monto aplicado.",
      );
    const row = document.createElement("tr");
    row.id = `pago-${tipo.value}`;
    row.innerHTML = `
            <td><span class="fw-semibold text-dark small nombre-metodo"></span>
                <input type="hidden" name="pagos[${tipo.value}][idtipopago]" value="${tipo.value}"></td>
            <td class="text-end"><input type="number" name="pagos[${tipo.value}][monto]" value="${monto.toFixed(2)}"
                min="0.01" step="0.01" class="form-control form-control-sm text-end input-monto-pago"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-eliminar-pago">
                <i class="bi bi-trash3 fs-5"></i></button></td>`;

    row.querySelector(".nombre-metodo").textContent =
      tipo.selectedOptions[0].dataset.nombre;
    const celda = row.cells[0];
    const ref = document.createElement("input");
    ref.type = "text";
    ref.className = "form-control form-control-sm mt-1";
    ref.name = "pagos[" + tipo.value + "][referencia]";
    ref.value = referencia;
    ref.maxLength = 50;
    ref.placeholder = "Referencia";
    ref.required = !efectivo;
    ref.setAttribute("aria-label", "Referencia de operación");
    if (!efectivo) celda.appendChild(ref);
    if (efectivo) {
      const label = document.createElement("label");
      label.className = "small text-muted mt-1";
      label.textContent = "Efectivo recibido (S/)";
      celda.appendChild(label);
      const input = document.createElement("input");
      input.type = "number";
      input.step = "0.01";
      input.min = "0.01";
      input.className = "form-control form-control-sm pago-recibido";
      input.name = "pagos[" + tipo.value + "][montorecibido]";
      input.value = recibido.toFixed(2);
      input.setAttribute("aria-label", "Efectivo recibido");
      celda.appendChild(input);
    }
    $("inputReferenciaPago").value = "";
    $("inputRecibidoPago").value = "";
    $("filaSinPagos")?.remove();
    $("pagosBody").appendChild(row);
    tipo.value = "";
    $("inputMontoPago").value = "";
    totalizarPagos();
  });

  $("pagosBody")?.addEventListener("input", (event) => {
    if (event.target.matches(".input-monto-pago, .pago-recibido"))
      totalizarPagos();
  });

  $("pagosBody")?.addEventListener("click", (event) => {
    const boton = event.target.closest(".btn-eliminar-pago");
    if (!boton) return;
    boton.closest("tr").remove();
    if (!$$(".input-monto-pago", $("pagosBody")).length)
      $("pagosBody").innerHTML = avisoPagos;
    totalizarPagos();
  });
}
