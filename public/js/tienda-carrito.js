document.addEventListener('DOMContentLoaded', () => {
  const formularios = Array.from(document.querySelectorAll('[data-cart-add]'));
  const aviso = document.querySelector('[data-cart-feedback]');
  if (!formularios.length || !aviso || typeof window.fetch !== 'function' || typeof window.FormData !== 'function') return;

  const titulo = aviso.querySelector('[data-cart-feedback-title]');
  const mensaje = aviso.querySelector('[data-cart-announcement]');
  const producto = aviso.querySelector('[data-cart-product]');
  const total = aviso.querySelector('[data-cart-total]');
  const cerrar = aviso.querySelector('[data-cart-dismiss]');
  const continuar = aviso.querySelector('[data-cart-continue]');
  const recargar = aviso.querySelector('[data-cart-reload]');
  const moneda = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });
  let procesando = false;
  let bloqueado = false;
  let ultimoBoton = null;

  const mostrar = (texto, error = false, requiereRevision = false) => {
    aviso.hidden = false;
    aviso.classList.toggle('is-error', error);
    titulo.textContent = error ? 'Revisa tu carrito' : 'Producto agregado';
    mensaje.setAttribute('role', error ? 'alert' : 'status');
    mensaje.setAttribute('aria-live', error ? 'assertive' : 'polite');
    mensaje.textContent = texto;
    producto.hidden = true;
    total.hidden = true;
    cerrar.hidden = requiereRevision;
    continuar.hidden = requiereRevision;
    recargar.hidden = !requiereRevision;
  };

  const ocultar = () => {
    aviso.hidden = true;
    ultimoBoton?.focus({ preventScroll: true });
  };
  cerrar.addEventListener('click', ocultar);
  continuar.addEventListener('click', ocultar);
  recargar.addEventListener('click', () => window.location.reload());
  aviso.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !bloqueado) {
      event.preventDefault();
      ocultar();
    }
  });

  const actualizarCsrf = csrf => {
    if (!csrf || typeof csrf.name !== 'string' || typeof csrf.hash !== 'string' || !csrf.hash) return false;
    const campos = Array.from(document.querySelectorAll('input[type="hidden"]')).filter(campo => campo.name === csrf.name);
    campos.forEach(campo => { campo.value = csrf.hash; });
    return campos.length > 0;
  };

  formularios.forEach(formulario => {
    formulario.addEventListener('submit', async event => {
      event.preventDefault();
      if (procesando || bloqueado || !formulario.reportValidity()) return;
      procesando = true;
      ultimoBoton = event.submitter || formulario.querySelector('button[type="submit"]');
      const devolverFoco = document.activeElement === ultimoBoton;
      const textoBoton = ultimoBoton?.textContent;
      const botones = formularios.flatMap(form => Array.from(form.querySelectorAll('button[type="submit"]')))
        .map(boton => ({ boton, deshabilitado: boton.disabled }));
      botones.forEach(({ boton }) => { boton.disabled = true; });
      formulario.setAttribute('aria-busy', 'true');
      if (ultimoBoton) ultimoBoton.textContent = 'Agregando…';

      try {
        const respuesta = await window.fetch(formulario.action, {
          method: 'POST',
          body: new window.FormData(formulario),
          credentials: 'same-origin',
          signal: typeof window.AbortSignal?.timeout === 'function' ? window.AbortSignal.timeout(20000) : undefined,
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (respuesta.status === 403) {
          bloqueado = true;
          mostrar('La sesión de esta página venció o cambió en otra pestaña. Recarga la página antes de agregar más productos.', true, true);
          return;
        }
        if (respuesta.redirected || !respuesta.headers.get('content-type')?.includes('application/json')) throw new Error('Respuesta inesperada');
        const datos = await respuesta.json();
        const csrfActualizado = actualizarCsrf(datos.csrf);
        if ([422, 429].includes(respuesta.status) && datos.success === false && typeof datos.mensaje === 'string' && csrfActualizado) {
          mostrar(datos.mensaje, true);
          return;
        }
        if (!respuesta.ok || datos.success !== true || !csrfActualizado
          || !Number.isInteger(datos.carrito?.cantidad) || datos.carrito.cantidad < 1
          || typeof datos.carrito.total !== 'string' || !/^\d{1,14}\.\d{2}$/.test(datos.carrito.total)
          || typeof datos.producto?.nombre !== 'string' || typeof datos.producto.cantidad_agregada !== 'string'
          || !/^\d{1,4}\.\d{2}$/.test(datos.producto.cantidad_agregada) || Number(datos.producto.cantidad_agregada) <= 0
          || typeof datos.producto.unidad !== 'string' || typeof datos.mensaje !== 'string') {
          throw new Error('No se pudo confirmar el carrito');
        }
        document.querySelectorAll('[data-cart-count]').forEach(contador => {
          contador.textContent = String(datos.carrito.cantidad);
          contador.setAttribute('aria-label', `${datos.carrito.cantidad} ${datos.carrito.cantidad === 1 ? 'artículo distinto' : 'artículos distintos'}`);
        });
        mostrar(datos.mensaje);
        producto.textContent = `${datos.producto.nombre} · +${Number(datos.producto.cantidad_agregada)} ${datos.producto.unidad}`;
        producto.hidden = false;
        total.textContent = `Total referencial del carrito: ${moneda.format(Number(datos.carrito.total))}`;
        total.hidden = false;
      } catch {
        // Una respuesta perdida puede corresponder a un agregado ya guardado.
        // Nunca reenviar automáticamente el POST ni recurrir al submit nativo.
        bloqueado = true;
        mostrar('No pudimos confirmar si el producto se agregó. Revisa tu carrito antes de volver a intentarlo y recarga esta página para continuar.', true, true);
      } finally {
        procesando = false;
        formulario.removeAttribute('aria-busy');
        if (ultimoBoton) ultimoBoton.textContent = textoBoton;
        botones.forEach(({ boton, deshabilitado }) => { boton.disabled = bloqueado || deshabilitado; });
        if (devolverFoco && !bloqueado && document.activeElement === document.body) {
          ultimoBoton?.focus({ preventScroll: true });
        }
      }
    });
  });

  window.addEventListener('pageshow', event => {
    if (event.persisted) window.location.reload();
  });
});
