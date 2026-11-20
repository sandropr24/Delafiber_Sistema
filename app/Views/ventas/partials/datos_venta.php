                <div class="card border shadow-sm rounded-3 mb-3 venta-cliente">
                    <div class="card-header ventas-section-header">
                        <span class="ventas-paso" aria-hidden="true">01</span>
                        <div>
                            <h2>Datos de la venta</h2>
                            <p>Cliente, local y datos del comprobante.</p>
                        </div>
                        <span class="ventas-campos-nota"><span class="text-danger">*</span> Obligatorios</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="venta-datos-principales">
                            <div>
                                <label for="selectLocal" class="form-label">Local / sucursal <span class="text-danger">*</span></label>
                                <select name="idlocal" id="selectLocal" class="form-select" required>
                                    <option value="">Seleccione local...</option>
                                    <?php foreach ($locales ?? [] as $loc): ?>
                                        <option value="<?= $loc['idlocal'] ?>" <?= (int) $loc['idlocal'] === (int) $localInicial ? 'selected' : '' ?>><?= esc($loc['nombrelocal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="buscarClienteVenta" class="form-label small fw-bold text-secondary text-uppercase">Cliente <span class="text-danger">*</span> <kbd class="venta-atajo">F4</kbd></label>
                                <div class="input-group venta-cliente-busqueda">
                                    <div class="venta-autocomplete">
                                        <input type="search" id="buscarClienteVenta" class="form-control" placeholder="Nombre o documento del cliente" aria-label="Buscar y seleccionar cliente" role="combobox" aria-autocomplete="list" aria-controls="resultadosClientes" aria-expanded="false" autocomplete="off" required>
                                        <div id="resultadosClientes" class="venta-resultados" role="listbox" aria-label="Clientes" hidden></div>
                                    </div>
                                    <select name="idcliente" id="selectCliente" hidden aria-hidden="true" tabindex="-1">
                                        <option value="">Seleccione cliente...</option>
                                        <?php foreach ($clientes ?? [] as $cli): ?>
                                            <option value="<?= $cli['idpersona'] ?>" <?= (int) $cli['idpersona'] === (int) $clienteInicial ? 'selected' : '' ?>>
                                                <?= trim(esc($cli['nombres'] ?? '') . ' ' . esc($cli['apellidos'] ?? '')) ?: esc($cli['razonsocial'] ?? 'Sin Nombre') ?>
                                                · <?= esc($cli['numerodoc'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalClienteExpress" title="Registrar cliente rápido" aria-label="Registrar un cliente rápido">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="button" id="btnPublicoGeneral" data-cliente="<?= (int) $clienteInicial ?>" class="btn btn-sm btn-outline-secondary venta-publico-general" <?= empty($clienteInicial) ? 'disabled title="Configure un cliente Público General para habilitar este acceso"' : '' ?>><i class="bi bi-people me-1"></i>Público General</button>
                            </div>

                        </div>
                        <div class="ventas-separador"></div>
                        <div class="venta-datos-comprobante">
                            <div>
                                <label for="lineaVenta" class="form-label">Línea <span class="text-danger">*</span></label>
                                <select name="idlinea" id="lineaVenta" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($lineas as $x): ?>
                                        <option value="<?= esc($x['idlinea']) ?>"><?= esc($x['nombre'] ?? $x['linea'] ?? $x['idlinea']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="canalVenta" class="form-label">Canal <span class="text-danger">*</span></label>
                                <select name="idcanal" id="canalVenta" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($canales as $x): ?>
                                        <option value="<?= esc($x['idcanal']) ?>"><?= esc($x['nombre'] ?? $x['canal'] ?? $x['idcanal']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="estadoInicialVenta" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select name="idestado" id="estadoInicialVenta" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($estadosVenta as $x): ?>
                                        <option value="<?= esc($x['idestado']) ?>" <?= (int) $x['idestado'] === (int) $estadoInicial ? 'selected' : '' ?>><?= esc($x['estado']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="venta-comprobante"><span><i class="bi bi-receipt me-1" aria-hidden="true"></i> Nota de venta</span><span>Numeración automática al registrar</span><input type="hidden" name="tipodocumento" value="NOTA_VENTA"></div>
                    </div>
                </div>
