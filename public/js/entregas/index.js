
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

    async function datosConCsrf(form) {
        const respuesta = await fetch(vista.dataset.urlCsrf, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
        });
        if (!respuesta.ok) {
            throw new Error('No se pudo actualizar el token de seguridad. Recarga la página e inicia sesión nuevamente si es necesario.');
        }

        const token = await respuesta.json();
        if (!token || typeof token.name !== 'string' || typeof token.hash !== 'string' || !token.name || !token.hash) {
            throw new Error('El servidor devolvió un token de seguridad inválido.');
        }

        document.querySelectorAll('#formEstadoEntrega input[name], #formIncidenciaEntrega input[name], #formCancelarEntrega input[name]').forEach(input => {
            if (input.type === 'hidden' && input.name.startsWith('csrf_')) {
                input.name = token.name;
                input.value = token.hash;
            }
        });
        const datos = new FormData(form);
        datos.set(token.name, token.hash);
        return datos;
    }

    function verificarCsrf(respuesta) {
        if (respuesta.status === 403) {
            throw new Error('La validación de seguridad expiró o no coincide. Recarga la página e inténtalo nuevamente. No se guardó el cambio.');
        }
    }

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

    const transicionesPermitidas = {
        PENDIENTE: ['PENDIENTE', 'EN_CAMINO'],
        EN_CAMINO: ['EN_CAMINO', 'ENTREGADO'],
        REPROGRAMADO: ['REPROGRAMADO', 'EN_CAMINO'],
        RECHAZADO: [],
        INCIDENCIA: [],
        ENTREGADO: [],
        CANCELADO: []
    };

    function configurarEstados(actual) {
        const permitidos = transicionesPermitidas[actual] || [];
        [...estado.options].forEach(opcion => {
            opcion.hidden = !permitidos.includes(opcion.value);
            opcion.disabled = !permitidos.includes(opcion.value);
        });
        estado.value = actual;
        return permitidos.length > 0;
    }

    function abrirGestion(boton) {
        if (procesando) return;
        idEntrega.value = boton.dataset.id || '';
        estadoInicial = boton.dataset.estado || 'PENDIENTE';
        const editable = configurarEstados(estadoInicial);
        tecnico.value = boton.dataset.tecnico || '';
        estado.disabled = !editable;
        tecnico.disabled = !editable;
        guardarBtn.disabled = !editable;
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
        if (!(transicionesPermitidas[estadoInicial] || []).includes(estado.value)) {
            error('Transición de estado no permitida. Resuelve primero la incidencia.');
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
            const datos = await datosConCsrf(formulario);
            datos.set('identrega', idEntrega.value);
            datos.set('estado', estado.value);
            datos.set('idtecnico', tecnico.value);

            const respuesta = await fetch(vista.dataset.urlEstado, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            verificarCsrf(respuesta);
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
            guardarBtn.disabled = !(transicionesPermitidas[estadoInicial] || []).length;
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

    const incidenciaEl = document.getElementById('modalIncidenciaEntrega');
    const historialEl = document.getElementById('modalHistorialIncidencias');
    const incidenciaForm = document.getElementById('formIncidenciaEntrega');
    if (incidenciaEl && historialEl && incidenciaForm) {
        const incidenciaModal = bootstrap.Modal.getOrCreateInstance(incidenciaEl);
        const historialModal = bootstrap.Modal.getOrCreateInstance(historialEl);
        const incidenciaError = document.getElementById('incidenciaError');
        const incidenciaGuardar = document.getElementById('btnGuardarIncidencia');
        const historialContenido = document.getElementById('historialIncidenciasContenido');
        document.addEventListener('click', async (event) => {
            const btnIncidencia = event.target.closest('.btn-incidencia-entrega');
            if (btnIncidencia && !btnIncidencia.disabled) {
                incidenciaForm.reset();
                document.getElementById('incidenciaIdEntrega').value = btnIncidencia.dataset.id;
                incidenciaError.classList.add('d-none');
                incidenciaModal.show();
            }
            const btnHistorial = event.target.closest('.btn-historial-entrega');
            if (!btnHistorial) return;
            historialContenido.textContent = 'Cargando historial...';
            historialModal.show();
            try {
                const url = `${vista.dataset.urlHistorial}/${encodeURIComponent(btnHistorial.dataset.id)}`;
                const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo cargar el historial.');
                historialContenido.replaceChildren();
                if (!data.registros.length) {
                    historialContenido.textContent = 'Esta entrega todavía no tiene incidencias registradas.';
                    return;
                }
                data.registros.forEach((item) => {
                    const bloque = document.createElement('div');
                    bloque.className = 'border rounded p-3 mb-2';
                    const titulo = document.createElement('div');
                    titulo.className = 'fw-semibold';
                    titulo.textContent = `${item.tipo} · ${item.fechaintento}`;
                    const cambio = document.createElement('div');
                    cambio.className = 'small text-muted';
                    cambio.textContent = `${item.estado_anterior} → ${item.estado_nuevo}${item.fechareprogramada ? ' · Nueva fecha: ' + item.fechareprogramada : ''}`;
                    const motivo = document.createElement('div');
                    motivo.textContent = item.motivo;
                    bloque.append(titulo, cambio, motivo);
                    historialContenido.append(bloque);
                });
            } catch (err) {
                historialContenido.textContent = err.message || 'Error al consultar el historial.';
            }
        });
        incidenciaForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!incidenciaForm.reportValidity()) return;
            incidenciaGuardar.disabled = true;
            incidenciaError.classList.add('d-none');
            try {
                const response = await fetch(vista.dataset.urlIncidencia, {
                    method: 'POST', body: await datosConCsrf(incidenciaForm), credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                verificarCsrf(response);
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo registrar la incidencia.');
                window.location.reload();
            } catch (err) {
                incidenciaError.textContent = err.message || 'Error al registrar incidencia.';
                incidenciaError.classList.remove('d-none');
            } finally {
                incidenciaGuardar.disabled = false;
            }
        });
    }

    const cancelarEl = document.getElementById('modalCancelarEntrega');
    const cancelarForm = document.getElementById('formCancelarEntrega');
    if (cancelarEl && cancelarForm) {
        const cancelarModal = bootstrap.Modal.getOrCreateInstance(cancelarEl);
        const cancelarError = document.getElementById('cancelarEntregaError');
        const cancelarGuardar = document.getElementById('btnConfirmarCancelacion');
        document.addEventListener('click', (event) => {
            const boton = event.target.closest('.btn-cancelar-entrega');
            if (!boton) return;
            cancelarForm.reset();
            document.getElementById('cancelarIdEntrega').value = boton.dataset.id;
            cancelarError.classList.add('d-none');
            cancelarModal.show();
        });
        cancelarForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!cancelarForm.reportValidity()) return;
            cancelarGuardar.disabled = true;
            cancelarError.classList.add('d-none');
            try {
                const response = await fetch(vista.dataset.urlCancelar, {
                    method: 'POST', body: await datosConCsrf(cancelarForm), credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                verificarCsrf(response);
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo cancelar la entrega.');
                window.location.reload();
            } catch (err) {
                cancelarError.textContent = err.message || 'Error al cancelar la entrega.';
                cancelarError.classList.remove('d-none');
            } finally {
                cancelarGuardar.disabled = false;
            }
        });
    }

    buscar?.addEventListener('input', filtrar);
    filtroEstado?.addEventListener('change', filtrar);
    filtroTecnico?.addEventListener('change', filtrar);
});
