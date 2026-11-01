// DELAFIBER - Formulario de nueva compra
document.addEventListener('DOMContentLoaded', function() {
        const btnAgregar = document.getElementById('btnAgregarItem');
        const selectProducto = document.getElementById('selectProducto');
        const inputCantidad = document.getElementById('inputCantidad');
        const inputPrecio = document.getElementById('inputPrecio');
        const tablaDetalle = document.getElementById('tablaDetalle').querySelector('tbody');
        const filaVacia = document.getElementById('filaVacia');
        const totalGeneral = document.getElementById('totalGeneral');

        const monedaInput = document.getElementById('moneda');
        const cambioInput = document.getElementById('tipocambio');
        const simboloMoneda = () => monedaInput.value === 'USD' ? 'US$' : 'S/';
        let simbolo = simboloMoneda();
        monedaInput.addEventListener('change', () => {
            if (tablaDetalle.querySelector('input[name="productos[]"]')) {
                monedaInput.value = monedaInput.dataset.anterior || 'PEN';
                alert('Quite los productos antes de cambiar la moneda.');
                return;
            }
            monedaInput.dataset.anterior = monedaInput.value;
            cambioInput.readOnly = monedaInput.value === 'PEN';
            cambioInput.value = monedaInput.value === 'PEN' ? '1.0000' : '';
            simbolo = simboloMoneda();
            document.getElementById('simboloTotalCompra').textContent = simbolo;
        });
        monedaInput.dataset.anterior = 'PEN';
        const etiquetaUnidad = document.getElementById('etiquetaUnidad');
        function unidadSeleccionada() {
            return (selectProducto.selectedOptions[0]?.dataset.unidad || 'UND').toUpperCase();
        }
        function actualizarUnidad() {
            const unidad = unidadSeleccionada();
            const decimal = unidad === 'M'; // Metro: fracciones; demás unidades actuales: enteros.
            inputCantidad.step = decimal ? '0.01' : '1';
            inputCantidad.min = decimal ? '0.01' : '1';
            etiquetaUnidad.textContent = selectProducto.value ? '(' + unidad + ')' : '';
            if (!decimal && (!Number.isInteger(Number(inputCantidad.value)) || Number(inputCantidad.value) < 1)) inputCantidad.value = '1';
        }
        actualizarUnidad();
        inputCantidad.addEventListener('change', function() {
            if (!selectProducto.value) return;
            const valor = Number(this.value);
            const decimal = unidadSeleccionada() === 'M';
            if (!Number.isFinite(valor) || valor <= 0 || (!decimal && !Number.isInteger(valor)) || (decimal && Math.round(valor * 100) !== valor * 100)) {
                this.setCustomValidity(decimal ? 'Ingrese hasta dos decimales.' : 'Ingrese una cantidad entera.');
            } else {
                this.setCustomValidity('');
            }
            this.reportValidity();
        });
        selectProducto.addEventListener('change', function() {
            inputCantidad.setCustomValidity('');
            actualizarUnidad();
            const selectedOption = this.options[this.selectedIndex];
            inputPrecio.value = '';
        });

        document.getElementById('formCompra').addEventListener('submit', function(e) {
            if (!tablaDetalle.querySelector('input[name="productos[]"]') ||
                (monedaInput.value === 'USD' && !(parseFloat(cambioInput.value) > 0))) {
                e.preventDefault(); alert('Agregue productos y revise el tipo de cambio.');
            }
        });
        btnAgregar.addEventListener('click', function() {
            const idProd = selectProducto.value;
            const nombreProd = selectProducto.options[selectProducto.selectedIndex]?.getAttribute('data-nombre');
            const cantidad = parseFloat(inputCantidad.value);
            const precio = parseFloat(inputPrecio.value);

            if (!idProd) {
                alert('Por favor seleccione un producto.');
                return;
            }
            if (!Number.isFinite(cantidad) || cantidad <= 0 || (unidadSeleccionada() !== 'M' && !Number.isInteger(cantidad)) || (unidadSeleccionada() === 'M' && Math.abs(cantidad * 100 - Math.round(cantidad * 100)) > 1e-7)) {
                alert('Ingrese una cantidad válida.');
                return;
            }
            if (isNaN(precio) || precio < 0) {
                alert('Ingrese un precio unitario válido.');
                return;
            }

            const elementoVacio = document.getElementById('filaVacia');
            if (elementoVacio) {
                elementoVacio.remove();
            }

            const unidad = unidadSeleccionada();
            const cantidadVisible = unidad === 'M' ? cantidad.toFixed(2) : String(cantidad);
            const subtotal = (cantidad * precio).toFixed(2);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="productos[]" value="${idProd}">
                    <span class="fw-medium text-dark">${nombreProd}</span>
                </td>
                <td class="text-center">
                    <input type="hidden" name="cantidades[]" value="${cantidad}">
                    <span class="badge bg-secondary px-2 py-1 fs-6">${cantidadVisible}</span>
                </td>
                <td class="text-end">
                    <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
                    ${simbolo} ${precio.toFixed(2)}
                </td>
                <td class="text-end fw-bold text-success class-subtotal">
                    ${simbolo} ${subtotal}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btnEliminar" title="Eliminar ítem">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tablaDetalle.appendChild(tr);

            selectProducto.value = '';
            inputCantidad.value = '1';
            actualizarUnidad();
            inputPrecio.value = '';

            calcularTotal();
        });

        tablaDetalle.addEventListener('click', function(e) {
            if (e.target.closest('.btnEliminar')) {
                e.target.closest('tr').remove();

                if (tablaDetalle.children.length === 0) {
                    tablaDetalle.innerHTML = `
                    <tr id="filaVacia">
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fas fa-cart-arrow-down fa-2x mb-2 text-black-50 d-block"></i>
                            No se han agregado productos a la compra.
                        </td>
                    </tr>
                `;
                }
                calcularTotal();
            }
        });

        function calcularTotal() {
            let total = 0;
            tablaDetalle.querySelectorAll('tr').forEach(fila => {
                const cantidadInput = fila.querySelector('input[name="cantidades[]"]');
                const precioInput = fila.querySelector('input[name="precios[]"]');
                if (!cantidadInput || !precioInput) return;
                const cantidad = Number(cantidadInput.value);
                const precio = Number(precioInput.value);
                if (Number.isFinite(cantidad) && Number.isFinite(precio)) {
                    total += cantidad * precio;
                }
            });
            totalGeneral.value = total.toFixed(2);
        }
    });
