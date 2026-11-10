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
  form?.addEventListener("submit", (evento) => {
    const asignado = tecnico.value || inicio.value || fin.value;
    if (asignado && (!tecnico.value || !inicio.value || !fin.value)) {
      evento.preventDefault();
      alert("Para programar una visita, completa técnico, inicio y fin.");
      return;
    }
    if (
      asignado &&
      (new Date(inicio.value) >= new Date(fin.value) ||
        new Date(inicio.value) < new Date())
    ) {
      evento.preventDefault();
      alert("Selecciona un horario futuro con fin posterior al inicio.");
      return;
    }
    boton.disabled = true;
    boton.innerHTML =
      '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Guardando...';
  });
});
