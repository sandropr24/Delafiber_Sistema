
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
    const buscador = $('buscarArticulo');
    const grupos = $$('.articulo-grupo');
    const botonesFiltro = $$('#filtrosArticulos [data-tipo]');
    let tipoVisible = 'todos';
    const normalizar = (texto) => String(texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    function filtrarArticulos() {
        const texto = normalizar(buscador?.value.trim());
        let encontrados = 0;
        grupos.forEach((grupo) => {
            const tipo = grupo.dataset.tipo;
            const visible = tipoVisible === 'todos' || tipoVisible === tipo;
            grupo.classList.toggle('d-none', !visible);
            const selector = grupo.querySelector('select');
            [...selector.options].forEach((opcion, i) => {
                if (!i) return;
                const coincide = normalizar(opcion.textContent).includes(texto);
                opcion.hidden = !coincide;
                opcion.disabled = !coincide;
                if (coincide && visible) encontrados++;
            });
            if (selector.selectedOptions[0]?.disabled) {
                selector.value = '';
                if (activo === selector) { activo = null; $('inputPrecio').value = ''; }
            }
        });
        $('resultadoBusqueda').textContent = `${encontrados} artículo(s) disponibles en el filtro.`;
    }
    buscador?.addEventListener('input', filtrarArticulos);
    botonesFiltro.forEach((boton) => boton.addEventListener('click', () => {
        tipoVisible = boton.dataset.tipo;
        botonesFiltro.forEach((b) => {
            b.classList.toggle('btn-primary', b === boton);
            b.classList.toggle('btn-outline-secondary', b !== boton);
        });
        filtrarArticulos();
    }));
    filtrarArticulos();


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

    function consumo(id, cantidad, esCombo, controlaStock = false) {
        const requerido = {};
        const partes = esCombo ? componentes(id) : [{ idproducto: id, cantidad: 1, controlastock: controlaStock ? 1 : 0 }];
        for (const p of partes) {
            if (String(p.tipo || '').toUpperCase() === 'SERVICIO' || Number(p.controlastock) !== 1) continue;
            const pid = String(p.idproducto);
            requerido[pid] = (requerido[pid] || 0) + Number(p.cantidad) * cantidad;
        }
        return requerido;
    }

    function validarInventario(id, cantidad, esCombo, controlaStock = false) {
        if (esCombo && !componentes(id).length) return 'Este combo no tiene componentes configurados.';
        if (esCombo && componentes(id).some((p) => String(p.tipo || '').toUpperCase() === 'COMBO')) {
            return 'Un combo no puede contener otro combo.';
        }

        const total = {};
        const sumar = (datos) => Object.entries(datos).forEach(([pid, n]) => {
            total[pid] = (total[pid] || 0) + n;
        });

        filas().forEach((row) => {
            if (row.dataset.idproducto === String(id)) return;
            sumar(consumo(row.dataset.idproducto, Number(row.querySelector('.input-cantidad').value),
                row.dataset.combo === '1', row.dataset.controlastock === '1'));
        });

        sumar(consumo(id, cantidad, esCombo, controlaStock));

        for (const [pid, n] of Object.entries(total)) {
            if (n > disponibles(pid) + 0.00001) {
                return `El producto ${pid} necesita ${n} unidades y solo hay ${disponibles(pid)} disponibles.`;
            }
        }
        return '';
    }

    function partesConSerie(row, cantidad) {
        if (row.dataset.combo !== '1') {
            return row.dataset.controlaserie === '1'
                ? [{ idproducto: row.dataset.idproducto, nombre: row.querySelector('.nombre-producto').textContent, cantidad }]
                : [];
        }

        const agrupadas = {};
        componentes(row.dataset.idproducto).filter((p) => Number(p.controlaserie) === 1).forEach((p) => {
            const pid = String(p.idproducto);
            if (!agrupadas[pid]) {
                agrupadas[pid] = { idproducto: pid, nombre: p.nombre || `Producto ${pid}`, cantidad: 0 };
            }
            agrupadas[pid].cantidad += Number(p.cantidad) * cantidad;
        });
        return Object.values(agrupadas);
    }

    function pintarSeries(row, id, cantidad) {
        const destino = row.querySelector('.series-seleccionadas');
        if (!destino) return;

        const anteriores = {};
        $$('.selector-serie', destino).forEach((el) => {
            (anteriores[el.dataset.producto] ||= []).push(el.value);
        });
        destino.replaceChildren();

        partesConSerie(row, cantidad).forEach((parte) => {
            const bloque = document.createElement('div');
            bloque.className = 'mt-2';

            const titulo = document.createElement('div');
            titulo.className = 'small text-secondary fw-semibold';
            titulo.textContent = `Series: ${parte.nombre}`;
            bloque.appendChild(titulo);

            for (let i = 0; i < parte.cantidad; i++) {
                const selector = document.createElement('select');
                selector.name = row.dataset.combo === '1'
                    ? `detalles[${id}][series_componentes][${parte.idproducto}][]`
                    : `detalles[${id}][series][]`;
                selector.className = 'form-select form-select-sm mt-1 selector-serie';
                selector.dataset.producto = parte.idproducto;
                selector.required = true;
                selector.add(new Option(`Serie ${i + 1}...`, ''));

                unidades(parte.idproducto).forEach((item) => {
                    selector.add(new Option(item.numero, String(item.id)));
                });
                selector.value = anteriores[parte.idproducto]?.[i] || '';
                bloque.appendChild(selector);
            }
            destino.appendChild(bloque);
        });
    }

    function cantidadValida(row, cantidad) {
        const id = row.dataset.idproducto;
        if (!Number.isInteger(cantidad) || cantidad < 1) return 'Ingrese una cantidad entera mayor que cero.';

        const error = validarInventario(id, cantidad, row.dataset.combo === '1', row.dataset.controlastock === '1');
        if (error) return error;

        for (const p of partesConSerie(row, cantidad)) {
            if (!Number.isInteger(p.cantidad) || p.cantidad < 1 || p.cantidad > unidades(p.idproducto).length) {
                return `No hay suficientes series disponibles para ${p.nombre}.`;
            }
        }
        return '';
    }

    function descuentoVenta() {
        const tipo = $('tipoDescuento')?.value || 'NINGUNO';
        const valorTexto = $('valorDescuento')?.value || '0';
        const valor = Number(valorTexto);
        const motivo = $('motivoDescuento')?.value.trim() || '';
        const activo = tipo !== 'NINGUNO';
        const subtotalCentimos = filas().reduce((sum, row) => {
            const cantidad = Number(row.querySelector('.input-cantidad').value);
            const precio = Number(row.querySelector('[name$="[precioventa]"]').value);
            return sum + Math.round(cantidad * precio * 100);
        }, 0);
        const error = $('errorDescuento');
        let mensaje = '';
        let centimos = 0;
        if (activo) {
            if (!Number.isFinite(valor) || valor <= 0 || !/^\d+(?:\.\d{1,2})?$/.test(valorTexto)) {
                mensaje = 'Ingresa un descuento válido mayor que cero (máximo dos decimales).';
            } else if (motivo.length < 5 || motivo.length > 300) {
                mensaje = 'Indica un motivo de al menos 5 caracteres.';
            } else if (tipo === 'PORCENTAJE') {
                if (valor > 100) mensaje = 'El porcentaje no puede superar 100 %.';
                else centimos = Math.round(subtotalCentimos * Math.round(valor * 100) / 10000);
            } else {
                centimos = Math.round(valor * 100);
            }
            if (centimos >= subtotalCentimos && subtotalCentimos > 0) {
                mensaje = 'El descuento debe ser menor que el subtotal.';
            }
        }
        if (error) {
            error.textContent = mensaje;
            error.classList.toggle('d-none', !mensaje);
        }
        return {subtotalCentimos, descuentoCentimos: centimos, error: mensaje};
    }

    function totalizar() {
        const calculo = descuentoVenta();
        const subtotal = calculo.subtotalCentimos / 100;
        const descuento = calculo.descuentoCentimos / 100;
        const total = Math.max(0, subtotal - descuento);
        $('lblSubtotal').textContent = money(subtotal);
        $('lblDescuento').textContent = `- ${money(descuento)}`;
        $('lblTotal').textContent = money(total);
        $('inputTotal').value = total.toFixed(2);
        totalizarPagos();
    }

    $('tipoDescuento')?.addEventListener('change', () => {
        const activo = $('tipoDescuento').value !== 'NINGUNO';
        $('campoValorDescuento').hidden = !activo;
        $('campoMotivoDescuento').hidden = !activo;
        $('valorDescuento').disabled = !activo;
        $('motivoDescuento').disabled = !activo;
        $('valorDescuento').required = activo;
        $('motivoDescuento').required = activo;
        if (!activo) {
            $('valorDescuento').value = '0.00';
            $('motivoDescuento').value = '';
        }
        totalizar();
    });
    $('valorDescuento')?.addEventListener('input', totalizar);
    $('motivoDescuento')?.addEventListener('input', totalizar);

    function actualizarFila(row) {
        const input = row.querySelector('.input-cantidad');
        const cantidad = Number(input.value);
        const error = cantidadValida(row, cantidad);

        if (error) {
            alertar(row.dataset.combo === '1' ? 'Combo no disponible' : 'Cantidad inválida', error);
            input.value = row.dataset.ultimaCantidad || 1;
            totalizar();
            return;
        }

        row.dataset.ultimaCantidad = String(cantidad);
        pintarSeries(row, row.dataset.idproducto, cantidad);
        row.querySelector('.lbl-subtotal').textContent = money(
            cantidad * Number(row.querySelector('[name$="[precioventa]"]').value)
        );
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

        if (!Number.isInteger(cantidad) || cantidad < 1) {
            return alertar('Cantidad inválida', 'Ingrese una cantidad entera mayor que cero.');
        }
        if (!Number.isFinite(precio) || precio <= 0) {
            return alertar('Precio inválido', 'Ingrese un precio válido mayor que cero.');
        }

        const error = validarInventario(id, acumulada, esCombo, controlaStock);
        if (error) return alertar('Inventario insuficiente', error);

        if (existente) {
            const anterior = Number(existente.querySelector('.input-cantidad').value);
            existente.querySelector('.input-cantidad').value = acumulada;
            const errorCantidad = cantidadValida(existente, acumulada);

            if (errorCantidad) {
                existente.querySelector('.input-cantidad').value = anterior;
                return alertar('Artículo no disponible', errorCantidad);
            }
            actualizarFila(existente);
        } else {
            const row = document.createElement('tr');
            row.id = `row-${id}`;
            row.dataset.idproducto = id;
            row.dataset.combo = esCombo ? '1' : '0';
            row.dataset.controlastock = controlaStock ? '1' : '0';
            row.dataset.controlaserie = controlaSerie ? '1' : '0';
            row.dataset.ultimaCantidad = String(cantidad);

            row.innerHTML = `
                <td><span class="fw-semibold text-dark nombre-producto"></span>
                    <input type="hidden" name="detalles[${id}][idproducto]" value="${id}">
                    <div class="series-seleccionadas mt-2"></div></td>
                <td class="text-center"><input type="number" name="detalles[${id}][cantidad]" value="${cantidad}" min="1"
                    class="form-control form-control-sm text-center input-cantidad"></td>
                <td class="text-end text-secondary">${money(precio)}
                    <input type="hidden" name="detalles[${id}][precioventa]" value="${precio.toFixed(2)}"></td>
                <td class="text-end fw-bold text-dark lbl-subtotal">${money(cantidad * precio)}</td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-eliminar-fila">
                    <i class="bi bi-trash3 fs-5"></i></button></td>`;

            row.querySelector('.nombre-producto').textContent = opcion.dataset.nombre || opcion.textContent.trim();
            carrito.appendChild(row);

            const errorCantidad = cantidadValida(row, cantidad);
            if (errorCantidad) {
                row.remove();
                if (!filas().length) carrito.innerHTML = avisoVacio;
                return alertar('Artículo no disponible', errorCantidad);
            }

            $('filaVacia')?.remove();
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
        if (!event.target.matches('.input-cantidad')) return;
        const row = event.target.closest('tr');
        const cantidad = Number(event.target.value);

        if (Number.isInteger(cantidad) && cantidad > 0) {
            row.querySelector('.lbl-subtotal').textContent = money(
                cantidad * Number(row.querySelector('[name$="[precioventa]"]').value)
            );
            totalizar();
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

        const row = document.createElement('tr');
        row.id = `pago-${tipo.value}`;
        row.innerHTML = `
            <td><span class="fw-semibold text-dark small nombre-metodo"></span>
                <input type="hidden" name="pagos[${tipo.value}][idtipopago]" value="${tipo.value}"></td>
            <td class="text-end"><input type="number" name="pagos[${tipo.value}][monto]" value="${monto.toFixed(2)}"
                min="0.01" step="0.01" class="form-control form-control-sm text-end input-monto-pago"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-eliminar-pago">
                <i class="bi bi-trash3 fs-5"></i></button></td>`;

        row.querySelector('.nombre-metodo').textContent = tipo.selectedOptions[0].dataset.nombre;
        $('filaSinPagos')?.remove();
        $('pagosBody').appendChild(row);
        tipo.value = '';
        $('inputMontoPago').value = '';
        totalizarPagos();
    });

    $('pagosBody')?.addEventListener('input', (event) => {
        if (event.target.matches('.input-monto-pago')) totalizarPagos();
    });

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

        if (!/^(?:\d{8}|\d{11})$/.test(documento)) {
            return alertar('Documento inválido', 'Ingrese un DNI de 8 dígitos o RUC de 11 dígitos.');
        }
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
                const confirmar = await Swal.fire({
                    icon: 'question', title: 'Cliente ya registrado',
                    text: 'Este documento ya pertenece a un cliente. ¿Deseas seleccionarlo?',
                    showCancelButton: true, confirmButtonText: 'Sí, seleccionar', cancelButtonText: 'Cancelar'
                });
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

            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'Cliente seleccionado correctamente', showConfirmButton: false, timer: 2500
            });
        } catch (error) {
            console.error('Error en registro rápido:', error);
            alertar('Error de registro', error.message, 'error');
        } finally {
            boton.disabled = false;
            boton.innerHTML = original;
        }
    });

    $('formVenta')?.addEventListener('submit', (event) => {
        for (const row of filas()) {
            const cantidad = Number(row.querySelector('.input-cantidad').value);
            const error = cantidadValida(row, cantidad);
            if (error) {
                event.preventDefault();
                return alertar('Inventario', error);
            }

            const esperadas = partesConSerie(row, cantidad).reduce((n, p) => n + p.cantidad, 0);
            if ($$('.selector-serie', row).length !== esperadas) {
                event.preventDefault();
                return alertar('Series', 'Confirma la cantidad del artículo para actualizar las series.');
            }
        }

        const elegidas = $$('.selector-serie', carrito).map((select) => select.value);
        if (elegidas.some((id) => !id) || new Set(elegidas).size !== elegidas.length) {
            event.preventDefault();
            return alertar('Números de serie', 'Selecciona una serie diferente para cada unidad.');
        }

        if (!$('selectCliente')?.value || $('selectCliente').value === 'false') {
            event.preventDefault();
            return alertar('Cliente requerido', 'Seleccione un cliente válido.');
        }

        const validacionDescuento = descuentoVenta();
        if (validacionDescuento.error) {
            event.preventDefault();
            return alertar('Descuento inválido', validacionDescuento.error);
        }

        const total = Number($('inputTotal').value || 0);
        const pagado = $$('.input-monto-pago').reduce((sum, input) => sum + Number(input.value || 0), 0);
        if (total <= 0) {
            event.preventDefault();
            return alertar('Carrito vacío', 'Debe agregar al menos un artículo.');
        }

        if ($('tipoentrega')?.value === 'DOMICILIO') {
            if (!$('direccion_entrega')?.value.trim()) {
                event.preventDefault();
                return alertar('Dirección requerida', 'Ingrese la dirección de entrega.');
            }
            if (!$('telefono_entrega')?.value.trim()) {
                event.preventDefault();
                return alertar('Teléfono requerido', 'Ingrese el teléfono de contacto.');
            }
        }

        if (Math.round(pagado * 100) !== Math.round(total * 100)) {
            event.preventDefault();
            return alertar('Pago incorrecto', `Los pagos (${money(pagado)}) deben coincidir con el total (${money(total)}).`, 'error');
        }
    });
});
