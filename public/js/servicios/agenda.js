document.addEventListener('DOMContentLoaded', () => {
    const tecnico = document.getElementById('agendaTecnico');
    const fecha = document.getElementById('agendaFecha');
    const limpiar = document.getElementById('limpiarAgenda');
    const vacio = document.getElementById('agendaSinResultados');
    const dias = [...document.querySelectorAll('.df-sv-agenda-day')];

    const filtrar = () => {
        let visibles = 0;
        dias.forEach(dia => {
            let cantidadDia = 0;
            dia.querySelectorAll('.df-sv-agenda-item').forEach(orden => {
                const mostrar = (!tecnico?.value || orden.dataset.tecnico === tecnico.value)
                    && (!fecha?.value || dia.dataset.fecha === fecha.value);
                orden.classList.toggle('d-none', !mostrar);
                if (mostrar) { visibles++; cantidadDia++; }
            });
            dia.classList.toggle('d-none', cantidadDia === 0);
        });
        vacio?.classList.toggle('d-none', visibles !== 0);
    };
    tecnico?.addEventListener('change', filtrar);
    fecha?.addEventListener('change', filtrar);
    limpiar?.addEventListener('click', () => {
        if (tecnico) tecnico.value = '';
        if (fecha) fecha.value = '';
        filtrar();
    });
    filtrar();
});
