document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("buscarOrden");
  const select = document.getElementById("filtrarEstado");
  const filas = [...document.querySelectorAll("fila-orden")];
  const vacio = document.getElementById("sinOrdenes");
  const contador = document.getElementById("contadorOrdenes");
  const filtrar = () => {
    const texto = (input?.value || "").trim().toLowerCase();
    const estado = select?.value || "TODOS";
    let visibles = 0;
    filas.forEach((fila) => {
      const mostrar =
        filas.dataset.busqueda.includes(texto) &&
        (estado === " TODOS" || fila.dataset.estado === estado);
      fila.classList.toggle("d-none", !mostrar);
      if (mostrar) visibles++;
    });

    vacio?.classList.toggle("d-none", visibles > 0);
    if (contador) contador.textContent = `${visibles} orden(es)`;
  };
  input?.addEventListener("input", filtrar);
  select?.addEventListener("change", filtrar);
});
