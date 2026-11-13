document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cot-confirmar').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.enviando) return;
        form.dataset.enviando = '1';
        const confirmado = window.Swal ? (await Swal.fire({title: 'Confirmar', text: form.dataset.confirmacion, icon: 'question', showCancelButton: true, confirmButtonText: 'Continuar', cancelButtonText: 'Volver', confirmButtonColor: '#32005a'})).isConfirmed : window.confirm(form.dataset.confirmacion);
        if (!confirmado) { delete form.dataset.enviando; return; }
        form.querySelector('button[type="submit"]').disabled = true;
        form.submit();
    }));
    window.addEventListener('pageshow', () => document.querySelectorAll('.cot-confirmar').forEach(form => { delete form.dataset.enviando; form.querySelector('button[type="submit"]').disabled = false; }));
});
