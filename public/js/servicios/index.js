document.addEventListener('DOMContentLoaded', () => {
    const buscar = document.getElementById('buscarOrden');
    const estado = document.getElementById('filtrarEstado');
    const filas = [...document.querySelectorAll('.fila-orden')];
    const vacio = document.getElementById('sinOrdenes');
    const contador = document.getElementById('contadorOrdenes');

    const normalizar = texto => (texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const filtrar = () => {
        const texto = normalizar(buscar?.value.trim());
        const seleccionado = estado?.value || 'TODOS';
        let visibles = 0;

        filas.forEach(fila => {
            const coincideTexto = normalizar(fila.dataset.busqueda).includes(texto);
            const coincideEstado = seleccionado === 'TODOS' || fila.dataset.estado === seleccionado;
            const mostrar = coincideTexto && coincideEstado;
            fila.classList.toggle('d-none', !mostrar);
            if (mostrar) visibles++;
        });

        vacio?.classList.toggle('d-none', visibles !== 0);
        if (contador) contador.textContent = `${visibles} de ${filas.length} órdenes`;
    };

    buscar?.addEventListener('input', filtrar);
    estado?.addEventListener('change', filtrar);
    filtrar();
});
