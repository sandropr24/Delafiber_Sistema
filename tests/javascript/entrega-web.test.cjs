
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

// Aísla los módulos reales sustituyendo sus dependencias y el DOM.
function modulo(archivo, dependencias, configurar = '') {
    const codigo = readFileSync(resolve(__dirname, '../../public/js/ventas/modules', archivo), 'utf8')
        .replace(/^import\s[\s\S]*?;\s*/gm, '')
        .replace(/\bexport\s+(?=async\s+function|function|const|let|class)/g, '');
    runInNewContext(codigo + '\n' + configurar, dependencias);
    return dependencias;
}

function campo(value = '') {
    return {
        value, disabled: false, required: false, error: '',
        classList: { toggle(clase, activo) { this[clase] = activo; } },
        setCustomValidity(mensaje) { this.error = mensaje; },
    };
}

function formulario({ pedido = 17, fuente = {}, old = {}, borrador = null, recargar = false } = {}) {
    const campos = {
        tipoentrega: campo('RECOJO_TIENDA'),
        direccion_entrega: campo(), telefono_entrega: campo(),
        referencia_entrega: campo(), idtecnico_entrega: campo(),
        camposEntrega: campo(), buscarClienteVenta: campo(),
        selectCliente: Object.assign(campo('1'), { selectedOptions: [{ textContent: 'Ana Cliente' }] }),
        detalleBorrador: campo(), avisoBorrador: campo(),
        ventaConfig: { dataset: { pedido: String(pedido), cotizacion: '0', iniciarPedido: recargar ? '1' : '0' } },
    };
    campos.formVenta = { elements: { namedItem: nombre => campos[nombre] || null } };
    const $ = id => campos[id] || null;
    const entrega = modulo('carrito.js', {
        $, document: { addEventListener() {} },
    }).entrega;
    entrega();
    const contexto = modulo('cliente-borrador.js', {
        $, $$: () => [], console,
        leer: nombre => nombre === 'old' ? old : nombre === 'pedido-datos' ? fuente : {},
        sessionStorage: { getItem: () => borrador ? JSON.stringify({ fecha: Date.now(), pedidoInicializado: true, datos: borrador }) : null },
        entrega, totalizar() {}, fila: () => null, restaurarArticulo() {},
    }, 'draftKey = "prueba"; selectores = []; carrito = { querySelector: () => null };');
    return { campos, restaurar: contexto.restaurarBorrador, entrega };
}

function propuesta(cambios = {}) {
    return {
        tipoentrega: 'DOMICILIO', direccion_entrega: 'Avenida Original 123',
        telefono_entrega: '999999999', referencia_entrega: '',
        ...cambios,
    };
}

test('la conversión precarga domicilio y muestra sus campos obligatorios', async () => {
    const { campos, restaurar } = formulario({ fuente: propuesta() });
    await restaurar();
    assert.equal(campos.tipoentrega.value, 'DOMICILIO');
    assert.equal(campos.direccion_entrega.value, 'Avenida Original 123');
    assert.equal(campos.telefono_entrega.value, '999999999');
    assert.equal(campos.camposEntrega.classList['d-none'], false);
    assert.equal(campos.direccion_entrega.required, true);
    assert.equal(campos.telefono_entrega.disabled, false);
});

test('la conversión de recojo no obliga a completar una dirección', async () => {
    const { campos, restaurar } = formulario({
        fuente: propuesta({ tipoentrega: 'RECOJO_TIENDA', direccion_entrega: '', telefono_entrega: '' }),
    });
    await restaurar();
    assert.equal(campos.tipoentrega.value, 'RECOJO_TIENDA');
    assert.equal(campos.direccion_entrega.required, false);
    assert.equal(campos.telefono_entrega.disabled, true);
    assert.equal(campos.idtecnico_entrega.disabled, true);
});

test('el borrador conserva los ajustes del vendedor sobre la propuesta web', async () => {
    const { campos, restaurar } = formulario({
        fuente: propuesta(),
        borrador: Object.entries(propuesta({ direccion_entrega: 'Dirección revisada 456', telefono_entrega: '988888888' })),
    });
    await restaurar();
    assert.equal(campos.direccion_entrega.value, 'Dirección revisada 456');
    assert.equal(campos.telefono_entrega.value, '988888888');
});

test('un POST fallido tiene prioridad sobre el borrador y sobre el pedido', async () => {
    const { campos, restaurar } = formulario({
        fuente: propuesta(),
        borrador: Object.entries(propuesta({ direccion_entrega: 'Dirección del borrador' })),
        old: propuesta({ direccion_entrega: 'Dirección del POST', telefono_entrega: '977777777' }),
    });
    await restaurar();
    assert.equal(campos.direccion_entrega.value, 'Dirección del POST');
    assert.equal(campos.telefono_entrega.value, '977777777');
});

test('recargar explícitamente el pedido descarta la propuesta editada del borrador', async () => {
    const { campos, restaurar } = formulario({
        fuente: propuesta(), recargar: true,
        borrador: Object.entries(propuesta({ tipoentrega: 'RECOJO_TIENDA' })),
    });
    await restaurar();
    assert.equal(campos.tipoentrega.value, 'DOMICILIO');
    assert.equal(campos.direccion_entrega.value, 'Avenida Original 123');
});

test('una venta administrativa sin datos web mantiene el recojo por defecto', async () => {
    const { campos, restaurar } = formulario({ pedido: 0 });
    await restaurar();
    assert.equal(campos.tipoentrega.value, 'RECOJO_TIENDA');
    assert.equal(campos.direccion_entrega.value, '');
});

test('no recorta una dirección web extensa y obliga a revisarla', async () => {
    const direccion = 'Á'.repeat(200);
    const { campos, restaurar } = formulario({ fuente: propuesta({ direccion_entrega: direccion }) });
    await restaurar();
    assert.equal(campos.direccion_entrega.value, direccion);
    assert.match(campos.direccion_entrega.error, /150 caracteres/);
});

test('una dirección de 150 caracteres Unicode cumple el mismo límite que PHP', async () => {
    const { campos, restaurar } = formulario({ fuente: propuesta({ direccion_entrega: 'Á'.repeat(150) }) });
    await restaurar();
    assert.equal(campos.direccion_entrega.error, '');
});

test('cambiar a recojo desactiva los campos incompatibles sin borrar la solicitud cargada', async () => {
    const direccion = 'A'.repeat(200);
    const { campos, restaurar, entrega } = formulario({ fuente: propuesta({ direccion_entrega: direccion }) });
    await restaurar();
    campos.tipoentrega.value = 'RECOJO_TIENDA';
    entrega();
    assert.equal(campos.direccion_entrega.disabled, true);
    assert.equal(campos.direccion_entrega.error, '');
    assert.equal(campos.direccion_entrega.value, direccion);
    campos.tipoentrega.value = 'DOMICILIO';
    entrega();
    assert.equal(campos.direccion_entrega.disabled, false);
    assert.match(campos.direccion_entrega.error, /150 caracteres/);
});

test('la referencia extensa también se valida sin truncarla', async () => {
    const { campos, restaurar } = formulario({ fuente: propuesta({ referencia_entrega: 'R'.repeat(151) }) });
    await restaurar();
    assert.match(campos.referencia_entrega.error, /150 caracteres/);
    assert.equal(campos.referencia_entrega.value.length, 151);
});
