document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filtrosAgenda');
    const desde = document.getElementById('agendaDesde');
    const hasta = document.getElementById('agendaHasta');
    form?.addEventListener('submit', evento => {
        hasta.setCustomValidity('');
        if (desde.value && hasta.value && desde.value > hasta.value) {
            evento.preventDefault();
            hasta.setCustomValidity('La fecha final debe ser igual o posterior a la fecha inicial.');
            hasta.reportValidity();
        }
    });
    hasta?.addEventListener('change', () => hasta.setCustomValidity(''));
});
