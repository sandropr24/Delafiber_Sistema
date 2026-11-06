<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>
<?php
$entregas = $entregas ?? [];
$tecnicos = $tecnicos ?? [];
$pendientes = 0;
$enCamino = 0;
$entregados = 0;
foreach ($entregas as $entrega) {
    switch ($entrega['estado']) {
        case 'PENDIENTE':
            $pendientes++;
            break;
        case 'EN CAMINO':
            $enCamino++;
            break;
        case 'ENTREGADO':
            $entregados++;
            break;
    }
}
?>
<div class="container-fluid px-4 py-4" id="viewEntregas" data-url-buscar="<?= base_url('entregas/buscar_tabla') ?>" data-url-estado="<?= base_url('entregas/cambiar_estado') ?>">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <?= esc($titulo ?? 'Gestión de Entregas y Logística de Campo') ?>
            </h1>
            <p class="text-muted small mb-0">Control de despacho a domicilio, recojo en tienda y seguimiento por técnico asignado.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-semibold">Pendientes de Despacho</div>
                <div class="h4 fw-bold text-warning mb-0 mt-1">
                    <?= $pendientes ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                <div class="text-muted small text-uppercase fw-semibold">En Camino / En Ruta</div>
                <div class="h4 fw-bold text-info mb-0 mt-1">
                    <?= $enCamino ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-semibold">Entregados con Éxito</div>
                <div class="h4 fw-bold text-success mb-0 mt-1">
                    <?= $entregados ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 shadow-sm p-3 mb-4" style="border-color: #e0e0e0 !important;">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="inputBuscarEntrega" class="form-control border-start-0" placeholder="Buscar por venta, cliente, teléfono o dirección...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="selectEstadoEntrega">
                    <option value="">Todos los Estados</option>
                    <option value="PENDIENTE">PENDIENTE</option>
                    <option value="EN_CAMINO">EN CAMINO</option>
                    <option value="ENTREGADO">ENTREGADO</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="selectTecnicoEntrega">
                    <option value="">Todos los Técnicos</option>
                    <?php foreach ($tecnicos as $tecnico): ?>
                        <option value="<?= (int) $tecnico['idtecnico'] ?>">
                            <?= esc(trim($tecnico['nombres'] . ' ' . $tecnico['apellidos'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4 bg-light border-bottom">
            <h6 class="m-0 fw-bold text-dark">
                <i class="bi bi-truck text-primary me-2"></i>
                Listado de Entregas
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" width="100%">
                    <thead class="table-light text-uppercase fs-7 text-secondary">
                        <tr>
                            <th class="py-3 px-4"># Venta</th>
                            <th class="py-3">Cliente / Teléfono</th>
                            <th class="py-3">Tipo / Dirección</th>
                            <th class="py-3">Técnico Asignado</th>
                            <th class="py-3 text-center">Estado</th>
                            <th class="py-3 text-center">Cobro Recepción</th>
                            <th class="py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEntregas">
                        <?php if (!empty($entregas)): ?>
                            <?php foreach ($entregas as $item): ?>
                                <?php
                                $estado = $item['estado'] ?? 'PENDIENTE';
                                $badgeClass = match ($estado) {
                                    'EN_CAMINO' => 'info',
                                    'ENTREGADO' => 'success',
                                    default => 'warning',
                                };
                                ?>
                                <tr class="border-bottom">
                                    <td class="px-4 fw-bold text-dark">
                                        <?= esc($item['correlativo'] ?? 'VTA-' . $item['idventa']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <?= esc($item['cliente_nombre'] ?? 'Cliente General') ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= esc($item['telefono'] ?? $item['cliente_telefono'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary mb-1">
                                            <?= esc($item['tipoentrega'] ?? '-') ?>
                                        </span>
                                        <div class="small text-dark">
                                            <?= esc(!empty($item['direccion']) ? $item['direccion'] : 'Recojo en Local') ?>
                                        </div>
                                        <?php if (!empty($item['referencia'])): ?>
                                            <small class="text-muted">
                                                Ref: <?= esc($item['referencia']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary">
                                            <i class="bi bi-person-badge me-1"></i>
                                            <?= esc($item['tecnico_nombre'] ?? 'Sin Asignar') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?>
                                                   border border-<?= $badgeClass ?>
                                                   px-3 py-2 rounded-pill">
                                            <?= esc($estado) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($item['cobrocontraentrega'] ?? 0) == 1): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">
                                                S/ <?= number_format((float) ($item['total'] ?? 0), 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">PAGADO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border text-primary btn-gestionar-entrega"
                                            title="Gestionar Entrega"
                                            data-id="<?= (int) $item['identrega'] ?>"
                                            data-estado="<?= esc($estado, 'attr') ?>"
                                            data-tecnico="<?= esc($item['idtecnico'] ?? '', 'attr') ?>"
                                            data-tipo="<?= esc($item['tipoentrega'] ?? '', 'attr') ?>"
                                            data-direccion="<?= esc($item['direccion'] ?? '', 'attr') ?>"
                                            data-referencia="<?= esc($item['referencia'] ?? '', 'attr') ?>"
                                            data-telefono="<?= esc($item['telefono'] ?? '', 'attr') ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No hay registros de entregas.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEstadoEntrega" tabindex="-1" aria-labelledby="tituloModalEntrega" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="tituloModalEntrega">
                    <i class="bi bi-truck text-primary me-2"></i>
                    Gestionar Entrega
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEstadoEntrega">
                <?= csrf_field() ?>
                <div class="modal-body">

                    <div id="infoDomicilioEntrega" class="border rounded-3 bg-light p-3 mb-3 d-none">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-geo-alt-fill text-primary me-2"></i>Datos de entrega a domicilio
                        </h6>
                        <div class="mb-2">
                            <div class="small text-muted">Dirección</div>
                            <div id="detalleDireccionEntrega" class="fw-semibold text-dark text-break">-</div>
                        </div>
                        <div class="mb-2">
                            <div class="small text-muted">Referencia</div>
                            <div id="detalleReferenciaEntrega" class="text-dark text-break">-</div>
                        </div>
                        <div>
                            <div class="small text-muted">Teléfono de contacto</div>
                            <div id="detalleTelefonoEntrega" class="fw-semibold text-dark">-</div>
                        </div>
                    </div>
                    <input type="hidden" id="identrega" name="identrega">
                    <div class="mb-3">
                        <label for="nuevoEstadoEntrega" class="form-label fw-semibold">Estado de entrega</label>
                        <select class="form-select" id="nuevoEstadoEntrega" name="estado" required>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="EN_CAMINO">En camino</option>
                            <option value="ENTREGADO">Entregado</option>
                        </select>
                        <div class="form-text">Al confirmar como entregado, se descontará el stock reservado.</div>
                    </div>
                    <div class="mb-3">
                        <label for="tecnicoEntrega" class="form-label fw-semibold">Técnico asignado</label>
                        <select class="form-select" id="tecnicoEntrega" name="idtecnico">
                            <option value="">Sin asignar</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?= (int) $tecnico['idtecnico'] ?>">
                                    <?= esc(trim($tecnico['nombres'] . ' ' . $tecnico['apellidos'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Para recojo en tienda, el técnico puede quedar sin asignar.</div>
                    </div>
                    <div id="mensajeEntrega" class="alert alert-danger d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEstadoEntrega">
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarEntrega" tabindex="-1" aria-labelledby="tituloConfirmarEntrega" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark" id="tituloConfirmarEntrega">
                    <i class="bi bi-truck text-danger me-2"></i>
                    Confirmar entrega
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="text-warning fs-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-2">¿Estás seguro de confirmar esta entrega?</h6>
                        <p class="text-muted mb-2">
                            Al confirmar, se descontará del inventario
                            físico el stock reservado para esta venta.
                        </p>
                        <p class="small text-danger fw-semibold mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Esta operación no podrá repetirse.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEntrega">
                    <i class="bi bi-check-circle me-1"></i>
                    Confirmar entrega
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/entregas/index.js') ?>"></script>

<?= $this->endSection() ?>
