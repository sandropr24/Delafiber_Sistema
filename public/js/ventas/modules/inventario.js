import { $, $$, cantidadCorrecta, alertar } from './utils.js';

let estado, stock, series, combos, local, carrito, filas;
export function inicializarInventario(compartido, obtenerFilas) {
  estado = compartido;
  ({ stock, series, combos, local, carrito } = compartido);
  filas = obtenerFilas;
}

export const unidades = (id) => series[local?.value]?.[id] || [];
export const disponibles = (id) => Number(stock[local?.value]?.[id] ?? 0);
export const componentes = (id) => (Array.isArray(combos[id]) ? combos[id] : []);
export function mostrarDisponibilidad() {
  const opcion = estado.activo?.selectedOptions[0];
  const texto = $("disponibilidadArticulo");
  if (!local?.value) {
    texto.textContent = "Seleccione un local para consultar existencias.";
    return;
  }
  if (!opcion?.value) {
    texto.textContent = `Local: ${local.selectedOptions[0].textContent.trim()} · Busque un artículo para consultar existencias.`;
    return;
  }
  const partes =
    opcion.dataset.combo === "1"
      ? componentes(opcion.value)
      : [
          {
            idproducto: opcion.value,
            nombre: opcion.dataset.nombre,
            controlastock: opcion.dataset.controlastock,
          },
        ];
  texto.textContent = partes
    .map((p) =>
      Number(p.controlastock) === 1
        ? (p.nombre || opcion.dataset.nombre) +
          ": " +
          disponibles(p.idproducto) +
          " disponibles"
        : (p.nombre || opcion.dataset.nombre) + ": sin control de stock",
    )
    .join(" · ");
}
export function consumo(id, cantidad, esCombo, controlaStock = false) {
  const requerido = {};
  const partes = esCombo
    ? componentes(id)
    : [{ idproducto: id, cantidad: 1, controlastock: controlaStock ? 1 : 0 }];
  for (const p of partes) {
    if (
      String(p.tipo || "").toUpperCase() === "SERVICIO" ||
      Number(p.controlastock) !== 1
    )
      continue;
    const pid = String(p.idproducto);
    requerido[pid] = (requerido[pid] || 0) + Number(p.cantidad) * cantidad;
  }
  return requerido;
}

export function validarInventario(id, cantidad, esCombo, controlaStock = false) {
  if (esCombo && !componentes(id).length)
    return "Este combo no tiene componentes configurados.";
  if (
    esCombo &&
    componentes(id).some(
      (p) => String(p.tipo || "").toUpperCase() === "COMBO",
    )
  ) {
    return "Un combo no puede contener otro combo.";
  }

  const total = {};
  const sumar = (datos) =>
    Object.entries(datos).forEach(([pid, n]) => {
      total[pid] = (total[pid] || 0) + n;
    });

  filas().forEach((row) => {
    if (row.dataset.idproducto === String(id)) return;
    sumar(
      consumo(
        row.dataset.idproducto,
        Number(row.querySelector(".input-cantidad").value),
        row.dataset.combo === "1",
        row.dataset.controlastock === "1",
      ),
    );
  });

  sumar(consumo(id, cantidad, esCombo, controlaStock));

  for (const [pid, n] of Object.entries(total)) {
    if (n > disponibles(pid) + 0.00001) {
      return `El producto ${pid} necesita ${n} unidades y solo hay ${disponibles(pid)} disponibles.`;
    }
  }
  return "";
}

export function partesConSerie(row, cantidad) {
  if (row.dataset.combo !== "1") {
    return row.dataset.controlaserie === "1"
      ? [
          {
            idproducto: row.dataset.idproducto,
            nombre: row.querySelector(".nombre-producto").textContent,
            cantidad,
          },
        ]
      : [];
  }

  const agrupadas = {};
  componentes(row.dataset.idproducto)
    .filter((p) => Number(p.controlaserie) === 1)
    .forEach((p) => {
      const pid = String(p.idproducto);
      if (!agrupadas[pid]) {
        agrupadas[pid] = {
          idproducto: pid,
          nombre: p.nombre || `Producto ${pid}`,
          cantidad: 0,
        };
      }
      agrupadas[pid].cantidad += Number(p.cantidad) * cantidad;
    });
  return Object.values(agrupadas);
}

export function pintarSeries(row, id, cantidad) {
  const destino = row.querySelector(".series-seleccionadas");
  if (!destino) return;

  const anteriores = {};
  $$(".selector-serie", destino).forEach((el) => {
    (anteriores[el.dataset.producto] ||= []).push(el.value);
  });
  destino.replaceChildren();

  partesConSerie(row, cantidad).forEach((parte) => {
    const bloque = document.createElement("div");
    bloque.className = "mt-2";

    const titulo = document.createElement("div");
    titulo.className = "small text-secondary fw-semibold";
    titulo.textContent = `Series: ${parte.nombre}`;
    bloque.appendChild(titulo);

    for (let i = 0; i < parte.cantidad; i++) {
      const selector = document.createElement("select");
      selector.name =
        row.dataset.combo === "1"
          ? `detalles[${id}][series_componentes][${parte.idproducto}][]`
          : `detalles[${id}][series][]`;
      selector.className = "form-select form-select-sm mt-1 selector-serie";
      selector.dataset.producto = parte.idproducto;
      selector.required = true;
      selector.add(new Option(`Serie ${i + 1}...`, ""));

      unidades(parte.idproducto).forEach((item) => {
        selector.add(new Option(item.numero, String(item.id)));
      });
      selector.value = anteriores[parte.idproducto]?.[i] || "";
      bloque.appendChild(selector);
    }
    destino.appendChild(bloque);
  });
}

export function cantidadValida(row, cantidad) {
  const id = row.dataset.idproducto;
  if (!cantidadCorrecta(cantidad, row.dataset.decimal === "1"))
    return "Ingrese una cantidad positiva; solo los productos en metros admiten hasta dos decimales.";

  const error = validarInventario(
    id,
    cantidad,
    row.dataset.combo === "1",
    row.dataset.controlastock === "1",
  );
  if (error) return error;

  for (const p of partesConSerie(row, cantidad)) {
    if (
      !Number.isInteger(p.cantidad) ||
      p.cantidad < 1 ||
      p.cantidad > unidades(p.idproducto).length
    ) {
      return `No hay suficientes series disponibles para ${p.nombre}.`;
    }
  }
  return "";
}


export function validarDetalleVenta(event) {
  for (const row of filas()) {
    const cantidad = Number(row.querySelector(".input-cantidad").value);
    const error = cantidadValida(row, cantidad);
    if (error) {
      event.preventDefault();
      return alertar("Inventario", error);
    }

    const esperadas = partesConSerie(row, cantidad).reduce(
      (n, p) => n + p.cantidad,
      0,
    );
    if ($$(".selector-serie", row).length !== esperadas) {
      event.preventDefault();
      return alertar(
        "Series",
        "Confirma la cantidad del artículo para actualizar las series.",
      );
    }
  }

  const elegidas = $$(".selector-serie", carrito).map(
    (select) => select.value,
  );
  if (
    elegidas.some((id) => !id) ||
    new Set(elegidas).size !== elegidas.length
  ) {
    event.preventDefault();
    return alertar(
      "Números de serie",
      "Selecciona una serie diferente para cada unidad.",
    );
  }

  return true;
}
