const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');
const { resolve } = require('node:path');

function formulario(anterior = {}) {
    const ids = [
        'tipodoc', 'numerodoc', 'nombres', 'apellidos', 'razonsocial', 'idpersona',
        'telefono', 'email', 'direccion', 'modalTitulo', 'modalCliente',
        'contenedorNombres', 'contenedorApellidos', 'contenedorRazonSocial', 'clienteConfig',
    ];
    const campos = Object.fromEntries(ids.map(id => [id, { value: '', hidden: false }]));
    campos.tipodoc.value = 'DNI';
    campos.clienteConfig.dataset = { form: JSON.stringify(anterior) };
    let aperturas = 0;
    const contexto = {
        document: { getElementById: id => campos[id] },
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show: () => aperturas++ }) } },
    };
    runInNewContext(readFileSync(resolve(__dirname, '../../public/js/clientes/index.js'), 'utf8'), contexto);
    return { campos, contexto, aperturas: () => aperturas };
}

test('RUC exige razón social y desactiva nombres y apellidos', () => {
    const { campos, contexto } = formulario();
    campos.tipodoc.value = 'RUC';
    contexto.adaptarCamposDoc();
    assert.equal(campos.razonsocial.required, true);
    assert.equal(campos.razonsocial.disabled, false);
    assert.equal(campos.nombres.required, false);
    assert.equal(campos.apellidos.required, false);
    assert.equal(campos.nombres.disabled, true);
    assert.equal(campos.apellidos.disabled, true);
    assert.equal(campos.contenedorRazonSocial.hidden, false);
    assert.equal(campos.numerodoc.pattern, '[0-9]{11}');
});

test('DNI vuelve a exigir nombres y apellidos sin razón social', () => {
    const { campos, contexto } = formulario();
    campos.tipodoc.value = 'RUC';
    contexto.adaptarCamposDoc();
    campos.tipodoc.value = 'DNI';
    contexto.adaptarCamposDoc();
    assert.equal(campos.nombres.required, true);
    assert.equal(campos.apellidos.required, true);
    assert.equal(campos.razonsocial.required, false);
    assert.equal(campos.razonsocial.disabled, true);
    assert.equal(campos.numerodoc.pattern, '[0-9]{8}');
});

test('CE y pasaporte admiten documentos alfanuméricos sin truncarlos', () => {
    const { campos, contexto } = formulario();
    for (const tipo of ['CE', 'PASAPORTE']) {
        campos.tipodoc.value = tipo;
        campos.numerodoc.value = 'AB123456789012345';
        contexto.adaptarCamposDoc();
        assert.equal(campos.numerodoc.maxLength, 20);
        assert.equal(campos.numerodoc.inputMode, 'text');
        assert.equal(campos.numerodoc.value, 'AB123456789012345');
    }
});

test('editar RUC muestra la razón social en lugar de nombres vacíos', () => {
    const { campos, contexto, aperturas } = formulario();
    contexto.editarCliente({ idpersona: 7, tipodoc: 'RUC', numerodoc: '20123456789', razonsocial: 'Redes 123 SAC', nombres: null });
    assert.equal(campos.idpersona.value, 7);
    assert.equal(campos.razonsocial.value, 'Redes 123 SAC');
    assert.equal(campos.modalTitulo.textContent, 'Editar Cliente');
    assert.equal(aperturas(), 1);
});

test('editar RUC antiguo recupera el nombre sin alterar el registro original', () => {
    const { campos, contexto } = formulario();
    const cliente = { idpersona: 7, tipodoc: 'RUC', nombres: 'Empresa anterior', apellidos: null };
    contexto.editarCliente(cliente);
    assert.equal(campos.razonsocial.value, 'Empresa anterior');
    assert.equal(cliente.nombres, 'Empresa anterior');
});

test('limpiar formulario no conserva los datos de otra empresa', () => {
    const { campos, contexto } = formulario();
    contexto.editarCliente({ idpersona: 7, tipodoc: 'RUC', razonsocial: 'Empresa anterior' });
    contexto.limpiarFormulario();
    assert.equal(campos.razonsocial.value, '');
    assert.equal(campos.idpersona.value, '');
    assert.equal(campos.tipodoc.value, 'DNI');
    assert.equal(campos.nombres.disabled, false);
});

test('recupera los datos y reabre el formulario después de un error', () => {
    const { campos, aperturas } = formulario({ tipodoc: 'RUC', razonsocial: 'Empresa pendiente', numerodoc: '123' });
    assert.equal(campos.razonsocial.value, 'Empresa pendiente');
    assert.equal(campos.numerodoc.value, '123');
    assert.equal(campos.modalTitulo.textContent, 'Nuevo Cliente');
    assert.equal(aperturas(), 1);
});
