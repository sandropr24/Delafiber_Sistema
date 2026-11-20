<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/servicios/index.css') ?>"><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page df-servicios">
    <?= view('partials/ruta', ['uiSection' => 'Servicios', 'uiCurrent' => 'Nueva orden', 'uiSectionUrl' => 'admin/servicios']) ?>
    <div class="df-page-heading">
        <div>
            <h1 class="h3 fw-bold mb-1">Registrar orden de servicio</h1>
            <p class="text-muted small mb-0">Completa la solicitud y programa al técnico si ya tienes una fecha.</p>
        </div><a href="<?= base_url('admin/servicios') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-2"></i>Volver</a>
    </div>
    <div class="card df-sv-card mb-4">
        <div class="card-header df-sv-section-title"><i class="bi bi-receipt me-2"></i>Origen comercial</div>
        <div class="card-body p-3 p-lg-4"><label for="buscarVentaOrigen" class="form-label fw-semibold">Buscar una venta con servicio pendiente</label>
            <input id="buscarVentaOrigen" type="search" class="form-control mb-2" data-url="<?= base_url('admin/servicios/buscar-ventas') ?>" placeholder="ID, comprobante, cliente o documento" autocomplete="off">
            <div id="estadoBusquedaVenta" class="form-text mb-2" aria-live="polite">Escribe al menos dos caracteres, o el número exacto de venta.</div>
            <label for="ventaOrigen" class="visually-hidden">Venta seleccionada</label>
            <select id="ventaOrigen" class="form-select" data-url="<?= base_url('admin/servicios/crear') ?>">
                <option value="">Servicio independiente (sin venta vinculada)</option><?php foreach ($ventasDisponibles as $v): ?><option value="<?= (int) $v['idventa'] ?>" <?= (int) ($ventaSeleccionada['idventa'] ?? 0) === (int) $v['idventa'] ? 'selected' : '' ?>>Venta N.º <?= (int) $v['idventa'] ?> · <?= esc($v['serie'] . '-' . $v['numero']) ?></option><?php endforeach; ?>
            </select>
            <p class="small text-muted mb-0 mt-2"><?= $ventaSeleccionada ? 'La orden quedará asociada a esta venta. Cliente y servicios disponibles se obtienen de lo vendido.' : 'Puedes registrar órdenes independientes, pero este formulario no genera ventas ni cobros.' ?></p>
        </div>
    </div>
    <form action="<?= base_url('admin/servicios/guardar') ?>" method="post" id="formServicio" autocomplete="off">
        <div id="errorServicio" class="alert alert-danger d-none" role="alert" aria-live="assertive"></div>
        <input type="hidden" name="idventa" value="<?= (int) ($ventaSeleccionada['idventa'] ?? 0) ?>">
        <?= csrf_field() ?>
        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-7">
                <div class="card df-sv-card">
                    <div class="card-header df-sv-section-title"><i class="bi bi-clipboard2-check me-2"></i>Información del servicio</div>
                    <div class="card-body p-3 p-lg-4">
                        <div class="row g-3">
                            <?php $clienteInicial = $clientes[0] ?? null; ?>
                            <div class="col-12"><label class="form-label fw-semibold" for="buscarClienteServicio">Cliente <span class="text-danger">*</span></label><input type="text" id="buscarClienteServicio" class="form-control" list="clientesServicioOpciones" value="<?= esc($clienteInicial['cliente'] ?? '', 'attr') ?>" data-url="<?= base_url('admin/servicios/buscar-clientes') ?>" data-direccion="<?= esc($clienteInicial['direccion'] ?? '', 'attr') ?>" data-telefono="<?= esc($clienteInicial['telefono'] ?? '', 'attr') ?>" placeholder="Escribe nombre o documento" autocomplete="off" required <?= $ventaSeleccionada ? 'readonly' : '' ?>><datalist id="clientesServicioOpciones"></datalist><input type="hidden" name="idcliente" id="idcliente" value="<?= (int) ($clienteInicial['idpersona'] ?? 0) ?>"><small class="text-muted"><?= $ventaSeleccionada ? 'Cliente asignado automáticamente desde la venta.' : 'Escribe al menos dos caracteres y selecciona una coincidencia.' ?></small></div>
                            <div class="col-md-8"><label class="form-label fw-semibold" for="idservicio">Servicio del catálogo</label><select class="form-select" name="idservicio" id="idservicio">
                                    <option value="">Diagnóstico / servicio personalizado</option><?php foreach ($servicios as $servicio): ?><option value="<?= (int) $servicio['idproducto'] ?>" data-nombre="<?= esc($servicio['nombre'], 'attr') ?>" data-pendiente="<?= esc($servicio['pendiente'] ?? '', 'attr') ?>" data-unidad="<?= esc($servicio['abreviatura'] ?? 'SERV', 'attr') ?>" <?= (string) old('idservicio') === (string) $servicio['idproducto'] ? 'selected' : '' ?>><?= esc($servicio['nombre']) ?><?= isset($servicio['pendiente']) ? ' · Pendiente: ' . esc($servicio['pendiente'] . ' ' . $servicio['abreviatura']) : '' ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold" for="cantidadServicio">Cantidad <span class="text-danger">*</span></label><input id="cantidadServicio" name="cantidad_servicio" class="form-control" type="number" min="0.01" step="0.01" value="<?= esc(old('cantidad_servicio') ?? '1.00', 'attr') ?>" required><div id="cantidadServicioAyuda" class="form-text">Unidades cubiertas por esta orden.</div></div>
                            <div class="col-12"><label class="form-label fw-semibold" for="titulo">Título del trabajo <span class="text-danger">*</span></label><input type="text" name="titulo" id="titulo" class="form-control" value="<?= esc(old('titulo') ?? '', 'attr') ?>" maxlength="150" placeholder="Ej. Instalación de 4 cámaras de seguridad" required></div>
                            <div class="col-12"><label class="form-label fw-semibold" for="descripcion">Descripción / problema reportado</label><textarea name="descripcion" id="descripcion" class="form-control" rows="3" placeholder="Indica qué necesita el cliente..."><?= esc(old('descripcion') ?? '') ?></textarea></div>
                            <div class="col-md-8"><label class="form-label fw-semibold" for="direccion">Dirección del trabajo</label><input type="text" class="form-control" id="direccion" name="direccion" maxlength="255" value="<?= esc(old('direccion') ?? '', 'attr') ?>" placeholder="Lugar donde se realizará el servicio"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold" for="telefono">Teléfono de contacto</label><input type="tel" class="form-control" id="telefono" name="telefono" maxlength="20" value="<?= esc(old('telefono') ?? '', 'attr') ?>"></div>
                            <div class="col-12"><label class="form-label fw-semibold" for="observaciones">Observaciones internas</label><textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Indicaciones adicionales para el equipo técnico..."><?= esc(old('observaciones') ?? '') ?></textarea></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card df-sv-card">
                    <div class="card-header df-sv-section-title"><i class="bi bi-calendar2-week me-2"></i>Programación</div>
                    <div class="card-body p-3 p-lg-4">
                        <div class="df-sv-tip mb-3"><i class="bi bi-info-circle me-1"></i>Si aún no tienes fecha, deja estos campos vacíos. La orden quedará <strong>Pendiente</strong> y podrás programarla después.</div>
                        <div class="mb-3"><label class="form-label fw-semibold" for="idtecnico">Técnico responsable</label><select class="form-select" name="idtecnico" id="idtecnico">
                                <option value="">Sin asignar</option><?php foreach ($tecnicos as $tecnico): ?><option value="<?= (int) $tecnico['idtecnico'] ?>" <?= (string) old('idtecnico') === (string) $tecnico['idtecnico'] ? 'selected' : '' ?>><?= esc($tecnico['nombres'] . ' ' . $tecnico['apellidos']) ?><?= $tecnico['especialidad'] ? ' · ' . esc($tecnico['especialidad']) : '' ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="mb-3"><label class="form-label fw-semibold" for="fecha_inicio">Fecha y hora de inicio</label><input type="datetime-local" name="fecha_inicio" id="fecha_inicio" value="<?= esc(old('fecha_inicio') ?? '', 'attr') ?>" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold" for="fecha_fin">Fecha y hora de término</label><input type="datetime-local" name="fecha_fin" id="fecha_fin" value="<?= esc(old('fecha_fin') ?? '', 'attr') ?>" class="form-control"></div>
                        <p class="small text-muted mb-2">Si asignas técnico, completa ambos horarios. La fecha de inicio debe ser futura y la de término posterior.</p>
                        <p class="small text-muted mb-0">El sistema verificará que el técnico no tenga otro servicio en ese horario.</p>
                    </div>
                </div>
                <div class="df-sv-form-actions d-flex flex-wrap gap-2 justify-content-end mt-3"><a href="<?= base_url('admin/servicios') ?>" class="btn btn-light border">Cancelar</a><button type="submit" class="btn df-sv-btn" id="btnGuardarServicio"><i class="bi bi-check-lg me-1"></i>Guardar orden</button></div>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script src="<?= base_url('js/servicios/crear.js') ?>"></script><?= $this->endSection() ?>
