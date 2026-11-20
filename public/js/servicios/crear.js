document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formServicio");
  const cliente = document.getElementById("idcliente");
  const buscarCliente = document.getElementById("buscarClienteServicio");
  const opcionesCliente = document.getElementById("clientesServicioOpciones");
  const servicio = document.getElementById("idservicio");
  const titulo = document.getElementById("titulo");
  const direccion = document.getElementById("direccion");
  const telefono = document.getElementById("telefono");
  const tecnico = document.getElementById("idtecnico");
  const inicio = document.getElementById("fecha_inicio");
  const fin = document.getElementById("fecha_fin");
  const boton = document.getElementById("btnGuardarServicio");
  const ventaOrigen = document.getElementById("ventaOrigen");
  const buscarVenta = document.getElementById("buscarVentaOrigen");
  const estadoVenta = document.getElementById("estadoBusquedaVenta");
  const cantidadServicio = document.getElementById("cantidadServicio");
  const cantidadAyuda = document.getElementById("cantidadServicioAyuda");
  let formularioModificado = false;
  form?.addEventListener("input", () => { formularioModificado = true; });
  ventaOrigen?.addEventListener("change", () => {
    if (formularioModificado && !window.confirm("Cambiar el origen descartará los datos ingresados. ¿Deseas continuar?")) {
      ventaOrigen.value = ventaOrigen.dataset.valorInicial || "";
      return;
    }
    const destino = ventaOrigen.dataset.url;
    window.location.href = ventaOrigen.value
      ? destino + "?venta=" + encodeURIComponent(ventaOrigen.value)
      : destino;
  });
  if (ventaOrigen) ventaOrigen.dataset.valorInicial = ventaOrigen.value;
  let temporizadorVenta;
  let solicitudVenta;
  buscarVenta?.addEventListener("input", () => {
    clearTimeout(temporizadorVenta);
    solicitudVenta?.abort();
    const texto = buscarVenta.value.trim();
    if (texto && !/^\d+$/.test(texto) && texto.length < 2) {
      if (estadoVenta) estadoVenta.textContent = "Escribe al menos dos caracteres, o el número exacto de venta.";
      return;
    }
    temporizadorVenta = setTimeout(async () => {
      solicitudVenta = new AbortController();
      if (estadoVenta) estadoVenta.textContent = texto ? "Buscando ventas pendientes..." : "Cargando ventas recientes con servicios pendientes...";
      try {
        const respuesta = await fetch(`${buscarVenta.dataset.url}?q=${encodeURIComponent(texto)}`, {
          headers: { Accept: "application/json" },
          signal: solicitudVenta.signal
        });
        if (!respuesta.ok) throw new Error("No se pudo consultar las ventas.");
        const filas = await respuesta.json();
        const valorActual = ventaOrigen.value;
        const opcionActual = valorActual ? ventaOrigen.selectedOptions[0]?.cloneNode(true) : null;
        ventaOrigen.replaceChildren(ventaOrigen.options[0]?.cloneNode(true));
        if (opcionActual) ventaOrigen.appendChild(opcionActual);
        filas.forEach(fila => {
          if (String(fila.id) === valorActual) return;
          const opcion = document.createElement("option");
          opcion.value = fila.id;
          opcion.textContent = fila.texto;
          ventaOrigen.appendChild(opcion);
        });
        ventaOrigen.value = valorActual;
        if (estadoVenta) estadoVenta.textContent = filas.length ? "Selecciona una venta de los resultados." : "No se encontraron ventas con servicios pendientes.";
      } catch (error) {
        if (error.name !== "AbortError" && estadoVenta) estadoVenta.textContent = "No se pudo completar la búsqueda. Intenta nuevamente.";
      }
    }, 300);
  });
  buscarVenta?.addEventListener("focus", () => {
    if (!buscarVenta.value.trim() && ventaOrigen.options.length <= 1) buscarVenta.dispatchEvent(new Event("input"));
  });
  if (cliente?.value && buscarCliente) {
    if (direccion && !direccion.value.trim()) direccion.value = buscarCliente.dataset.direccion || "";
    if (telefono && !telefono.value.trim()) telefono.value = buscarCliente.dataset.telefono || "";
  }
  if (servicio && titulo && !titulo.value.trim() && servicio.value) {
    titulo.value = servicio.selectedOptions[0]?.dataset.nombre || "";
    titulo.dataset.autogenerado = "si";
  }
  let clientesEncontrados = new Map();
  let temporizadorCliente;
  const seleccionarCliente = () => {
    const elegido = clientesEncontrados.get(buscarCliente?.value || "");
    cliente.value = elegido?.id || "";
    if (elegido) {
      direccion.value = elegido.direccion || "";
      telefono.value = elegido.telefono || "";
    }
  };
  buscarCliente?.addEventListener("input", () => {
    cliente.value = "";
    clearTimeout(temporizadorCliente);
    const texto = buscarCliente.value.trim();
    if (texto.length < 2) { opcionesCliente.replaceChildren(); return; }
    temporizadorCliente = setTimeout(async () => {
      try {
        const respuesta = await fetch(`${buscarCliente.dataset.url}?q=${encodeURIComponent(texto)}`, { headers: { Accept: "application/json" } });
        if (!respuesta.ok) return;
        const filas = await respuesta.json();
        clientesEncontrados = new Map();
        opcionesCliente.replaceChildren();
        filas.forEach(fila => {
          const etiqueta = `${fila.texto} · #${fila.id}`;
          clientesEncontrados.set(etiqueta, fila);
          const opcion = document.createElement("option");
          opcion.value = etiqueta;
          opcionesCliente.appendChild(opcion);
        });
        seleccionarCliente();
      } catch (_) { opcionesCliente.replaceChildren(); }
    }, 250);
  });
  buscarCliente?.addEventListener("change", seleccionarCliente);
  servicio?.addEventListener("change", () => {
    const nombre = servicio.selectedOptions[0].dataset.nombre || "";
    if (!titulo.value.trim() || titulo.dataset.autogenerado === "si") {
      titulo.value = nombre;
      titulo.dataset.autogenerado = "si";
    }
    const pendiente = servicio.selectedOptions[0]?.dataset.pendiente;
    const unidad = servicio.selectedOptions[0]?.dataset.unidad || "SERV";
    if (cantidadServicio && pendiente) cantidadServicio.max = pendiente;
    else cantidadServicio?.removeAttribute("max");
    if (cantidadAyuda) cantidadAyuda.textContent = pendiente ? `Máximo pendiente: ${pendiente} ${unidad}.` : "Unidades cubiertas por esta orden.";
  });
  servicio?.dispatchEvent(new Event("change"));
  titulo?.addEventListener("input", () => {
    titulo.dataset.autogenerado = "no";
  });
  const error = document.getElementById('errorServicio');
  const mensajeError = (texto, campo) => {
    if (error) {
      error.textContent = texto;
      error.classList.remove('d-none');
      error.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    campo?.focus();
  };

  [tecnico, inicio, fin].forEach(campo => campo?.addEventListener('change', () => error?.classList.add('d-none')));

  form?.addEventListener('submit', evento => {
    error?.classList.add('d-none');
    if (!cliente?.value) {
      evento.preventDefault();
      mensajeError('Selecciona un cliente de las coincidencias mostradas.', buscarCliente);
      return;
    }
    const programar = Boolean(tecnico.value || inicio.value || fin.value);
    if (programar && !tecnico.value) {
      evento.preventDefault();
      mensajeError('Selecciona al técnico responsable para programar la visita.', tecnico);
      return;
    }
    if (programar && !inicio.value) {
      evento.preventDefault();
      mensajeError('Indica la fecha y hora de inicio del servicio.', inicio);
      return;
    }
    if (programar && !fin.value) {
      evento.preventDefault();
      mensajeError('Indica la fecha y hora de término del servicio.', fin);
      return;
    }
    if (programar) {
      const fechaInicio = new Date(inicio.value);
      const fechaFin = new Date(fin.value);
      if (!Number.isFinite(fechaInicio.getTime()) || fechaInicio.getTime() <= Date.now()) {
        evento.preventDefault();
        mensajeError('La fecha y hora de inicio deben ser posteriores a la hora actual.', inicio);
        return;
      }
      if (!Number.isFinite(fechaFin.getTime()) || fechaFin.getTime() <= fechaInicio.getTime()) {
        evento.preventDefault();
        mensajeError('La fecha y hora de término deben ser posteriores al inicio.', fin);
        return;
      }
    }
    if (form.dataset.enviando === 'si') {
      evento.preventDefault();
      return;
    }
    form.dataset.enviando = 'si';
    boton.disabled = true;
    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Guardando...';
  });
});
