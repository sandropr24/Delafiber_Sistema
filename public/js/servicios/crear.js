document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formServicio");
  const cliente = document.getElementById("idcliente");
  const servicio = document.getElementById("idservicio");
  const titulo = document.getElementById("titulo");
  const direccion = document.getElementById("direccion");
  const telefono = document.getElementById("telefono");
  const tecnico = document.getElementById("idtecnico");
  const inicio = document.getElementById("fecha_inicio");
  const fin = document.getElementById("fecha_fin");
  const boton = document.getElementById("btnGuardarServicio");
  const ventaOrigen = document.getElementById("ventaOrigen");
  ventaOrigen?.addEventListener("change", () => {
    const destino = ventaOrigen.dataset.url;
    window.location.href = ventaOrigen.value
      ? destino + "?venta=" + encodeURIComponent(ventaOrigen.value)
      : destino;
  });
  if (cliente && cliente.value) {
    const opcion = cliente.selectedOptions[0];
    if (direccion && !direccion.value.trim())
      direccion.value = opcion?.dataset.direccion || "";
    if (telefono && !telefono.value.trim())
      telefono.value = opcion?.dataset.telefono || "";
  }
  if (servicio && titulo && !titulo.value.trim() && servicio.value) {
    titulo.value = servicio.selectedOptions[0]?.dataset.nombre || "";
    titulo.dataset.autogenerado = "si";
  }
  cliente?.addEventListener("change", () => {
    const elegido = cliente.selectedOptions[0];
    direccion.value = elegido?.dataset.direccion || "";
    telefono.value = elegido?.dataset.telefono || "";
  });
  servicio?.addEventListener("change", () => {
    const nombre = servicio.selectedOptions[0].dataset.nombre || "";
    if (!titulo.value.trim() || titulo.dataset.autogenerado === "si") {
      titulo.value = nombre;
      titulo.dataset.autogenerado = "si";
    }
  });
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
