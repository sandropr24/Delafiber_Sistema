
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
    const alertar = (title, text, icon = 'warning') => Swal.fire({ icon, title, text });
    const leer = (name, fallback = {}) => {
        try { return JSON.parse($('ventaConfig')?.getAttribute(`data-${name}`) || JSON.stringify(fallback)); }
        catch (error) { console.error(`Datos inválidos: ${name}`, error); return fallback; }
    };
    const stock = leer('stock');
    const series = leer('series');
    const combosRaw = leer('combos', []);
    const combos = Array.isArray(combosRaw)
        ? combosRaw.reduce((acc, c) => { (acc[String(c.idcombo)] ||= []).push(c); return acc; }, {})
        : combosRaw || {};
    const local = $('selectLocal');
    const carrito = $('carritoBody');
    const selectores = [$('selectProducto'), $('selectServicio'), $('selectCombo')];
    let activo = null;
    const unidades = (id) => series[local?.value]?.[id] || [];
    const disponibles = (id) => Number(stock[local?.value]?.[id] ?? 0);
    const componentes = (id) => Array.isArray(combos[id]) ? combos[id] : [];
    const filas = () => $$('.input-cantidad', carrito).map((input) => input.closest('tr'));
    const fila = (id) => $(`row-${id}`);
    const avisoVacio = '<tr id="filaVacia"><td colspan="5" class="text-center text-muted py-5">No se han agregado productos a la venta.</td></tr>';
    const avisoPagos = '<tr id="filaSinPagos"><td colspan="3" class="text-center text-muted py-2 small">Sin pagos asignados.</td></tr>';

    function entrega() {
        const domicilio = $('tipoentrega')?.value === 'DOMICILIO';
        $('camposEntrega')?.classList.toggle('d-none', !domicilio);
        [$('direccion_entrega'), $('telefono_entrega')].forEach((el) => { if (el) el.required = domicilio; });
    }
    $('tipoentrega')?.addEventListener('change', entrega);
    entrega();

    selectores.forEach((selector) => selector?.addEventListener('change', () => {
        if (!selector.value) return;
        activo = selector;
        selectores.filter((otro) => otro !== selector).forEach((otro) => { if (otro) otro.value = ''; });
        $('inputPrecio').value = Number(selector.selectedOptions[0]?.dataset.precio || 0).toFixed(2);
    }));
    local?.addEventListener('change', () => {
        if (!filas().length) return;
        carrito.innerHTML = avisoVacio;
        totalizar();
        alertar('Local actualizado', 'Se limpiaron los artículos porque el inventario y las series pertenecen a un local específico.', 'info');
    });

    function validarCombo(id, cantidad) {
        const partes = componentes(id);
        if (!partes.length) return 'Este combo no tiene componentes configurados.';
        const requerido = {};
        for (const parte of partes) {
            if (String(parte.tipo || '').toUpperCase() === 'COMBO') return 'Un combo no puede contener otro combo.';
            if (String(parte.tipo || '').toUpperCase() === 'SERVICIO' || Number(parte.controlastock) !== 1) continue;
            const pid = String(parte.idproducto);
            requerido[pid] = (requerido[pid] || 0) + Number(parte.cantidad) * cantidad;
        }
        for (const [pid, cantidadNecesaria] of Object.entries(requerido)) {
            if (cantidadNecesaria > disponibles(pid)) return `El combo requiere ${cantidadNecesaria} unidades del producto ${pid}, pero solo hay ${disponibles(pid)} disponibles.`;
        }
        return '';
    }

    function pintarSeries(row, id, cantidad) {
        const destino = row.querySelector('.series-seleccionadas');
        if (!destino) return;
        const anteriores = $$('select', destino).map((el) => el.value);
        destino.replaceChildren();
        for (let i = 0; i < cantidad; i++) {
            const selector = document.createElement('select');
            selector.name = `detalles[${id}][series][]`;
            selector.className = 'form-select form-select-sm mt-1 selector-serie';
            selector.required = true;
            selector.add(new Option(`Serie de unidad ${i + 1}...`, ''));
            unidades(id).forEach((item) => selector.add(new Option(item.numero, String(item.id))));
            selector.value = anteriores[i] || '';
            destino.appendChild(selector);
        }
    }

    function cantidadValida(row, cantidad) {
        const id = row.dataset.idproducto;
        if (!Number.isInteger(cantidad) || cantidad < 1) return 'Ingrese una cantidad entera mayor que cero.';
        if (row.dataset.controlastock === '1' && cantidad > disponibles(id)) return `Solo hay ${disponibles(id)} unidades disponibles.`;
        if (row.dataset.combo === '1') return validarCombo(id, cantidad);
        if (row.querySelector('.series-seleccionadas') && cantidad > unidades(id).length) return `Solo hay ${unidades(id).length} equipos con serie disponible.`;
        return '';
    }

    function totalizar() {
        const total = filas().reduce((sum, row) => sum + Number(row.querySelector('.input-cantidad').value) * Number(row.querySelector('[name$="[precioventa]"]').value), 0);
        $('lblTotal').textContent = money(total);
        $('inputTotal').value = total.toFixed(2);
        totalizarPagos();
    }

    function actualizarFila(row) {
        const cantidad = Number(row.querySelector('.input-cantidad').value);
        const error = cantidadValida(row, cantidad);
        if (error) {
            alertar(row.dataset.combo === '1' ? 'Combo no disponible' : 'Cantidad inválida', error);
            row.querySelector('.input-cantidad').value = row.dataset.ultimaCantidad || 1;
            return;
        }
        row.dataset.ultimaCantidad = String(cantidad);
        pintarSeries(row, row.dataset.idproducto, cantidad);
        row.querySelector('.lbl-subtotal').textContent = money(cantidad * Number(row.querySelector('[name$="[precioventa]"]').value));
        totalizar();
    }

    $('btnAgregarItem')?.addEventListener('click', () => {
        if (!local?.value) return alertar('Local requerido', 'Seleccione un local antes de agregar artículos.');
        if (!activo?.value) return alertar('Artículo requerido', 'Seleccione un producto, servicio o combo.');
        const opcion = activo.selectedOptions[0];
        const id = activo.value;
        const cantidad = Number($('inputCantidad').value);
        const precio = Number($('inputPrecio').value);
        const esCombo = opcion.dataset.combo === '1';
        const controlaStock = opcion.dataset.controlastock === '1';
        const controlaSerie = opcion.dataset.controlaserie === '1';
        const existente = fila(id);
        const acumulada = cantidad + Number(existente?.querySelector('.input-cantidad').value || 0);
        if (!Number.isInteger(cantidad) || cantidad < 1) return alertar('Cantidad inválida', 'Ingrese una cantidad entera mayor que cero.');
        if (!Number.isFinite(precio) || precio <= 0) return alertar('Precio inválido', 'Ingrese un precio válido mayor que cero.');
        if (controlaStock && acumulada > disponibles(id)) return alertar('Stock insuficiente', `Solo hay ${disponibles(id)} unidades disponibles.`);
        if (esCombo) {
            const error = validarCombo(id, acumulada);
            if (error) return alertar('Combo no disponible', error);
        }
        if (controlaSerie && acumulada > unidades(id).length) return alertar('Series insuficientes', `Solo hay ${unidades(id).length} equipos con serie disponible.`);
        if (existente) {
            existente.querySelector('.input-cantidad').value = acumulada;
            actualizarFila(existente);
        } else {
            $('filaVacia')?.remove();
            const row = document.createElement('tr');
            row.id = `row-${id}`;
            row.dataset.idproducto = id;
            row.dataset.combo = esCombo ? '1' : '0';
            row.dataset.controlastock = controlaStock ? '1' : '0';
            row.dataset.ultimaCantidad = String(cantidad);
            row.innerHTML = `
                <td><span class="fw-semibold text-dark nombre-producto"></span>
                    <input type="hidden" name="detalles[${id}][idproducto]" value="${id}">
                    ${controlaSerie ? '<div class="series-seleccionadas mt-2"></div>' : ''}</td>
                <td class="text-center"><input type="number" name="detalles[${id}][cantidad]" value="${cantidad}" min="1"
                    max="${controlaStock ? disponibles(id) : 999999}" class="form-control form-control-sm text-center input-cantidad"></td>
                <td class="text-end text-secondary">${money(precio)}<input type="hidden" name="detalles[${id}][precioventa]" value="${precio.toFixed(2)}"></td>
                <td class="text-end fw-bold text-dark lbl-subtotal">${money(cantidad * precio)}</td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila"><i class="bi bi-trash3 fs-5"></i></button></td>`;
            row.querySelector('.nombre-producto').textContent = opcion.dataset.nombre || opcion.textContent.trim();
            carrito.appendChild(row);
            pintarSeries(row, id, cantidad);
            totalizar();
        }
        activo.value = '';
        activo = null;
        $('inputCantidad').value = 1;
        $('inputPrecio').value = '';
    });

    carrito?.addEventListener('change', (event) => {
        if (event.target.matches('.input-cantidad')) actualizarFila(event.target.closest('tr'));
    });
    carrito?.addEventListener('input', (event) => {
        if (event.target.matches('.input-cantidad')) {
            const row = event.target.closest('tr');
            const cantidad = Number(event.target.value);
            if (Number.isInteger(cantidad) && cantidad > 0) {
                row.querySelector('.lbl-subtotal').textContent = money(cantidad * Number(row.querySelector('[name$="[precioventa]"]').value));
                totalizar();
            }
        }
    });
    carrito?.addEventListener('click', (event) => {
        const boton = event.target.closest('.btn-eliminar-fila');
        if (!boton) return;
        boton.closest('tr').remove();
        if (!filas().length) carrito.innerHTML = avisoVacio;
        totalizar();
    });

    function totalizarPagos() {
        const total = $$('.input-monto-pago').reduce((sum, input) => sum + Number(input.value || 0), 0);
        $('lblTotalPagado').textContent = money(total);
        const diferencia = total - Number($('inputTotal').value || 0);
        const texto = $('lblFaltanteVuelto');
        texto.className = diferencia >= 0 ? 'text-info fw-bold' : 'text-danger fw-bold';
        texto.textContent = `${diferencia >= 0 ? 'Vuelto' : 'Faltante'}: ${money(Math.abs(diferencia))}`;
    }

    $('btnAgregarPago')?.addEventListener('click', () => {
        const tipo = $('selectTipoPago');
        const monto = Number($('inputMontoPago').value);
        if (!tipo.value) return alertar('Método requerido', 'Seleccione un método de pago.');
        if (!Number.isFinite(monto) || monto <= 0) return alertar('Monto inválido', 'Ingrese un monto mayor que cero.');
        if ($(`pago-${tipo.value}`)) return alertar('Método ya agregado', 'Este método ya está agregado.', 'info');
        $('filaSinPagos')?.remove();
        const row = document.createElement('tr');
        row.id = `pago-${tipo.value}`;
        row.innerHTML = `
            <td><span class="fw-semibold text-dark small nombre-metodo"></span><input type="hidden" name="pagos[${tipo.value}][idtipopago]" value="${tipo.value}"></td>
            <td class="text-end"><input type="number" name="pagos[${tipo.value}][monto]" value="${monto.toFixed(2)}" min="0.01" step="0.01" class="form-control form-control-sm text-end input-monto-pago"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-eliminar-pago"><i class="bi bi-trash3 fs-5"></i></button></td>`;
        row.querySelector('.nombre-metodo').textContent = tipo.selectedOptions[0].dataset.nombre;
        $('pagosBody').appendChild(row);
        tipo.value = '';
        $('inputMontoPago').value = '';
        totalizarPagos();
    });
    $('pagosBody')?.addEventListener('input', (event) => { if (event.target.matches('.input-monto-pago')) totalizarPagos(); });
    $('pagosBody')?.addEventListener('click', (event) => {
        const boton = event.target.closest('.btn-eliminar-pago');
        if (!boton) return;
        boton.closest('tr').remove();
        if (!$$('.input-monto-pago', $('pagosBody')).length) $('pagosBody').innerHTML = avisoPagos;
        totalizarPagos();
    });

    $('formClienteExpress')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const datos = new FormData(form);
        const documento = String(datos.get('documento') || '').trim();
        const nombre = String(datos.get('nombre') || '').trim();
        if (!/^(?:\d{8}|\d{11})$/.test(documento)) return alertar('Documento inválido', 'Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.');
        if (nombre.length < 2) return alertar('Nombre requerido', 'Ingrese el nombre o razón social.');
        const boton = $('btnGuardarClienteExpress');
        const original = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Verificando...';
        try {
            const respuesta = await fetch(form.dataset.urlExpress, {
                method: 'POST', body: datos,
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
            });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !['success', 'exists'].includes(resultado.status) || !resultado.idcliente) {
                throw new Error(resultado.message || 'No se pudo registrar el cliente.');
            }
            if (resultado.status === 'exists') {
                const confirmar = await Swal.fire({ icon: 'question', title: 'Cliente ya registrado', text: 'Este documento ya pertenece a un cliente. ¿Deseas seleccionarlo?', showCancelButton: true, confirmButtonText: 'Sí, seleccionar', cancelButtonText: 'Cancelar' });
                if (!confirmar.isConfirmed) return;
            }
            const cliente = $('selectCliente');
            const id = String(resultado.idcliente);
            let opcion = [...cliente.options].find((opt) => opt.value === id);
            if (!opcion) { opcion = new Option('', id); cliente.add(opcion); }
            opcion.textContent = `${resultado.nombre || nombre} (${resultado.documento || documento})`;
            cliente.value = id;
            cliente.dispatchEvent(new Event('change', { bubbles: true }));
            bootstrap.Modal.getInstance($('modalClienteExpress'))?.hide();
            form.reset();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Cliente seleccionado correctamente', showConfirmButton: false, timer: 2500 });
        } catch (error) {
            console.error('Error en registro rápido:', error);
            alertar('Error de registro', error.message, 'error');
        } finally {
            boton.disabled = false;
            boton.innerHTML = original;
        }
    });

    $('formVenta')?.addEventListener('submit', (event) => {
        const elegidas = $$('.selector-serie').map((select) => select.value);
        if (elegidas.some((id) => !id) || new Set(elegidas).size !== elegidas.length) {
            event.preventDefault();
            return alertar('Números de serie', 'Selecciona una serie diferente para cada unidad.');
        }
        if (!$('selectCliente')?.value || $('selectCliente').value === 'false') {
            event.preventDefault();
            return alertar('Cliente requerido', 'Seleccione un cliente válido.');
        }
        const total = Number($('inputTotal').value || 0);
        const pagado = $$('.input-monto-pago').reduce((sum, input) => sum + Number(input.value || 0), 0);
        if (total <= 0) { event.preventDefault(); return alertar('Carrito vacío', 'Debe agregar al menos un artículo.'); }
        if ($('tipoentrega')?.value === 'DOMICILIO') {
            if (!$('direccion_entrega')?.value.trim()) { event.preventDefault(); return alertar('Dirección requerida', 'Ingrese la dirección de entrega.'); }
            if (!$('telefono_entrega')?.value.trim()) { event.preventDefault(); return alertar('Teléfono requerido', 'Ingrese el teléfono de contacto.'); }
        }
        if (Math.round(pagado * 100) < Math.round(total * 100)) {
            event.preventDefault();
            alertar('Pago insuficiente', `El monto pagado (${money(pagado)}) es menor al total de la venta (${money(total)}).`, 'error');
        }
    });
});
