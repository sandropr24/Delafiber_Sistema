import { $, money, cantidadCorrecta } from '../ventas/modules/utils.js';
import { crearBusqueda } from '../ventas/modules/buscador.js';
import { calcular } from './calculos.js';

const config = $('cotizacionConfig'), form = $('formCotizacion');
const productos = JSON.parse(config.dataset.productos), catalogo = new Map(productos.map(p => [String(p.idproducto), p]));
const filas = new Map(), draftKey = 'cotizacion-borrador-' + config.dataset.user;
let restaurando = true, enviando = false, valido = false, cambioEditado = config.dataset.cambioConfigurado === '1';
const decimal = p => p.tipo === 'PRODUCTO' && p.unidad?.toUpperCase() === 'M' && Number(p.controlaserie) !== 1;
function mostrarError(texto) { $('errorCotizacion').textContent = texto; $('errorCotizacion').classList.remove('d-none'); }
function sincronizarCliente() {
    const select = $('clienteCotizacion');
    $('buscarClienteCotizacion').value = select.value ? select.selectedOptions[0].textContent.trim() : '';
    $('buscarClienteCotizacion').setCustomValidity(select.value ? '' : 'Selecciona un cliente de los resultados.');
}
crearBusqueda($('buscarClienteCotizacion'), $('resultadosClientesCotizacion'), () => [...$('clienteCotizacion').options].filter(o => o.value).map(o => ({value: o.value, label: o.textContent.trim()})), opcion => { $('clienteCotizacion').value = opcion.value; sincronizarCliente(); actualizar(); guardarBorrador(); }, true);
$('buscarClienteCotizacion').addEventListener('input', () => { $('clienteCotizacion').value = ''; $('buscarClienteCotizacion').setCustomValidity('Selecciona un cliente de los resultados.'); });
crearBusqueda($('buscarArticuloCotizacion'), $('resultadosArticulosCotizacion'), () => productos.map(p => ({value: String(p.idproducto), codigo: p.codigobarras || String(p.idproducto), label: `${p.nombre} · ${money(p.precioventa)}`})), opcion => { agregar(opcion.value); $('buscarArticuloCotizacion').value = ''; $('buscarArticuloCotizacion').focus(); });
function agregar(id, datos = {}) {
    id = String(id); const producto = catalogo.get(id);
    if (!producto) { mostrarError('Un artículo ya no está activo. Retíralo o reemplázalo antes de guardar.'); return; }
    if (filas.has(id)) { const input = filas.get(id).querySelector('.cot-cantidad'); input.value = Number(input.value) + Number(datos.cantidad || 1); }
    else {
        if (filas.size >= 200) return mostrarError('La proforma admite hasta 200 artículos.');
        const row = document.createElement('tr'); row.dataset.id = id;
        row.innerHTML = `<td><strong class="cot-nombre"></strong><input type="hidden" name="detalles[${Number(id)}][idproducto]" value="${Number(id)}"><textarea aria-label="Descripción para el cliente" name="detalles[${Number(id)}][descripcion]" class="form-control cot-descripcion" maxlength="1000" rows="2"></textarea></td><td><input aria-label="Cantidad" class="form-control cot-cantidad" type="number" name="detalles[${Number(id)}][cantidad]" min="${decimal(producto) ? '0.01' : '1'}" step="${decimal(producto) ? '0.01' : '1'}" max="99999999.99" required></td><td><input aria-label="Precio de origen" class="form-control cot-precio" type="number" name="detalles[${Number(id)}][precio_origen]" min="0.0001" max="99999999.9999" step="0.0001" required></td><td class="text-end cot-importe"></td><td><button class="btn btn-outline-danger quitar-cot-articulo" type="button" aria-label="Retirar artículo">×</button></td>`;
        row.querySelector('.cot-nombre').textContent = producto.nombre;
        row.querySelector('.cot-descripcion').value = datos.descripcion || producto.nombre;
        row.querySelector('.cot-cantidad').value = datos.cantidad || 1;
        let precio = Number(producto.precioventa);
        if ($('monedaCotizacion').value === 'USD') precio /= Number($('cambioCotizacion').value || 1);
        row.querySelector('.cot-precio').value = datos.precio_origen ?? precio.toFixed(4);
        filas.set(id, row); $('detalleCotizacion').appendChild(row);
    }
    actualizar(); guardarBorrador();
}
function detalles() { return [...filas].map(([id, row]) => ({idproducto: id, cantidad: row.querySelector('.cot-cantidad').value, precio_origen: row.querySelector('.cot-precio').value, descripcion: row.querySelector('.cot-descripcion').value})); }
function actualizar() {
    $('cotizacionVacia').hidden = filas.size > 0;
    $('cabeceraPrecioCotizacion').textContent = $('monedaCotizacion').value === 'USD' ? 'Precio USD' : 'Precio S/';
    $('tasaCotizacion').textContent = $('igvCotizacion').value ? $('igvCotizacion').value + ' %' : '';
    valido = false;
    try {
        const lineas = detalles();
        for (const linea of lineas) if (!cantidadCorrecta(Number(linea.cantidad), decimal(catalogo.get(linea.idproducto)))) throw new Error('Revisa las cantidades; solo los productos en metros admiten fracciones.');
        const calculo = calcular(lineas, $('monedaCotizacion').value, $('cambioCotizacion').value, $('igvCotizacion').value, $('incluyeIgvCotizacion').value === '1');
        $('baseCotizacion').textContent = money(calculo.base); $('importeIgvCotizacion').textContent = money(calculo.igv); $('totalCotizacion').textContent = money(calculo.total);
        [...filas.values()].forEach((row, i) => { row.querySelector('.cot-importe').textContent = money(calculo.detalles[i].importe); });
        $('errorCotizacion').classList.add('d-none'); valido = true;
    } catch (error) {
        $('baseCotizacion').textContent = 'S/ 0.00'; $('importeIgvCotizacion').textContent = 'S/ 0.00'; $('totalCotizacion').textContent = 'S/ 0.00';
        if (filas.size) mostrarError(error.message);
    }
    $('guardarCotizacion').disabled = enviando || !valido || !form.checkValidity();
    const linea = $('lineaCotizacion').selectedOptions[0];
    if (config.dataset.id === '0') $('numeroPrevisto').textContent = linea?.dataset.prefijo ? `COT-${new Date().getFullYear()}-${linea.dataset.prefijo}-… · Número asignado al guardar` : 'Selecciona una línea de numeración';
}
function datosActuales() {
    const datos = {};
    for (const name of ['idpersona','idlocal','idlinea','fechavencimiento','moneda_origen','tipocambio','porcentajeigv','precios_incluyen_igv','condiciones','observacion']) {
        const campo = form.elements.namedItem(name); datos[name] = campo?.value || '';
    }
    datos.detalles = detalles(); return datos;
}
function guardarBorrador() { if (!restaurando && config.dataset.id === '0') { try { sessionStorage.setItem(draftKey, JSON.stringify({fecha: Date.now(), datos: datosActuales()})); } catch (error) { console.warn(error); } } }
['input','change'].forEach(tipo => form.addEventListener(tipo, () => { actualizar(); guardarBorrador(); }));
$('cambioCotizacion').addEventListener('input', () => { cambioEditado = true; });
let monedaPrevia = 'PEN';
$('monedaCotizacion').addEventListener('change', () => {
    const moneda = $('monedaCotizacion').value, cambio = Number($('cambioCotizacion').value);
    if (moneda === 'USD' && !cambioEditado) {
        $('monedaCotizacion').value = monedaPrevia;
        $('cambioCotizacion').value = '';
        mostrarError('Ingresa el tipo de cambio y vuelve a seleccionar dólares.');
        $('cambioCotizacion').focus();
        actualizar(); guardarBorrador(); return;
    }
    else if (cambio > 0 && moneda !== monedaPrevia) for (const row of filas.values()) {
        const input = row.querySelector('.cot-precio'); input.value = (moneda === 'USD' ? Number(input.value) / cambio : Number(input.value) * cambio).toFixed(4);
    }
    monedaPrevia = moneda; actualizar(); guardarBorrador();
});
$('detalleCotizacion').addEventListener('click', event => { const boton = event.target.closest('.quitar-cot-articulo'); if (!boton) return; const row = boton.closest('tr'); filas.delete(row.dataset.id); row.remove(); actualizar(); guardarBorrador(); });
let inicial = JSON.parse(config.dataset.inicial);
if (config.dataset.id === '0' && !inicial.idpersona && !Object.values(inicial.detalles || {}).length) {
    try { const draft = JSON.parse(sessionStorage.getItem(draftKey) || 'null'); if (draft && Date.now() - draft.fecha < 86400000) inicial = draft.datos; } catch (error) { console.warn(error); }
}
const campos = {idpersona:'clienteCotizacion',idlocal:'localCotizacion',idlinea:'lineaCotizacion',fechavencimiento:'vigenciaCotizacion',moneda_origen:'monedaCotizacion',tipocambio:'cambioCotizacion',porcentajeigv:'igvCotizacion',precios_incluyen_igv:'incluyeIgvCotizacion',condiciones:'condicionesCotizacion',observacion:'observacionCotizacion'};
for (const [name, id] of Object.entries(campos)) if (inicial[name] != null) $(id).value = inicial[name];
monedaPrevia = $('monedaCotizacion').value; sincronizarCliente();
for (const linea of Object.values(inicial.detalles || {})) agregar(linea.idproducto, linea);
restaurando = false; actualizar();
form.addEventListener('submit', event => { if (enviando || !valido) { event.preventDefault(); return; } enviando = true; $('guardarCotizacion').disabled = true; $('guardarCotizacion').textContent = 'Guardando…'; });
window.addEventListener('pageshow', () => { enviando = false; $('guardarCotizacion').innerHTML = 'Guardar proforma <kbd>F9</kbd>'; actualizar(); });
document.addEventListener('keydown', event => { if (event.repeat || event.ctrlKey || event.altKey || event.metaKey || !['F2','F4','F9'].includes(event.key)) return; event.preventDefault(); if (event.key === 'F2') $('buscarArticuloCotizacion').focus(); if (event.key === 'F4') $('buscarClienteCotizacion').focus(); if (event.key === 'F9' && !$('guardarCotizacion').disabled) form.requestSubmit($('guardarCotizacion')); });
