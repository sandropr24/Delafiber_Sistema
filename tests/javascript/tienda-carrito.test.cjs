const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

function elemento(texto = '') {
    return {
        textContent: texto, hidden: false, disabled: false, atributos: {}, eventos: {}, enfocado: false,
        classList: { toggle() {} },
        setAttribute(nombre, valor) { this.atributos[nombre] = valor; },
        removeAttribute(nombre) { delete this.atributos[nombre]; },
        addEventListener(nombre, accion) { this.eventos[nombre] = accion; },
        focus() { this.enfocado = true; },
        set innerHTML(_) { assert.fail('No insertar HTML recibido del servidor'); },
    };
}

function respuesta(datos = {}, status = 200, opciones = {}) {
    return {
        status, ok: status >= 200 && status < 300, redirected: false,
        headers: { get: () => 'application/json; charset=UTF-8' },
        json: async () => ({
            success: true, mensaje: 'Producto agregado al carrito.',
            csrf: { name: 'csrf_test_name', hash: 'nuevo-token' },
            carrito: { cantidad: 1, total: '120.50' },
            producto: { nombre: '<Router>', cantidad_agregada: '1.00', cantidad: '1.00', unidad: 'UND' },
            ...datos,
        }),
        ...opciones,
    };
}

function pagina({ fetchDisponible = true, formDataDisponible = true, valida = true } = {}) {
    const eventosDocumento = {};
    const eventosVentana = {};
    const campos = ['csrf_test_name', 'csrf_test_name', 'csrf_test_name', 'request_token']
        .map(name => ({ name, value: 'anterior' }));
    const botones = [elemento('Agregar'), elemento('Agregar servicio')];
    const forms = botones.map((boton, indice) => ({
        ...elemento(), action: 'http://example.com/carrito/agregar', indice,
        reportValidity: () => valida,
        querySelector: () => boton, querySelectorAll: () => [boton],
        submit: () => assert.fail('Nunca reenviar el formulario tras un error de red'),
    }));
    const aviso = elemento();
    aviso.hidden = true;
    const partes = Object.fromEntries([
        'title', 'announcement', 'product', 'total', 'dismiss', 'continue', 'reload',
    ].map(nombre => [nombre, elemento()]));
    aviso.querySelector = selector => selector === '[data-cart-announcement]'
        ? partes.announcement : partes[selector.replace('[data-cart-', '').replace('feedback-', '').replace(']', '')];
    const contador = elemento('0');
    const llamadas = [];
    const cola = [];
    let recargas = 0;
    const window = {
        fetch: fetchDisponible ? async (url, opciones) => {
            llamadas.push({ url, opciones });
            const siguiente = cola.shift();
            if (siguiente instanceof Error) throw siguiente;
            return siguiente ?? respuesta();
        } : undefined,
        FormData: formDataDisponible ? class {
            constructor(form) { this.token = campos[form.indice].value; }
        } : undefined,
        location: { reload: () => { recargas++; } },
        addEventListener: (nombre, accion) => { eventosVentana[nombre] = accion; },
    };
    const document = {
        addEventListener: (nombre, accion) => { eventosDocumento[nombre] = accion; },
        querySelector: () => aviso,
        querySelectorAll: selector => ({
            '[data-cart-add]': forms,
            'input[type="hidden"]': campos,
            '[data-cart-count]': [contador],
        })[selector] ?? [],
    };
    runInNewContext(readFileSync(resolve(__dirname, '../../public/js/tienda-carrito.js'), 'utf8'), { document, window, Intl });
    eventosDocumento.DOMContentLoaded();
    const enviar = (indice = 0) => forms[indice].eventos.submit({
        preventDefault() {}, submitter: botones[indice],
    });
    return { forms, botones, partes, aviso, contador, campos, llamadas, cola, enviar, eventosVentana, recargas: () => recargas };
}

test('agrega sin navegar, actualiza contador y todos los tokens con datos seguros', async () => {
    const p = pagina();
    await p.enviar();
    assert.equal(p.llamadas.length, 1);
    const { opciones } = p.llamadas[0];
    assert.equal(opciones.method, 'POST');
    assert.equal(opciones.credentials, 'same-origin');
    assert.equal(opciones.headers['X-Requested-With'], 'XMLHttpRequest');
    assert.equal(opciones.body.token, 'anterior');
    assert.equal(p.contador.textContent, '1');
    assert.equal(p.contador.atributos['aria-label'], '1 artículo distinto');
    assert.equal(p.aviso.hidden, false);
    assert.equal(p.partes.product.textContent, '<Router> · +1 UND');
    assert.match(p.partes.total.textContent, /120\.50/);
    assert.equal(p.partes.announcement.atributos.role, 'status');
    assert.deepEqual(p.campos.map(c => c.value), ['nuevo-token', 'nuevo-token', 'nuevo-token', 'anterior']);
    assert.equal(p.botones[0].textContent, 'Agregar');
    assert.equal(p.botones[0].disabled, false);
});

test('agregados sucesivos usan el token renovado y cuentan artículos distintos', async () => {
    const p = pagina();
    await p.enviar();
    p.cola.push(respuesta({ carrito: { cantidad: 1, total: '241.00' } }));
    await p.enviar();
    assert.equal(p.llamadas[1].opciones.body.token, 'nuevo-token');
    assert.equal(p.contador.textContent, '1');
    assert.match(p.partes.total.textContent, /241\.00/);
    p.cola.push(respuesta({ carrito: { cantidad: 2, total: '321.00' } }));
    await p.enviar(1);
    assert.equal(p.contador.textContent, '2');
    assert.equal(p.contador.atributos['aria-label'], '2 artículos distintos');
});

test('bloquea doble clic y solicitudes simultáneas desde distintos productos', async () => {
    const p = pagina();
    let terminar;
    p.cola.push(new Promise(resolve => { terminar = resolve; }));
    const pendiente = p.enviar();
    assert.ok(p.botones.every(boton => boton.disabled));
    assert.equal(p.botones[0].textContent, 'Agregando…');
    assert.equal(p.forms[0].atributos['aria-busy'], 'true');
    await p.enviar(1);
    assert.equal(p.llamadas.length, 1);
    terminar(respuesta());
    await pendiente;
    assert.ok(p.botones.every(boton => !boton.disabled));
    assert.equal(p.forms[0].atributos['aria-busy'], undefined);
});

test('un rechazo de validación conserva el contador, renueva CSRF y permite corregir', async () => {
    const p = pagina();
    p.cola.push(respuesta({ success: false, mensaje: 'Requiere unidades enteras.' }, 422));
    await p.enviar();
    assert.equal(p.contador.textContent, '0');
    assert.equal(p.partes.announcement.textContent, 'Requiere unidades enteras.');
    assert.equal(p.partes.announcement.atributos.role, 'alert');
    assert.equal(p.botones[0].disabled, false);
    await p.enviar();
    assert.equal(p.llamadas.length, 2);
    assert.equal(p.contador.textContent, '1');
});

test('una respuesta perdida nunca reenvía el POST ni usa el submit nativo', async () => {
    const p = pagina();
    p.cola.push(new Error('Conexión perdida'));
    await p.enviar();
    assert.equal(p.contador.textContent, '0');
    assert.match(p.partes.announcement.textContent, /confirmar si el producto se agregó/);
    assert.equal(p.partes.reload.hidden, false);
    assert.equal(p.partes.continue.hidden, true);
    assert.ok(p.botones.every(boton => boton.disabled));
    await p.enviar();
    assert.equal(p.llamadas.length, 1);
    p.partes.reload.eventos.click();
    assert.equal(p.recargas(), 1);
});

test('un token vencido exige recargar y no intenta agregar de nuevo', async () => {
    const p = pagina();
    p.cola.push(respuesta({}, 403, { json: () => assert.fail('No necesita leer una página de error CSRF') }));
    await p.enviar();
    assert.match(p.partes.announcement.textContent, /sesión/);
    assert.equal(p.partes.reload.hidden, false);
    await p.enviar();
    assert.equal(p.llamadas.length, 1);
});

test('un límite 429 informa sin alterar el carrito ni reenviar el agregado', async () => {
    const p = pagina();
    p.cola.push(respuesta({ success: false, mensaje: 'Demasiadas solicitudes.' }, 429));
    await p.enviar();
    assert.equal(p.contador.textContent, '0');
    assert.equal(p.partes.announcement.textContent, 'Demasiadas solicitudes.');
    assert.equal(p.llamadas.length, 1);
    assert.equal(p.botones[0].disabled, false);
});

test('HTML, redirecciones, errores de servidor y JSON incompleto exigen revisión', async () => {
    for (const fallo of [
        respuesta({}, 200, { headers: { get: () => 'text/html' } }),
        respuesta({}, 200, { redirected: true }),
        respuesta({ success: false, mensaje: 'Error.' }, 500),
        respuesta({ csrf: null }),
        respuesta({ carrito: null }),
        respuesta({ carrito: { cantidad: 1, total: 'no es un precio' } }),
    ]) {
        const p = pagina();
        p.cola.push(fallo);
        await p.enviar();
        assert.equal(p.contador.textContent, '0');
        assert.equal(p.partes.reload.hidden, false);
        assert.ok(p.botones.every(boton => boton.disabled));
    }
});

test('seguir comprando cierra el aviso y devuelve el foco al botón original', async () => {
    const p = pagina();
    await p.enviar(1);
    p.partes.continue.eventos.click();
    assert.equal(p.aviso.hidden, true);
    assert.equal(p.botones[1].enfocado, true);
    await p.enviar();
    let prevenido = false;
    p.aviso.eventos.keydown({ key: 'Escape', preventDefault: () => { prevenido = true; } });
    assert.equal(prevenido, true);
    assert.equal(p.aviso.hidden, true);
});

test('sin Fetch o FormData se conserva el formulario tradicional', () => {
    for (const opciones of [{ fetchDisponible: false }, { formDataDisponible: false }]) {
        const p = pagina(opciones);
        assert.equal(p.forms[0].eventos.submit, undefined);
        assert.equal(p.aviso.hidden, true);
    }
});

test('no envía formularios inválidos y no reactiva botones ya deshabilitados', async () => {
    const invalida = pagina({ valida: false });
    await invalida.enviar();
    assert.equal(invalida.llamadas.length, 0);
    const p = pagina();
    p.botones[1].disabled = true;
    await p.enviar();
    assert.equal(p.botones[1].disabled, true);
    assert.equal(p.botones[0].disabled, false);
});

test('al volver desde la caché del navegador recarga el contador y el token', () => {
    const p = pagina();
    p.eventosVentana.pageshow({ persisted: false });
    assert.equal(p.recargas(), 0);
    p.eventosVentana.pageshow({ persisted: true });
    assert.equal(p.recargas(), 1);
});
