
export const $ = (id) => document.getElementById(id);

export const $$ = (selector, root = document) => [
    ...root.querySelectorAll(selector),
];

export const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

let temporizadorAlertaVenta = null;

export const limpiarAlertaVenta = (soloInventario = false) => {
    const box = $("mensajeVenta");

    if (!box || (soloInventario && box.dataset.tipo !== "inventario")) return;

    box.textContent = "";
    box.classList.add("d-none");
    delete box.dataset.tipo;
};

export const alertar = (title, text) => {
    const box = $("mensajeVenta");
    if (!box) return;

    limpiarAlertaVenta();

    const mensaje = document.createElement("span");
    mensaje.textContent = `${title}: ${text}`;

    const cerrar = document.createElement("button");
    cerrar.type = "button";
    cerrar.className = "btn-close ms-2 float-end";
    cerrar.setAttribute("aria-label", "Cerrar aviso");
    cerrar.addEventListener("click", limpiarAlertaVenta);

    box.append(mensaje, cerrar);
    box.classList.remove("d-none");
    box.focus();

    temporizadorAlertaVenta = setTimeout(limpiarAlertaVenta, 6500);
};

export const alertarCliente = (title, text) => {
    if (window.Swal) {
        return Swal.fire({
            icon: "error",
            title,
            text,
            confirmButtonText: "Entendido",
            returnFocus: true,
            didClose: () => $("formClienteExpress").elements.documento.focus(),
        });
    }

    window.alert(`${title}: ${text}`);
};

export const admiteDecimal = (opcion) =>
    opcion?.dataset.unidad?.toUpperCase() === "M" &&
    opcion.dataset.controlaserie !== "1";

export const cantidadCorrecta = (cantidad, decimal) =>
    Number.isFinite(cantidad) &&
    cantidad > 0 &&
    cantidad <= 99999999.99 &&
    Math.abs(cantidad * 100 - Math.round(cantidad * 100)) < 0.00001 &&
    (decimal || Number.isInteger(cantidad));

export const leer = (name, fallback = {}) => {
    try {
        return JSON.parse(
            $("ventaConfig")?.getAttribute(`data-${name}`) ||
                JSON.stringify(fallback),
        );
    } catch (error) {
        console.error(`Datos inválidos: ${name}`, error);
        return fallback;
    }
};

export const normalizar = (texto) =>
    String(texto || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
