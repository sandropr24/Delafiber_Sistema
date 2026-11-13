document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pedido-confirmar').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (form.dataset.enviando) return;
            form.dataset.enviando = 'confirmando';
            const confirmado = window.Swal
                ? (await Swal.fire({ icon: 'question', title: 'Confirmar operación', text: form.dataset.confirmacion, showCancelButton: true, confirmButtonText: 'Continuar', cancelButtonText: 'Volver', confirmButtonColor: '#32005a' })).isConfirmed
                : window.confirm(form.dataset.confirmacion);
            if (!confirmado) { delete form.dataset.enviando; return; }
            form.dataset.enviando = 'enviando';
            form.querySelector('button[type="submit"]').disabled = true;
            form.submit();
        });
    });
    window.addEventListener('pageshow', () => document.querySelectorAll('.pedido-confirmar').forEach(form => {
        delete form.dataset.enviando;
        form.querySelector('button[type="submit"]').disabled = false;
    }));
});
