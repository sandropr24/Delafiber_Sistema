const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

function tienda({ modalDisponible = true, foto = true } = {}) {
    const eventos = {};
    const enlace = { addEventListener: (nombre, accion) => { eventos[`enlace:${nombre}`] = accion; } };
    let aperturas = 0;
    let cierres = 0;
    const dialogo = {
        addEventListener: (nombre, accion) => { eventos[`dialogo:${nombre}`] = accion; },
        getBoundingClientRect: () => ({ left: 20, right: 300, top: 30, bottom: 400 }),
        close: () => { cierres++; },
    };
    if (modalDisponible) dialogo.showModal = () => { aperturas++; };
    const document = {
        addEventListener: (nombre, accion) => { eventos[nombre] = accion; },
        querySelector: selector => ({
            '[data-image-zoom]': foto ? enlace : null,
            '#imagen-producto': foto ? dialogo : null,
        })[selector] ?? null,
    };
    runInNewContext(readFileSync(resolve(__dirname, '../../public/js/tienda.js'), 'utf8'), { document });
    eventos.DOMContentLoaded();
    return { eventos, enlace, dialogo, aperturas: () => aperturas, cierres: () => cierres };
}

test('amplía la foto existente sin navegar cuando hay soporte de dialog', () => {
    const pagina = tienda();
    let prevenido = false;
    pagina.eventos['enlace:click']({ preventDefault: () => { prevenido = true; } });
    assert.equal(prevenido, true);
    assert.equal(pagina.aperturas(), 1);
});

test('respeta abrir la foto en otra pestaña con teclas modificadoras', () => {
    const pagina = tienda();
    for (const tecla of ['ctrlKey', 'metaKey', 'shiftKey', 'altKey']) {
        pagina.eventos['enlace:click']({
            [tecla]: true,
            preventDefault: () => assert.fail('No debe cancelar el enlace modificado'),
        });
    }
    assert.equal(pagina.aperturas(), 0);
});

test('clic en el fondo cierra la imagen pero clic dentro no la cierra', () => {
    const pagina = tienda();
    pagina.eventos['dialogo:click']({ target: pagina.dialogo, clientX: 25, clientY: 35 });
    assert.equal(pagina.cierres(), 0);
    pagina.eventos['dialogo:click']({ target: {}, clientX: 5, clientY: 5 });
    assert.equal(pagina.cierres(), 0);
    pagina.eventos['dialogo:click']({ target: pagina.dialogo, clientX: 5, clientY: 5 });
    assert.equal(pagina.cierres(), 1);
});

test('sin soporte de dialog conserva el enlace normal a la imagen', () => {
    const pagina = tienda({ modalDisponible: false });
    assert.equal(pagina.eventos['enlace:click'], undefined);
});

test('una ficha sin foto y páginas sin checkout no generan errores', () => {
    const pagina = tienda({ foto: false });
    assert.equal(pagina.aperturas(), 0);
    assert.equal(pagina.eventos['enlace:click'], undefined);
});
