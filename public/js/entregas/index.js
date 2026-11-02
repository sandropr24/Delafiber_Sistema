
document.addEventListener('DOMContentLoaded', () => {
    const vista = document.getElementById('viewEntregas');
    const gestionEl = document.getElementById('modalEstadoEntrega');
    const confirmarEl = document.getElementById('modalConfirmarEntrega');
    const formulario = document.getElementById('formEstadoEntrega');
    if (!vista || !gestionEl || !confirmarEl || !formulario || typeof bootstrap === 'undefined') return;

    const gestion = bootstrap.Modal.getOrCreateInstance(gestionEl);
    const confirmacion = bootstrap.Modal.getOrCreateInstance(confirmarEl);
    const idEntrega = document.getElementById('identrega');
    const estado = document.getElementById('nuevoEstadoEntrega');
    const tecnico = document.getElementById('tecnicoEntrega');
    const mensaje = document.getElementById('mensajeEntrega');
    const guardarBtn = document.getElementById('btnGuardarEstadoEntrega');
    const confirmarBtn = document.getElementById('btnConfirmarEntrega');
    const domicilioPanel = document.getElementById('infoDomicilioEntrega');
    const tbody = document.getElementById('tbodyEntregas');
    const buscar = document.getElementById('inputBuscarEntrega');
    const filtroEstado = document.getElementById('selectEstadoEntrega');
    const filtroTecnico = document.getElementById('selectTecnicoEntrega');

    let estadoInicial = '';
    let procesando = false;
    let regresarAGestion = false;
    let confirmado = false;

    function error(texto) {
        mensaje.textContent = texto;
        mensaje.classList.remove('d-none');
    }

    function limpiarError() {
        mensaje.textContent = '';
        mensaje.classList.add('d-none');
    }

    function mostrarDomicilio(boton) {
        if (!domicilioPanel) return;
        const esDomicilio = (boton.dataset.tipo || '') === 'DOMICILIO';
        domicilioPanel.classList.toggle('d-none', !esDomicilio);
        document.getElementById('detalleDireccionEntrega').textContent = boton.dataset.direccion || 'Sin dirección registrada';
        document.getElementById('detalleReferenciaEntrega').textContent = boton.dataset.referencia || 'Sin referencia';
        document.getElementById('detalleTelefonoEntrega').textContent = boton.dataset.telefono || 'Sin teléfono registrado';
    }

    function abrirGestion(boton) {
        if (procesando) return;
        idEntrega.value = boton.dataset.id || '';
        estadoInicial = boton.dataset.estado || 'PENDIENTE';
        estado.value = estadoInicial;
        tecnico.value = boton.dataset.tecnico || '';
        estado.disabled = estadoInicial === 'ENTREGADO';
        tecnico.disabled = estadoInicial === 'ENTREGADO';
        guardarBtn.disabled = estadoInicial === 'ENTREGADO';
        regresarAGestion = false;
        confirmado = false;
        limpiarError();
        mostrarDomicilio(boton);
        gestion.show();
    }

    document.addEventListener('click', (e) => {
        const boton = e.target.closest('.btn-gestionar-entrega');
        if (boton) abrirGestion(boton);
    });

    function validar() {
        if (!idEntrega.value) {
            error('No se identificó la entrega.');
            return false;
        }
        if (estadoInicial === 'ENTREGADO') {
            error('Esta entrega ya fue confirmada.');
            return false;
        }
        if (estadoInicial === 'EN_CAMINO' && estado.value === 'PENDIENTE') {
            error('No se puede regresar al estado Pendiente.');
            return false;
        }
        return true;
    }

    gestionEl.addEventListener('hidden.bs.modal', () => {
        if (regresarAGestion && !procesando && !confirmado) confirmacion.show();
    });

    confirmarEl.addEventListener('hidden.bs.modal', () => {
        if (regresarAGestion && !procesando && !confirmado) {
            regresarAGestion = false;
            gestion.show();
        }
    });

    formulario.addEventListener('submit', async (e) => {
        e.preventDefault();
        limpiarError();
        if (procesando || !validar()) return;

        if (estado.value === 'ENTREGADO') {
            regresarAGestion = true;
            gestion.hide();
            return;
        }
        await enviar();
    });

    confirmarBtn.addEventListener('click', async () => {
        if (!procesando && estado.value === 'ENTREGADO' && validar()) await enviar();
    });

    async function enviar() {
        if (procesando) return;
        procesando = true;
        guardarBtn.disabled = true;
        confirmarBtn.disabled = true;
        limpiarError();

        try {
            const datos = new FormData(formulario);
            datos.set('identrega', idEntrega.value);
            datos.set('estado', estado.value);
            datos.set('idtecnico', tecnico.value);

            const respuesta = await fetch(vista.dataset.urlEstado, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const contenido = await respuesta.text();
            let resultado;

            try {
                resultado = JSON.parse(contenido);
            } catch {
                throw new Error('El servidor no devolvió una respuesta válida.');
            }

            if (!respuesta.ok || resultado.success !== true) {
                throw new Error(resultado.message || resultado.mensaje || 'No se pudo actualizar la entrega.');
            }

            confirmado = true;
            regresarAGestion = false;
            confirmacion.hide();
            gestion.hide();
            window.location.reload();
        } catch (e) {
            error(e.message || 'Ocurrió un error al guardar.');

            if (confirmarEl.classList.contains('show')) {
                regresarAGestion = true;
                confirmacion.hide();
            } else if (!gestionEl.classList.contains('show')) {
                regresarAGestion = false;
                gestion.show();
            }
        } finally {
            procesando = false;
            confirmarBtn.disabled = false;
            guardarBtn.disabled = estadoInicial === 'ENTREGADO';
        }
    }

    function filtrar() {
        if (!tbody) return;

        const texto = (buscar?.value || '').toLowerCase().trim();
        const estadoSeleccionado = filtroEstado?.value || '';
        const tecnicoSeleccionado = filtroTecnico?.value || '';

        tbody.querySelectorAll('tr').forEach((fila) => {
            const boton = fila.querySelector('.btn-gestionar-entrega');
            if (!boton) return;

            const contenido = [
                fila.textContent,
                boton.dataset.direccion,
                boton.dataset.referencia,
                boton.dataset.telefono
            ].join(' ').toLowerCase();

            const coincide = contenido.includes(texto) &&
                (!estadoSeleccionado || boton.dataset.estado === estadoSeleccionado) &&
                (!tecnicoSeleccionado || boton.dataset.tecnico === tecnicoSeleccionado);

            fila.classList.toggle('d-none', !coincide);
        });
    }

    buscar?.addEventListener('input', filtrar);
    filtroEstado?.addEventListener('change', filtrar);
    filtroTecnico?.addEventListener('change', filtrar);
});
