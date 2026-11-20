function adaptarCamposDoc() {
    const tipo = document.getElementById('tipodoc').value;
    const juridica = tipo === 'RUC';
    document.getElementById('contenedorNombres').hidden = juridica;
    document.getElementById('contenedorApellidos').hidden = juridica;
    document.getElementById('contenedorRazonSocial').hidden = !juridica;
    for (const id of ['nombres', 'apellidos']) {
        const campo = document.getElementById(id);
        campo.disabled = juridica;
        campo.required = !juridica;
    }
    const razonSocial = document.getElementById('razonsocial');
    razonSocial.disabled = !juridica;
    razonSocial.required = juridica;

    const documento = document.getElementById('numerodoc');
    const longitud = tipo === 'DNI' ? 8 : juridica ? 11 : null;
    documento.minLength = longitud ?? 5;
    documento.maxLength = longitud ?? 20;
    documento.pattern = longitud ? `[0-9]{${longitud}}` : '[A-Za-z0-9]{5,20}';
    documento.inputMode = longitud ? 'numeric' : 'text';
    documento.title = longitud
        ? `Ingrese exactamente ${longitud} dígitos para ${tipo}.`
        : 'Ingrese entre 5 y 20 caracteres alfanuméricos.';
}

function limpiarFormulario() {
    document.getElementById('modalTitulo').textContent = 'Nuevo Cliente';
    for (const id of ['idpersona', 'numerodoc', 'nombres', 'apellidos', 'razonsocial', 'telefono', 'email', 'direccion']) {
        document.getElementById(id).value = '';
    }
    document.getElementById('tipodoc').value = 'DNI';
    adaptarCamposDoc();
}

function editarCliente(cliente) {
    limpiarFormulario();
    document.getElementById('modalTitulo').textContent = cliente.idpersona ? 'Editar Cliente' : 'Nuevo Cliente';
    for (const id of ['idpersona', 'numerodoc', 'nombres', 'apellidos', 'telefono', 'email', 'direccion']) {
        document.getElementById(id).value = cliente[id] ?? '';
    }
    document.getElementById('tipodoc').value = cliente.tipodoc || 'DNI';
    document.getElementById('razonsocial').value = cliente.razonsocial
        || (cliente.tipodoc === 'RUC' ? `${cliente.nombres || ''} ${cliente.apellidos || ''}`.trim() : '');
    adaptarCamposDoc();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCliente')).show();
}

const formularioCliente = JSON.parse(document.getElementById('clienteConfig').dataset.form);
if (Object.keys(formularioCliente).length) {
    editarCliente(formularioCliente);
} else {
    adaptarCamposDoc();
}
