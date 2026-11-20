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
            const resultado = form.querySelector('textarea[name="resultado"]')?.value.trim() || '';
            const inicio = form.querySelector('input[name="fecha_inicio"]');
            const fin = form.querySelector('input[name="fecha_fin"]');
            if (inicio && fin) {
                const desde = new Date(inicio.value);
                const hasta = new Date(fin.value);
                if (!Number.isFinite(desde.getTime()) || desde.getTime() <= Date.now()) {
                    alert('La programación debe comenzar en una fecha y hora futuras.');
                    inicio.focus();
                    return;
                }
                if (!Number.isFinite(hasta.getTime()) || hasta.getTime() <= desde.getTime()) {
                    alert('La hora de término debe ser posterior a la de inicio.');
                    fin.focus();
                    return;
                }
            }

            if (estado === 'CANCELADO' && nota.length < 5) {
                alert('Escribe al menos 5 caracteres para justificar esta operación.');
                return;
            }
            if (estado === 'FINALIZADO' && resultado.length < 5) {
                alert('Describe el resultado técnico con al menos 5 caracteres.');
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
const buscarMaterial = document.getElementById('buscarMaterialServicio');
const opcionesMaterial = document.getElementById('materialesServicioOpciones');
const cantidad = document.getElementById('cantidadConsumo');
const informacion = document.getElementById('stockDisponibleServicio');
const consumo = document.getElementById('formConsumoServicio');
if (material && cantidad && informacion && consumo) {
    let encontrados = new Map();
    let temporizador;
    const mostrarStock = () => {
        const elegido = encontrados.get(buscarMaterial?.value || '');
        const disponible = Number(elegido?.disponible || 0);
        material.value = elegido?.id || '';
        informacion.textContent = material.value
            ? `Disponible sin reservas: ${disponible.toFixed(2)}`
            : 'Selecciona un material para consultar su disponibilidad.';
        cantidad.max = material.value ? disponible.toFixed(2) : '';
    };
    buscarMaterial?.addEventListener('input', () => {
        material.value = '';
        mostrarStock();
        clearTimeout(temporizador);
        const texto = buscarMaterial.value.trim();
        if (texto.length < 2) { opcionesMaterial.replaceChildren(); return; }
        temporizador = setTimeout(async () => {
            try {
                const respuesta = await fetch(`${buscarMaterial.dataset.url}?q=${encodeURIComponent(texto)}`, { headers: { Accept: 'application/json' } });
                if (!respuesta.ok) return;
                const filas = await respuesta.json();
                encontrados = new Map();
                opcionesMaterial.replaceChildren();
                filas.forEach(fila => {
                    const etiqueta = `${fila.texto} · Disp.: ${fila.disponible} · #${fila.id}`;
                    encontrados.set(etiqueta, fila);
                    const opcion = document.createElement('option');
                    opcion.value = etiqueta;
                    opcionesMaterial.appendChild(opcion);
                });
                mostrarStock();
            } catch (_) { opcionesMaterial.replaceChildren(); }
        }, 250);
    });
    buscarMaterial?.addEventListener('change', mostrarStock);
    consumo.addEventListener('submit', evento => {
        if (consumo.dataset.enviando === 'si') { evento.preventDefault(); return; }
        const disponible = Number(encontrados.get(buscarMaterial?.value || '')?.disponible || 0);
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
