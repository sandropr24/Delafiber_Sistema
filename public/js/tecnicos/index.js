document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);
    const filas = [...document.querySelectorAll('#tablaTecnicos tbody tr')];
    const normalizar = valor => String(valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const filtrar = () => {
        const texto = normalizar($('buscarTecnico').value);
        const estado = $('filtrarEstadoTecnico').value;
        let visibles = 0;
        filas.forEach(fila => {
            const mostrar = normalizar(fila.dataset.busqueda).includes(texto) && (estado === 'TODOS' || fila.dataset.estado === estado);
            fila.classList.toggle('d-none', !mostrar);
            if (mostrar) visibles++;
        });
        $('contadorTecnicos').textContent = `${visibles} técnico(s)`;
        $('sinResultadosTecnicos').classList.toggle('d-none', visibles !== 0);
    };
    $('buscarTecnico')?.addEventListener('input', filtrar);
    $('filtrarEstadoTecnico')?.addEventListener('change', filtrar);
    $('btnNuevoTecnico')?.addEventListener('click', () => {
        $('formTecnico').reset();
        $('idtecnico').value = '';
        $('tituloModalTecnico').textContent = 'Nuevo técnico';
    });
    document.querySelectorAll('.btnEditarTecnico').forEach(boton => boton.addEventListener('click', () => {
        const tecnico = JSON.parse(boton.dataset.tecnico);
        ['idtecnico', 'nombres', 'apellidos', 'dni', 'telefono', 'especialidad'].forEach(campo => {
            const ids = { nombres: 'nombresTecnico', apellidos: 'apellidosTecnico', dni: 'dniTecnico', telefono: 'telefonoTecnico', especialidad: 'especialidadTecnico' };
            $(ids[campo] || campo).value = tecnico[campo] || '';
        });
        $('tituloModalTecnico').textContent = 'Editar técnico';
    }));
    document.querySelectorAll('.formEstadoTecnico').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault();
        Swal.fire({ title: `¿Deseas ${form.dataset.accion} este técnico?`, text: form.dataset.nombre, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar', confirmButtonColor: '#32005a' })
            .then(resultado => { if (resultado.isConfirmed) form.submit(); });
    }));
    const flash = $('flashTecnico');
    if (flash) Swal.fire({ icon: flash.dataset.tipo, text: flash.dataset.mensaje, confirmButtonColor: '#32005a' });
    filtrar();
});
