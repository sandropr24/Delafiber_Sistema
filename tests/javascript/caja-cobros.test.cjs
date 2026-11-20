
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

function modulo(archivo, contexto, final = '') {
    const codigo = readFileSync(resolve(__dirname, '../../public/js/ventas/modules', archivo), 'utf8')
        .replace(/^import\s[\s\S]*?;\s*/gm, '')
        .replace(/\bexport\s+(?=async\s+function|function|const|let|class)/g, '');
    runInNewContext(codigo + '\n' + final, contexto);
    return contexto;
}

function elemento() {
    return {
        children: [], eventos: {}, value: '', error: '',
        appendChild(hijo) { this.children.push(hijo); },
        addEventListener(evento, callback) { this.eventos[evento] = callback; },
        setAttribute(clave, valor) { this[clave] = valor; },
        setCustomValidity(error) { this.error = error; },
    };
}

test('cada pago electrónico exige cuenta y archivo con nombre asociado a su fila', () => {
    const contexto = modulo('pagos.js', {
        document: { createElement: () => elemento() },
        leer: () => [{ idcuenta: 11, banco: '<script>BCP</script>', numerocuenta: '123' }],
    });
    const celda = elemento();
    contexto.crearSoporteElectronico(celda, '2');
    const [cuenta, label] = celda.children;
    const archivo = label.children[0];
    assert.equal(cuenta.required, true);
    assert.equal(cuenta.name, 'pagos[2][idcuenta]');
    assert.equal(cuenta.children[1].textContent, '<script>BCP</script> 123');
    assert.equal(cuenta.children[1].innerHTML, undefined);
    assert.equal(archivo.name, 'voucher_pagos[2]');
    assert.equal(archivo.type, 'file');
    assert.equal(archivo.required, true);
    assert.equal(archivo.accept, 'image/jpeg,image/png,image/webp');
    archivo.files = [{ size: 4 * 1024 * 1024 + 1 }];
    archivo.eventos.change();
    assert.match(archivo.error, /4 MB/);
    archivo.files = [{ size: 100 }];
    archivo.eventos.change();
    assert.equal(archivo.error, '');
});

test('la caja solo se selecciona para el local al que pertenece', () => {
    const campos = {
        selectLocal: { value: '2' }, avisoCajaVenta: { hidden: true },
        selectCaja: { value: '', options: [
            { value: '', dataset: {} },
            { value: '9', dataset: { local: '2' } },
        ] },
    };
    const contexto = modulo('pagos.js', { $: id => campos[id] });
    contexto.sincronizarCaja();
    assert.equal(campos.selectCaja.value, '9');
    assert.equal(campos.avisoCajaVenta.hidden, true);
    campos.selectLocal.value = '5';
    contexto.sincronizarCaja();
    assert.equal(campos.selectCaja.value, '');
    assert.equal(campos.selectCaja.options[1].disabled, true);
    assert.equal(campos.avisoCajaVenta.hidden, false);
});

test('no conserva una apertura antigua que ya no está en las opciones', () => {
    const campos = {
        selectLocal: { value: '2' }, avisoCajaVenta: {},
        selectCaja: { value: '8', options: [{ value: '' }, { value: '10', dataset: { local: '2' } }] },
    };
    modulo('pagos.js', { $: id => campos[id] }).sincronizarCaja();
    assert.equal(campos.selectCaja.value, '10');
});

test('el borrador no intenta serializar fotos ni tokens CSRF', () => {
    let guardado;
    const datos = [
        ['csrf_test_name', 'secreto'],
        ['idapertura', '9'],
        ['pagos[2][idcuenta]', '11'],
        ['voucher_pagos[2]', { name: 'foto.png', size: 100 }],
    ];
    class FormData { entries() { return datos.values(); } }
    const contexto = modulo('cliente-borrador.js', {
        $: () => ({}), FormData,
        sessionStorage: { setItem: (_, valor) => { guardado = JSON.parse(valor); } },
    }, 'draftKey = "prueba";');
    contexto.guardarBorrador();
    assert.deepEqual(guardado.datos, [['idapertura', '9'], ['pagos[2][idcuenta]', '11']]);
});

test('restaurar un pago recupera la cuenta pero no rellena un campo file', async () => {
    const cuenta = { value: '' };
    const campos = {
        ventaConfig: { dataset: { pedido: '0', cotizacion: '0' } },
        formVenta: { elements: { namedItem: () => null } },
        selectCliente: { value: '1', selectedOptions: [{ textContent: 'Cliente' }] },
        buscarClienteVenta: { setCustomValidity() {} },
        selectTipoPago: { value: '', dispatchEvent() {} },
        inputMontoPago: {}, inputReferenciaPago: {}, inputRecibidoPago: {},
        btnAgregarPago: { click() {} },
        'pago-2': { querySelector: () => cuenta },
        detalleBorrador: {}, avisoBorrador: {},
    };
    const contexto = modulo('cliente-borrador.js', {
        $: id => campos[id], $$: () => [],
        leer: nombre => nombre === 'old' ? {
            pagos: { 2: { idtipopago: '2', monto: '15.00', referencia: 'OP1', idcuenta: '11' } },
        } : {},
        entrega() {}, totalizar() {}, Event: class Event {},
    }, 'selectores = []; carrito = { querySelector: () => null };');
    await contexto.restaurarBorrador();
    assert.equal(cuenta.value, '11');
    assert.equal(campos.inputReferenciaPago.value, 'OP1');
});
