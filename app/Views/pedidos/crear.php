<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/pedidos/index.css') ?>?v=<?= filemtime(FCPATH . 'css/pedidos/index.css') ?>"><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="container-fluid px-3 px-lg-4 py-4 df-pedidos">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <p class="pedido-eyebrow">Comercial / Pedidos</p>
            <h1 class="h3 fw-bold mb-1"><?= $pedido ? 'Editar ' . esc($pedido['codigo']) : 'Nuevo pedido' ?></h1>
            <p class="small text-muted mb-0">Guarda lo acordado con el cliente. El cobro y la reserva se realizan al convertirlo a venta.</p>
        </div><a class="btn btn-light border" href="<?= base_url('admin/pedidos') ?>">Volver al listado</a>
    </div>
    <div class="pedido-aviso mb-3" id="avisoBorradorPedido" hidden><span>Borrador recuperado.</span><button class="btn btn-sm btn-light border" type="button" id="limpiarPedido">Descartar / Limpiar</button></div>
    <form id="formPedido" method="post" action="<?= base_url('admin/pedidos/guardar') ?>" autocomplete="off">
        <input type="hidden" name="origen_formulario" value="pedido">
        <?= csrf_field() ?><input type="hidden" name="idpedido" value="<?= (int) ($pedido['idpedido'] ?? 0) ?>"><input type="hidden" name="request_token" value="<?= esc($token, 'attr') ?>"><input type="hidden" name="revision" value="<?= (int) ($datos['revision'] ?? $pedido['revision'] ?? 1) ?>">
        <div id="errorPedido" class="alert alert-danger d-none" role="alert" tabindex="-1"></div>
        <div class="pedido-grid">
            <div class="pedido-principal">
                <section class="card pedido-card">
                    <div class="card-header">1. Cliente y local</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5"><label for="localPedido" class="form-label">Local / Sucursal *</label><select id="localPedido" name="idlocal" class="form-select" required>
                                    <option value="">Seleccione local</option><?php foreach ($locales as $local): ?><option value="<?= (int) $local['idlocal'] ?>" <?= (int) ($datos['idlocal'] ?? session('idlocal') ?? 0) === (int) $local['idlocal'] || (empty($datos['idlocal']) && count($locales) === 1) ? 'selected' : '' ?>><?= esc($local['nombrelocal']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-7"><label for="buscarClientePedido" class="form-label">Cliente * <kbd>F4</kbd></label>
                                <div class="pedido-autocomplete"><input id="buscarClientePedido" class="form-control" type="search" placeholder="Nombre o documento" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="resultadosClientesPedido" required>
                                    <div id="resultadosClientesPedido" class="pedido-resultados" role="listbox" hidden></div>
                                </div><select id="clientePedido" name="idpersona" hidden>
                                    <option value=""></option><?php foreach ($clientes as $cliente): ?><option value="<?= (int) $cliente['idpersona'] ?>"><?= esc($cliente['nombre'] . ' · ' . $cliente['numerodoc']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="card pedido-card">
                    <div class="card-header">2. Artículos <kbd>F2</kbd></div>
                    <div class="card-body"><label for="buscarArticuloPedido" class="form-label">Producto, servicio o combo</label>
                        <div class="pedido-autocomplete"><input id="buscarArticuloPedido" class="form-control" type="search" placeholder="Nombre o código · Enter para agregar" role="combobox" aria-autocomplete="list" aria-controls="resultadosArticulosPedido" aria-expanded="false">
                            <div id="resultadosArticulosPedido" class="pedido-resultados" role="listbox" hidden></div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Los artículos con serie se seleccionan al vender. Este pedido no garantiza existencias.</p>
                    </div>
                </section>
                <section class="card pedido-card">
                    <div class="card-header">3. Detalle del pedido</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Precio S/</th>
                                    <th class="text-end">Importe</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="detallePedido"></tbody>
                        </table>
                    </div>
                    <div class="card-body small text-muted" id="pedidoVacio">Agrega artículos para preparar el pedido.</div>
                </section>
                <section class="card pedido-card">
                    <div class="card-body"><label for="observacionesPedido" class="form-label">Observaciones</label><textarea id="observacionesPedido" name="observaciones" class="form-control" rows="3" maxlength="2000" placeholder="Acuerdos, indicaciones o fecha deseada de atención"></textarea></div>
                </section>
            </div>
            <aside>
                <section class="card pedido-card pedido-resumen">
                    <div class="card-header">4. Revisar y guardar</div>
                    <div class="card-body">
                        <?php if ($esAdmin): ?><details class="mb-3" id="apartadoDescuentoPedido">
                                <summary class="mb-2">Descuento</summary><label for="tipoDescuentoPedido" class="form-label">Tipo</label><select id="tipoDescuentoPedido" name="tipodescuento" class="form-select mb-2">
                                    <option value="NINGUNO">Sin descuento</option>
                                    <option value="PORCENTAJE">Porcentaje (%)</option>
                                    <option value="MONTO">Monto (S/)</option>
                                </select>
                                <div id="camposDescuentoPedido" hidden><label for="valorDescuentoPedido" class="form-label">Valor</label><input id="valorDescuentoPedido" name="valordescuento" type="number" min="0.01" step="0.01" class="form-control mb-2"><label for="motivoDescuentoPedido" class="form-label">Motivo</label><input id="motivoDescuentoPedido" name="motivodescuento" maxlength="300" minlength="5" class="form-control"></div>
                            </details><?php else: ?><input type="hidden" name="tipodescuento" value="NINGUNO"><input type="hidden" name="valordescuento" value="0"><?php endif; ?>
                        <div class="pedido-importe"><span>Subtotal</span><strong id="subtotalPedido">S/ 0.00</strong></div>
                        <div class="pedido-importe"><span>Descuento</span><strong id="descuentoPedido">S/ 0.00</strong></div>
                        <div class="pedido-importe pedido-total"><span>Total</span><strong id="totalPedido">S/ 0.00</strong></div>
                        <p class="small">Se guardará como pendiente. Después podrás confirmarlo y convertirlo a venta.</p><button type="submit" id="guardarPedido" class="btn pedido-primary w-100" disabled>Guardar pedido <kbd>F9</kbd></button><a href="<?= base_url('admin/pedidos') ?>" class="btn btn-outline-light w-100 mt-2">Cancelar</a>
                    </div>
                </section>
            </aside>
        </div>
    </form>
    <div id="pedidoConfig" data-id="<?= (int) ($pedido['idpedido'] ?? 0) ?>" data-user="<?= (int) session('idusuario') ?>" data-admin="<?= $esAdmin ? '1' : '0' ?>" data-productos="<?= esc(json_encode($productos), 'attr') ?>" data-inicial="<?= esc(json_encode($datos), 'attr') ?>"></div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script type="module" src="<?= base_url('js/pedidos/crear.js') ?>?v=<?= filemtime(FCPATH . 'js/pedidos/crear.js') ?>"></script><?= $this->endSection() ?>