
import { $, $$, money, normalizar, admiteDecimal, cantidadCorrecta, alertar, limpiarAlertaVenta } from './utils.js';
import { crearBusqueda } from './buscador.js';
import { componentes, mostrarDisponibilidad, validarInventario, pintarSeries, cantidadValida } from './inventario.js';
import { totalizarPagos } from './pagos.js';

let estado, local, carrito, selectores, buscador, grupos, botonesFiltro;
let tipoVisible = 'todos';
let restaurandoArticulo = false;

export function restaurarArticulo(selector, cantidad) {
    restaurandoArticulo = true;
    try {
        selector.dispatchEvent(new Event('change'));
        $('inputCantidad').value = cantidad;
        $('btnAgregarItem').click();
    } finally {
        restaurandoArticulo = false;
    }
}

export const filas = () =>
    $$(".input-cantidad", carrito).map((input) => input.closest("tr"));

export const fila = (id) => $(`row-${id}`);

const avisoVacio =
    '<tr id="filaVacia"><td colspan="5" class="text-center text-muted py-5">No se han agregado productos a la venta.</td></tr>';

export function entrega() {
    const domicilio = $("tipoentrega")?.value === "DOMICILIO";
    $("camposEntrega")?.classList.toggle("d-none", !domicilio);

    [$("direccion_entrega"), $("telefono_entrega")].forEach((el) => {
        if (el) el.required = domicilio;
    });
}

function filtrarArticulos() {
    const texto = normalizar(buscador?.value.trim());
    let encontrados = 0;

    grupos.forEach((grupo) => {
        const tipo = grupo.dataset.tipo;
        const visible = tipoVisible === "todos" || tipoVisible === tipo;

        grupo.classList.toggle("d-none", !visible);

        const selector = grupo.querySelector("select");

        [...selector.options].forEach((opcion, i) => {
            if (!i) return;

            const coincide = normalizar(
                `${opcion.textContent} ${opcion.dataset.codigo || ""} ${opcion.value}`,
            ).includes(texto);

            opcion.hidden = !coincide;
            opcion.disabled = !coincide;

            if (coincide && visible) encontrados++;
        });

        if (selector.selectedOptions[0]?.disabled) {
            selector.value = "";

            if (estado.activo === selector) {
                estado.activo = null;
                $("inputPrecio").value = "";
            }
        }
    });

    $("resultadoBusqueda").textContent =
        `${encontrados} artículo(s) disponibles en el filtro.`;
}

export function descuentoVenta() {
    const tipo = $("tipoDescuento")?.value || "NINGUNO";
    const valorTexto = $("valorDescuento")?.value || "0";
    const valor = Number(valorTexto);
    const motivo = $("motivoDescuento")?.value.trim() || "";
    const activo = tipo !== "NINGUNO";

    const subtotalCentimos = filas().reduce((sum, row) => {
        const cantidad = Number(row.querySelector(".input-cantidad").value);
        const precio = Number(row.querySelector('[name$="[precioventa]"]').value);
        const cantidadCentimos = Math.round(cantidad * 100);
        const precioCentimos = Math.round(precio * 100);

        return sum + Math.floor((cantidadCentimos * precioCentimos + 50) / 100);
    }, 0);

    const error = $("errorDescuento");
    let mensaje = "";
    let centimos = 0;

    if (activo) {
        if (
            !Number.isFinite(valor) ||
            valor <= 0 ||
            !/^\d+(?:\.\d{1,2})?$/.test(valorTexto)
        ) {
            mensaje =
                "Ingresa un descuento válido mayor que cero (máximo dos decimales).";
        } else if (motivo.length < 5 || motivo.length > 300) {
            mensaje = "Indica un motivo de al menos 5 caracteres.";
        } else if (tipo === "PORCENTAJE") {
            if (valor > 100) {
                mensaje = "El porcentaje no puede superar 100 %.";
            } else {
                centimos = Math.round(
                    (subtotalCentimos * Math.round(valor * 100)) / 10000,
                );
            }
        } else {
            centimos = Math.round(valor * 100);
        }

        if (centimos >= subtotalCentimos && subtotalCentimos > 0) {
            mensaje = "El descuento debe ser menor que el subtotal.";
        }
    }

    if (error) {
        error.textContent = mensaje;
        error.classList.toggle("d-none", !mensaje);
    }

    return {
        subtotalCentimos,
        descuentoCentimos: centimos,
        error: mensaje
    };
}

export function totalizar() {
    const calculo = descuentoVenta();
    const subtotal = calculo.subtotalCentimos / 100;
    const descuento = calculo.descuentoCentimos / 100;
    const total = Math.max(0, subtotal - descuento);

    $("lblSubtotal").textContent = money(subtotal);
    $("lblDescuento").textContent = `- ${money(descuento)}`;
    $("lblTotal").textContent = money(total);
    $("inputTotal").value = total.toFixed(2);
    $("contadorArticulos").textContent = filas().length + " artículos";
    $("textoConfirmarVenta").textContent = "Registrar venta · " + money(total);

    totalizarPagos();
}

export function actualizarFila(row) {
    const input = row.querySelector(".input-cantidad");
    const cantidad = Number(input.value);
    const error = cantidadValida(row, cantidad);

    if (error) {
        alertar(
            row.dataset.combo === "1" ? "Combo no disponible" : "Cantidad inválida",
            error,
        );

        input.value = row.dataset.ultimaCantidad || 1;

        row.querySelector(".lbl-subtotal").textContent = money(
            Number(input.value) *
            Number(row.querySelector('[name$="[precioventa]"]').value),
        );

        totalizar();
        return;
    }

    limpiarAlertaVenta(true);

    row.dataset.ultimaCantidad = String(cantidad);
    pintarSeries(row, row.dataset.idproducto, cantidad);

    row.querySelector(".lbl-subtotal").textContent = money(
        cantidad * Number(row.querySelector('[name$="[precioventa]"]').value),
    );

    totalizar();
}

export function inicializarCarrito(compartido) {
    estado = compartido;
    ({ local, carrito, selectores, buscador } = compartido);

    grupos = $$('.articulo-grupo');
    botonesFiltro = $$('#filtrosArticulos [data-tipo]');

    const busquedaArticulos = crearBusqueda(
        buscador,
        $("resultadosArticulos"),
        () =>
            selectores
                .filter(
                    (s) => !s.closest(".articulo-grupo").classList.contains("d-none"),
                )
                .flatMap((selector) =>
                    [...selector.options]
                        .filter((x) => x.value)
                        .map((opcion) => ({
                            selector,
                            opcion,
                            value: opcion.value,
                            codigo: opcion.dataset.codigo || opcion.value,
                            label: `${opcion.dataset.nombre || opcion.textContent.trim()} · S/ ${Number(opcion.dataset.precio).toFixed(2)}`,
                        })),
                ),
        (item) => {
            item.selector.value = item.opcion.value;
            item.selector.dispatchEvent(new Event("change"));
            $("inputCantidad").value = "1";
            $("btnAgregarItem").click();

            if (!estado.activo) {
                buscador.value = "";
                filtrarArticulos();
                buscador.focus();
            }
        },
    );

    buscador?.addEventListener("input", filtrarArticulos);

    botonesFiltro.forEach((boton) =>
        boton.addEventListener("click", () => {
            tipoVisible = boton.dataset.tipo;

            botonesFiltro.forEach((b) => {
                b.classList.toggle("btn-primary", b === boton);
                b.classList.toggle("btn-outline-secondary", b !== boton);
            });

            filtrarArticulos();
        }),
    );

    filtrarArticulos();

    $("tipoentrega")?.addEventListener("change", entrega);
    entrega();

    selectores.forEach((selector) =>
        selector?.addEventListener("change", () => {
            if (!selector.value) return;

            estado.activo = selector;

            const decimal = admiteDecimal(selector.selectedOptions[0]);

            $("inputCantidad").step = decimal ? "0.01" : "1";
            $("inputCantidad").min = decimal ? "0.01" : "1";
            $("inputCantidad").value = "1";

            mostrarDisponibilidad();

            selectores
                .filter((otro) => otro !== selector)
                .forEach((otro) => {
                    if (otro) otro.value = "";
                });

            $("inputPrecio").value = Number(
                selector.selectedOptions[0]?.dataset.precio || 0,
            ).toFixed(2);
        }),
    );

    local?.addEventListener("change", () => {
        mostrarDisponibilidad();

        if (!filas().length) return;

        carrito.innerHTML = avisoVacio;
        totalizar();

        alertar(
            "Local actualizado",
            "Se limpiaron los artículos porque el inventario y las series pertenecen a un local específico.",
            "info",
        );
    });

    $("tipoDescuento")?.addEventListener("change", () => {
        const activo = $("tipoDescuento").value !== "NINGUNO";

        if (activo) {
            $("tipoDescuento").closest("details").open = true;
        }

        $("campoValorDescuento").hidden = !activo;
        $("campoMotivoDescuento").hidden = !activo;
        $("valorDescuento").disabled = !activo;
        $("motivoDescuento").disabled = !activo;
        $("valorDescuento").required = activo;
        $("motivoDescuento").required = activo;

        if (!activo) {
            $("valorDescuento").value = "0.00";
            $("motivoDescuento").value = "";
        }

        totalizar();
    });

    $("valorDescuento")?.addEventListener("input", totalizar);
    $("motivoDescuento")?.addEventListener("input", totalizar);

    $("btnAgregarItem")?.addEventListener("click", () => {
        if (!local?.value) {
            return alertar(
                "Local requerido",
                "Seleccione un local antes de agregar artículos.",
            );
        }

        if (!estado.activo?.value) {
            return alertar(
                "Artículo requerido",
                "Seleccione un producto, servicio o combo.",
            );
        }

        const opcion = estado.activo.selectedOptions[0];
        const id = estado.activo.value;
        const cantidad = Number($("inputCantidad").value);
        const precio = Number(opcion.dataset.precio);
        const esCombo = opcion.dataset.combo === "1";
        const controlaStock = opcion.dataset.controlastock === "1";
        const controlaSerie = opcion.dataset.controlaserie === "1";
        const existente = fila(id);

        const acumulada =
            cantidad + Number(existente?.querySelector(".input-cantidad").value || 0);

        if (!cantidadCorrecta(cantidad, admiteDecimal(opcion))) {
            return alertar(
                "Cantidad inválida",
                "Ingrese una cantidad positiva; solo los productos en metros admiten hasta dos decimales.",
            );
        }

        if (!Number.isFinite(precio) || precio <= 0) {
            return alertar(
                "Precio inválido",
                "Ingrese un precio válido mayor que cero.",
            );
        }

        const error = validarInventario(id, acumulada, esCombo, controlaStock);

        if (error && !restaurandoArticulo) {
            return alertar("Inventario insuficiente", error);
        }

        if (existente) {
            const anterior = Number(existente.querySelector(".input-cantidad").value);

            existente.querySelector(".input-cantidad").value = acumulada;

            const errorCantidad = cantidadValida(existente, acumulada);

            if (errorCantidad) {
                existente.querySelector(".input-cantidad").value = anterior;
                return alertar("Artículo no disponible", errorCantidad);
            }

            actualizarFila(existente);
        } else {
            const row = document.createElement("tr");

            row.id = `row-${id}`;
            row.dataset.idproducto = id;
            row.dataset.combo = esCombo ? "1" : "0";
            row.dataset.controlastock = controlaStock ? "1" : "0";
            row.dataset.controlaserie = controlaSerie ? "1" : "0";
            row.dataset.ultimaCantidad = String(cantidad);
            row.dataset.decimal = admiteDecimal(opcion) ? "1" : "0";
            row.dataset.precioCatalogo = String(precio);
            row.dataset.ultimoPrecio = String(precio);

            row.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark nombre-producto"></span>
                    <input type="hidden" name="detalles[${id}][idproducto]" value="${id}">
                    <div class="series-seleccionadas mt-2"></div>
                </td>
                <td class="text-center">
                    <input type="number" name="detalles[${id}][cantidad]" value="${cantidad}"
                        min="${admiteDecimal(opcion) ? "0.01" : "1"}"
                        step="${admiteDecimal(opcion) ? "0.01" : "1"}"
                        class="form-control form-control-sm text-center input-cantidad">
                </td>
                <td class="text-end text-secondary">
                    <input type="number" class="form-control form-control-sm text-end input-precio"
                        aria-label="Precio unitario"
                        name="detalles[${id}][precioventa]"
                        min="${$("tipoDescuento") ? "0.01" : precio.toFixed(2)}"
                        step="0.01" max="99999999.99" required
                        value="${precio.toFixed(2)}">
                </td>
                <td class="text-end fw-bold text-dark lbl-subtotal">
                    ${money(cantidad * precio)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </td>`;

            row.querySelector(".nombre-producto").textContent =
                opcion.dataset.nombre || opcion.textContent.trim();

            const etiqueta = document.createElement("small");
            etiqueta.className = "badge bg-light text-secondary border mt-1";

            etiqueta.textContent = esCombo
                ? "Combo"
                : estado.activo.id === "selectServicio"
                    ? "Servicio"
                    : "Producto · " + (opcion.dataset.unidad || "UND");

            row.cells[0].insertBefore(
                etiqueta,
                row.querySelector(".series-seleccionadas"),
            );

            if (esCombo) {
                const descripcion = document.createElement("small");
                descripcion.className = "d-block text-muted mt-1";

                descripcion.textContent = componentes(id)
                    .map((p) => `${p.cantidad} × ${p.nombre}`)
                    .join(" · ");

                row.cells[0].insertBefore(
                    descripcion,
                    row.querySelector(".series-seleccionadas"),
                );
            }

            carrito.appendChild(row);

            const errorCantidad = cantidadValida(row, cantidad);

            if (errorCantidad && !restaurandoArticulo) {
                row.remove();

                if (!filas().length) {
                    carrito.innerHTML = avisoVacio;
                }

                return alertar("Artículo no disponible", errorCantidad);
            }

            $("filaVacia")?.remove();

            pintarSeries(row, id, cantidad);
            totalizar();
        }

        estado.activo.value = "";
        estado.activo = null;
        $("inputCantidad").value = 1;
        $("inputPrecio").value = "";
    });

    carrito?.addEventListener("change", (event) => {
        if (event.target.matches(".input-precio")) {
            const input = event.target;
            const row = input.closest("tr");

            if (
                !input.checkValidity() ||
                !/^\d{1,8}(?:\.\d{1,2})?$/.test(input.value)
            ) {
                input.value = Number(row.dataset.ultimoPrecio).toFixed(2);

                alertar(
                    "Precio inválido",
                    "Ingrese un precio positivo con hasta dos decimales. Solo un administrador puede reducir el precio de catálogo.",
                );
            }

            row.dataset.ultimoPrecio = input.value;

            row.querySelector(".lbl-subtotal").textContent = money(
                Number(input.value) *
                Number(row.querySelector(".input-cantidad").value),
            );

            totalizar();
        }

        if (event.target.matches(".input-cantidad")) {
            actualizarFila(event.target.closest("tr"));
        }
    });

    carrito?.addEventListener("input", (event) => {
        if (event.target.matches(".input-precio")) {
            const row = event.target.closest("tr");

            row.querySelector(".lbl-subtotal").textContent = money(
                Number(event.target.value || 0) *
                Number(row.querySelector(".input-cantidad").value),
            );

            totalizar();
            return;
        }

        if (!event.target.matches(".input-cantidad")) return;

        const row = event.target.closest("tr");
        const cantidad = Number(event.target.value);

        if (!cantidadValida(row, cantidad)) {
            limpiarAlertaVenta(true);

            row.querySelector(".lbl-subtotal").textContent = money(
                cantidad * Number(row.querySelector('[name$="[precioventa]"]').value),
            );

            totalizar();
        }
    });

    carrito?.addEventListener("click", (event) => {
        const boton = event.target.closest(".btn-eliminar-fila");
        if (!boton) return;

        boton.closest("tr").remove();

        if (!filas().length) {
            carrito.innerHTML = avisoVacio;
        }

        totalizar();
    });
}