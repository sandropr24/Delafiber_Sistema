import { normalizar } from './utils.js';

export function crearBusqueda(
  input,
  lista,
  opciones,
  seleccionar,
  mostrarVacio = false,
) {
  let resultados = [],
    indice = -1;
  const cerrar = () => {
    lista.hidden = true;
    input.setAttribute("aria-expanded", "false");
    input.removeAttribute("aria-activedescendant");
  };
  function marcar() {
    [...lista.children].forEach((nodo, i) =>
      nodo.setAttribute("aria-selected", String(i === indice)),
    );
    if (indice >= 0) {
      input.setAttribute("aria-activedescendant", `${lista.id}-${indice}`);
      lista.children[indice]?.scrollIntoView({ block: "nearest" });
    }
  }
  function actualizar() {
    const texto = normalizar(input.value.trim());
    if (!texto && !mostrarVacio) return cerrar();
    resultados = opciones().filter((x) =>
      normalizar(`${x.label} ${x.codigo || ""} ${x.value || ""}`).includes(
        texto,
      ),
    );
    resultados.sort(
      (a, b) =>
        Number(normalizar(b.codigo || "") === texto) -
        Number(normalizar(a.codigo || "") === texto),
    );
    resultados = resultados.slice(0, 15);
    indice = resultados.length ? 0 : -1;
    lista.replaceChildren();
    resultados.forEach((item, i) => {
      const boton = document.createElement("button");
      boton.type = "button";
      boton.id = `${lista.id}-${i}`;
      boton.setAttribute("role", "option");
      boton.tabIndex = -1;
      boton.textContent = item.label;
      boton.addEventListener("mousedown", (e) => e.preventDefault());
      boton.addEventListener("click", () => {
        seleccionar(item);
        cerrar();
      });
      lista.appendChild(boton);
    });
    if (!resultados.length) {
      const mensaje = document.createElement("div");
      mensaje.className = "venta-sin-resultados";
      mensaje.textContent = "Sin coincidencias";
      lista.appendChild(mensaje);
    }
    lista.hidden = false;
    input.setAttribute("aria-expanded", "true");
    marcar();
  }
  input.addEventListener("input", actualizar);
  input.addEventListener("focus", actualizar);
  input.addEventListener("blur", () => setTimeout(cerrar, 150));
  input.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      cerrar();
      return;
    }
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      e.preventDefault();
      if (lista.hidden) actualizar();
      if (resultados.length) {
        indice =
          (indice + (e.key === "ArrowDown" ? 1 : -1) + resultados.length) %
          resultados.length;
        marcar();
      }
    }
    if (e.key === "Enter") {
      e.preventDefault();
      if (lista.hidden) actualizar();
      if (indice >= 0 && resultados[indice]) {
        seleccionar(resultados[indice]);
        cerrar();
      }
    }
  });
  return { cerrar, actualizar };
}
