                <div class="card border shadow-sm rounded-3 mb-3 venta-pago">
                    <div class="card-header ventas-section-header">
                        <span class="ventas-paso" aria-hidden="true">04</span>
                        <div>
                            <h2>Métodos de pago</h2>
                            <p>Distribuye el total y verifica el efectivo recibido.</p>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label for="formaPago" class="form-label">Condición de pago</label><select id="formaPago" name="formapago" class="form-select" required><?php foreach (['CONTADO' => 'Contado (pago completo)', 'ADELANTO_SALDO' => 'Adelanto y saldo / contraentrega', 'CUOTAS' => 'Pago inicial y saldo en cuotas'] as $forma => $etiqueta): ?><option value="<?= $forma ?>" <?= old('formapago', 'CONTADO') === $forma ? 'selected' : '' ?>><?= $etiqueta ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="cobrocontraentrega" value="1" id="cobroContraentrega" <?= old('cobrocontraentrega') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="cobroContraentrega">El técnico cobrará el saldo al entregar</label></div>
                            </div>
                        </div>
                        <div id="planCuotas" class="venta-plan-cuotas mb-3" hidden>
                            <div class="d-flex align-items-start gap-2 mb-3"><span class="ventas-resumen-icono"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                                <div><strong class="d-block">Configura el cronograma</strong><small class="text-muted">El saldo restante se dividirá automáticamente sin intereses.</small></div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4"><label for="numeroCuotas" class="form-label">Número de cuotas</label><input id="numeroCuotas" name="nrocuotas" type="number" min="1" max="120" step="1" value="<?= esc(old('nrocuotas', '2'), 'attr') ?>" class="form-control" disabled></div>
                                <div class="col-md-4"><label for="frecuenciaCuotas" class="form-label">Frecuencia</label><select id="frecuenciaCuotas" name="frecuenciacuotas" class="form-select" disabled><?php foreach (['SEMANAL' => 'Semanal', 'QUINCENAL' => 'Quincenal', 'MENSUAL' => 'Mensual'] as $frecuencia => $etiqueta): ?><option value="<?= $frecuencia ?>" <?= old('frecuenciacuotas', 'MENSUAL') === $frecuencia ? 'selected' : '' ?>><?= $etiqueta ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-4"><label for="primeraFechaCuota" class="form-label">Primera fecha de pago</label><input id="primeraFechaCuota" name="fechaprimervencimiento" type="date" min="<?= date('Y-m-d') ?>" value="<?= esc(old('fechaprimervencimiento', date('Y-m-d', strtotime('+1 month'))), 'attr') ?>" class="form-control" disabled></div>
                            </div>
                            <div id="previewCuotas" class="alert alert-light border small mt-3 mb-0" aria-live="polite">Agrega artículos para calcular el cronograma.</div>
                        </div>
                        <div class="mb-3">
                            <label for="selectCaja" class="form-label">Apertura de caja para este cobro</label>
                            <select id="selectCaja" name="idapertura" class="form-select" required>
                                <option value="">Selecciona tu caja abierta</option>
                                <?php foreach ($aperturasCaja ?? [] as $aperturaCaja): ?>
                                    <option value="<?= (int) $aperturaCaja['idapertura'] ?>" data-local="<?= (int) $aperturaCaja['idlocal'] ?>"><?= esc($aperturaCaja['caja']) ?> · Apertura #<?= (int) $aperturaCaja['idapertura'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p id="avisoCajaVenta" class="small text-warning mt-2" hidden>No tienes una caja abierta en el local seleccionado.</p>
                            <a href="<?= base_url('admin/caja') ?>" target="_blank" rel="noopener" class="small">Abrir o consultar caja</a>
                            <span class="small text-muted"> · Si abres caja en otra pestaña, recarga esta venta para actualizar las aperturas.</span>
                        </div>
                        <?php if (!empty($puedeAplicarDescuento)): ?>
                            <details class="venta-descuento">
                                <summary>Descuento de la venta</summary>
                                <div class="venta-descuento-campos">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <label for="tipoDescuento" class="form-label">Tipo</label>
                                            <select name="tipo_descuento" id="tipoDescuento" class="form-select">
                                                <option value="NINGUNO">Sin descuento</option>
                                                <option value="PORCENTAJE">Porcentaje (%)</option>
                                                <option value="MONTO">Monto fijo (S/)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7" id="campoValorDescuento" hidden>
                                            <label for="valorDescuento" class="form-label">Valor del descuento</label>
                                            <input type="number" name="valor_descuento" id="valorDescuento" class="form-control" min="0" step="0.01" value="0.00" disabled>
                                        </div>
                                        <div class="col-12" id="campoMotivoDescuento" hidden>
                                            <label for="motivoDescuento" class="form-label">Motivo</label>
                                            <input type="text" name="motivo_descuento" id="motivoDescuento" class="form-control" maxlength="300" minlength="5" placeholder="Ej. Compra por volumen" list="motivosVenta" disabled>
                                            <datalist id="motivosVenta">
                                                <option value="Compra por volumen">
                                                <option value="Promoción">
                                                <option value="Negociación comercial">
                                                <option value="Cliente frecuente">
                                                <option value="Cortesía comercial">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="text-danger small mt-2 d-none" id="errorDescuento" role="alert"></div>
                                </div>
                            </details>
                        <?php endif; ?>
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-md-5">
                                <label for="selectTipoPago" class="form-label">Método de pago</label>
                                <select class="form-select form-select-sm" id="selectTipoPago">
                                    <option value="" selected disabled>Seleccionar método...</option>
                                    <?php foreach ($tipospago ?? [] as $tp): ?>
                                        <option value="<?= $tp['idtipopago'] ?>" data-nombre="<?= esc($tp['tipopago']) ?>">
                                            <?= esc($tp['tipopago']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-7 col-md-4">
                                <label for="inputMontoPago" class="form-label">Monto a aplicar (S/)</label>
                                <input type="number" class="form-control form-control-sm text-end" id="inputMontoPago" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-5 col-md-3">
                                <button type="button" class="btn ventas-btn-morado w-100" id="btnAgregarPago">
                                    <i class="bi bi-plus-lg"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6" id="campoReferenciaPago" hidden><label for="inputReferenciaPago" class="form-label">Referencia de operación</label><input id="inputReferenciaPago" class="form-control" maxlength="8" placeholder="Para pagos electrónicos"></div>
                            <div class="col-md-6" id="campoRecibidoPago" hidden><label for="inputRecibidoPago" class="form-label">Efectivo recibido (S/)</label><input id="inputRecibidoPago" type="number" step="0.01" min="0" class="form-control" placeholder="Igual al monto si se deja vacío"></div>
                            <div class="col-12">
                                <div class="venta-pagos-rapidos" aria-label="Pagos rápidos">
                                    <button id="btnCompletarPago" type="button" class="btn btn-sm btn-light border">Monto exacto</button>
                                    <?php foreach ([10, 20, 50, 100] as $billete): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-efectivo="<?= $billete ?>" title="Efectivo recibido: S/ <?= $billete ?>">S/ <?= $billete ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <p id="previewVuelto" hidden class="small text-success fw-semibold mt-2 mb-0" aria-live="polite">Seleccione efectivo para calcular el vuelto.</p>
                            </div>
                        </div>
                        <p class="small text-muted">Para pagos no efectivos, completa en su fila la cuenta destino y el voucher (JPG, PNG o WEBP, hasta 4 MB). Si recargas o hay un error, debes volver a seleccionar las fotos.</p>
                        <div class="table-responsive ventas-tabla-wrap mb-2">
                            <table class="table table-hover align-middle mb-0" id="tablaPagos">
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom">
                                    <tr>
                                        <th class="ps-3 py-2">MÉTODO</th>
                                        <th class="text-end py-2">MONTO</th>
                                        <th class="pe-3 text-end py-2">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="pagosBody">
                                    <tr id="filaSinPagos">
                                        <td colspan="3" class="text-center text-muted py-3 small">Sin pagos asignados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small px-1 mt-2">
                            <span class="text-secondary">Pagado: <span id="lblTotalPagado" class="text-success fw-bold">S/ 0.00</span></span>
                            <span class="text-secondary"><span id="lblFaltanteVuelto" class="text-danger fw-bold">Faltante: S/ 0.00</span></span>
                        </div>
                    </div>
                </div>
