                <div class="card border shadow-sm rounded-3 venta-catalogo">
                    <div class="card-header ventas-section-header">
                        <span class="ventas-paso" aria-hidden="true">02</span>
                        <div>
                            <h2>Artículos de la venta</h2>
                            <p>Busca productos, servicios o combos para agregarlos.</p>
                        </div>
                        <kbd class="venta-atajo ms-auto">F2</kbd>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="buscarArticulo" class="form-label small fw-bold text-secondary">Buscar artículo</label>
                            <div class="input-group venta-articulo-busqueda">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <div class="venta-autocomplete">
                                    <input type="search" id="buscarArticulo" data-url-busqueda="<?= esc(base_url('admin/ventas/buscar-articulos'), 'attr') ?>" class="form-control" placeholder="Nombre o código de barras · Enter para agregar" role="combobox" aria-autocomplete="list" aria-controls="resultadosArticulos" aria-expanded="false" autocomplete="off">
                                    <div id="resultadosArticulos" class="venta-resultados" role="listbox" aria-label="Artículos" hidden></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap mt-2" id="filtrosArticulos">
                                <button type="button" class="btn btn-sm btn-primary" data-tipo="todos">Todos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="producto">Productos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="servicio">Servicios</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tipo="combo">Combos</button>
                            </div>
                            <div id="disponibilidadArticulo" class="small text-primary mt-2" aria-live="polite">Seleccione un local para consultar existencias.</div>
                            <div class="form-text" id="resultadoBusqueda" aria-live="polite">Selecciona el tipo o busca por nombre.</div>
                        </div>
                        <details class="venta-manual">
                            <summary>Agregar desde el catálogo manual</summary>
                            <div class="p-2 bg-light rounded-3 mb-2 border">
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-4 articulo-grupo" data-tipo="producto">
                                        <label for="selectProducto" class="form-label">Producto</label>
                                        <select class="form-select" id="selectProducto">
                                            <option value="" selected disabled>Seleccionar producto...</option>
                                            <?php foreach ($productos ?? [] as $prod): ?>
                                                <option value="<?= $prod['idproducto'] ?>"
                                                    data-nombre="<?= esc($prod['nombre'] ?? $prod['descripcion']) ?>"
                                                    data-precio="<?= $prod['precioventa'] ?? 0 ?>"
                                                    data-unidad="<?= esc($prod['unidad'] ?? 'UND', 'attr') ?>" data-codigo="<?= esc($prod['codigobarras'] ?? '', 'attr') ?>"
                                                    data-controlaserie="<?= (int) ($prod['controlaserie'] ?? 0) ?>"
                                                    data-controlastock="<?= (int) ($prod['controlastock'] ?? 0) ?>">
                                                    <?= esc($prod['nombre'] ?? $prod['descripcion']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4 articulo-grupo" data-tipo="servicio">
                                        <label for="selectServicio" class="form-label">Servicio</label>
                                        <select class="form-select" id="selectServicio">
                                            <option value="" selected>Seleccionar servicio...</option>
                                            <?php foreach ($servicios ?? [] as $serv): ?>
                                                <option value="<?= (int) $serv['idproducto'] ?>"
                                                    data-nombre="<?= esc($serv['nombre'] ?? $serv['descripcion'], 'attr') ?>"
                                                    data-precio="<?= esc($serv['precioventa'] ?? 0, 'attr') ?>"
                                                    data-controlaserie="0" data-controlastock="0">
                                                    <?= esc($serv['nombre'] ?? $serv['descripcion']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4 articulo-grupo" data-tipo="combo">
                                        <label for="selectCombo" class="form-label">Combo</label>
                                        <select class="form-select" id="selectCombo">
                                            <option value="">Seleccionar combo...</option>
                                            <?php foreach ($combos ?? [] as $combo): ?>
                                                <option value="<?= (int) $combo['idproducto'] ?>"
                                                    data-nombre="<?= esc($combo['nombre'], 'attr') ?>"
                                                    data-precio="<?= esc($combo['precioventa'] ?? 0, 'attr') ?>"
                                                    data-controlaserie="0" data-controlastock="0" data-combo="1">
                                                    <?= esc($combo['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label for="inputCantidad" class="form-label">Cantidad</label>
                                        <input type="number" class="form-control text-center" id="inputCantidad" min="1" step="1" value="1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label for="inputPrecio" class="form-label">Precio unitario (S/)</label>
                                        <input type="number" class="form-control text-end" id="inputPrecio" readonly step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <button type="button" class="btn ventas-btn-morado w-100" id="btnAgregarItem" title="Agregar artículo">
                                            <i class="bi bi-plus-lg me-1"></i> Agregar al carrito
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </details>
                        <div class="ventas-tabla-heading"><span>Detalle de artículos</span><span>Precios en soles (PEN)</span></div>
                        <div class="table-responsive ventas-tabla-wrap">
                            <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                                <caption class="visually-hidden">Artículos incluidos en esta venta</caption>
                                <thead class="bg-light text-uppercase fs-7 text-secondary border-bottom">
                                    <tr>
                                        <th scope="col">Artículo</th>
                                        <th scope="col" class="text-center">Cantidad</th>
                                        <th scope="col" class="text-end">Precio unit.</th>
                                        <th scope="col" class="text-end">Importe</th>
                                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody id="carritoBody">
                                    <tr id="filaVacia">
                                        <td colspan="5" class="ventas-estado-vacio">
                                            <div class="ventas-vacio-icono"><i class="bi bi-cart3" aria-hidden="true"></i></div>
                                            <strong>Tu venta empieza aquí</strong>
                                            <span>Busca un artículo por nombre o código<br class="d-none d-sm-block"> y selecciónalo para agregarlo al detalle.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
