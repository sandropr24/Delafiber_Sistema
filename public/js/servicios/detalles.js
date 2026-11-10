document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.df-sv-confirm-form').forEach(form => {
        form.addEventListener('submit', async evento => {
            if (form.dataset.enviando === 'si') { evento.preventDefault(); return; }
            evento.preventDefault();
            const boton = evento.submitter;
            const estado = boton?.value || '';
            const texto = form.dataset.confirmacion || '¿Confirmar esta operación?';
            const mensaje = estado === 'CANCELADO' ? 'La orden quedará cancelada.' : texto;
            const nota = form.querySelector('textarea[name="observacion"]')?.value.trim() || '';
            if (['CANCELADO', 'FINALIZADO'].includes(estado) && nota.length < 5) {
                alert('Escribe al menos 5 caracteres para justificar esta operación.');
                return;
            }
            let confirmado;
            if (typeof Swal !== 'undefined') {
                const r = await Swal.fire({ title: 'Confirmar operación', text: mensaje, icon: 'question', showCancelButton: true, confirmButtonText: 'Confirmar', cancelButtonText: 'Volver', confirmButtonColor: '#35035b' });
                confirmado = r.isConfirmed;
            } else confirmado = window.confirm(mensaje);
            if (!confirmado) return;
            form.dataset.enviando = 'si';
            if (boton?.name === 'estado') {
                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = 'estado';
                campo.value = boton.value;
                form.appendChild(campo);
            }
            form.submit();
        });
    });
});

// Ayuda visual de disponibilidad. El servidor vuelve a validar bajo bloqueo de BD.
const material = document.getElementById('materialKardex');
const cantidad = document.getElementById('cantidadConsumo');
const informacion = document.getElementById('stockDisponibleServicio');
const consumo = document.getElementById('formConsumoServicio');
if (material && cantidad && informacion && consumo) {
    const mostrarStock = () => {
        const disponible = Number(material.selectedOptions[0]?.dataset.disponible || 0);
        informacion.textContent = material.value
            ? `Disponible sin reservas: ${disponible.toFixed(2)}`
            : 'Selecciona un material para consultar su disponibilidad.';
        cantidad.max = material.value ? disponible.toFixed(2) : '';
    };
    material.addEventListener('change', mostrarStock);
    mostrarStock();
    consumo.addEventListener('submit', evento => {
        if (consumo.dataset.enviando === 'si') { evento.preventDefault(); return; }
        const disponible = Number(material.selectedOptions[0]?.dataset.disponible || 0);
        const solicitado = Number(cantidad.value);
        if (!material.value || !Number.isFinite(solicitado) || solicitado <= 0 || solicitado > disponible) {
            evento.preventDefault();
            alert('La cantidad debe ser mayor que cero y no superar el stock disponible.');
            return;
        }
        if (!window.confirm('¿Confirmas la salida física de este material del Kardex?')) {
            evento.preventDefault();
            return;
        }
        consumo.dataset.enviando = 'si';
        consumo.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
    });
}
