            <aside class="venta-cobro">
                <div class="card border shadow-sm rounded-3 resumen-venta">
                    <div class="card-header ventas-section-header">
                        <span class="ventas-resumen-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <div>
                            <h2>Resumen de venta</h2>
                            <p>Verifica los datos del cobro</p>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <dl class="ventas-resumen-datos">
                            <div>
                                <dt>Cliente</dt>
                                <dd id="resumenClienteVenta">Sin seleccionar</dd>
                            </div>
                            <div>
                                <dt>Local / sucursal</dt>
                                <dd id="resumenLocalVenta">Sin seleccionar</dd>
                            </div>
                            <div>
                                <dt>Entrega</dt>
                                <dd id="resumenEntregaVenta">Recojo en tienda</dd>
                            </div>
                        </dl>
                        <div class="ventas-separador"></div>
                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                            <span class="text-secondary">Detalle</span><span id="contadorArticulos" class="ventas-etiqueta">0 artículos</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-secondary">Subtotal</span>
                            <span class="fw-semibold" id="lblSubtotal">S/ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                            <span class="text-secondary">Descuento</span>
                            <span class="fw-semibold text-danger" id="lblDescuento">- S/ 0.00</span>
                        </div>
                        <div class="ventas-total-panel">
                            <span>Total de la venta</span>
                            <strong id="lblTotal">S/ 0.00</strong>
                            <small>Importe final en soles (PEN)</small>
                            <input type="hidden" name="total" id="inputTotal" value="0.00">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded venta-vuelto"><span class="fw-semibold">Vuelto a entregar</span><strong id="lblVuelto" class="text-success fs-5" aria-live="polite">S/ 0.00</strong></div>
                        <p id="estadoCobro" class="small text-muted" aria-live="polite">Agregue artículos y complete el pago.</p><button id="btnConfirmarVenta" type="submit" disabled aria-keyshortcuts="F9" class="btn ventas-btn-principal w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span class="fw-semibold" id="textoConfirmarVenta">Registrar venta</span>
                            <kbd class="venta-atajo">F9</kbd>
                        </button>
                    </div>
                </div>
                <div class="ventas-nota-lateral"><i class="bi bi-box-seam" aria-hidden="true"></i>
                    <div><strong>Reserva de inventario</strong>
                        <p>Al registrar se reservan los artículos. La salida de stock se confirma desde Entregas.</p>
                    </div>
                </div>
            </aside>
