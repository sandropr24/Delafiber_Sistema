document.addEventListener('DOMContentLoaded', () => {
  const menuCategorias = document.querySelector('[data-category-menu]');
  if (menuCategorias) {
    document.addEventListener('click', event => {
      if (!menuCategorias.contains(event.target)) menuCategorias.removeAttribute('open');
    });
    menuCategorias.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      menuCategorias.removeAttribute('open');
      menuCategorias.querySelector('summary')?.focus();
    });
  }

  const ampliarImagen = document.querySelector('[data-image-zoom]');
  const imagenDialogo = document.querySelector('#imagen-producto');
  if (ampliarImagen && imagenDialogo && typeof imagenDialogo.showModal === 'function') {
    ampliarImagen.addEventListener('click', event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      imagenDialogo.showModal();
    });
    imagenDialogo.addEventListener('click', event => {
      if (event.target !== imagenDialogo) return;
      const limites = imagenDialogo.getBoundingClientRect();
      if (event.clientX < limites.left || event.clientX > limites.right || event.clientY < limites.top || event.clientY > limites.bottom) {
        imagenDialogo.close();
      }
    });
  }

  const checkout = document.querySelector('[data-checkout-form]');
  if (!checkout) return;

  const direccion = checkout.querySelector('#direccion');
  const opcional = checkout.querySelector('[data-address-optional]');
  const modalidades = checkout.querySelectorAll('input[name="modalidad"]');
  const actualizarEntrega = () => {
    const domicilio = checkout.querySelector('input[name="modalidad"]:checked')?.value === 'DOMICILIO';
    direccion.required = domicilio;
    direccion.setAttribute('aria-required', domicilio ? 'true' : 'false');
    opcional.textContent = domicilio ? 'Obligatoria' : 'Opcional para recojo';
    modalidades.forEach(radio => radio.closest('label')?.classList.toggle('selected', radio.checked));
  };

  modalidades.forEach(radio => radio.addEventListener('change', actualizarEntrega));
  actualizarEntrega();
});
