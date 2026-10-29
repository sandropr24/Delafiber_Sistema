<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="container-fluid px-4 py-4" id="viewEntregas" data-url-buscar="<?= base_url('entregas/buscar_tabla') ?>" data-url-estado="<?= base_url('entregas/cambiar_estado') ?>">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted small mb-0">Control de despacho a domicilio, recojo en tienda y seguimiento por técnico asignado.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-semibold">Pendientes de Despacho</div>
                <div class="h4 fw-bold text-warning mb-0 mt-1">
                    <?php 
                        $pend = 0; 
                        if(!empty($entregas)) foreach($entregas as $e) if($e['estado'] == 'PENDIENTE') $pend++;
                        echo $pend;
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                <div class="text-muted small text-uppercase fw-semibold">En Camino / En Ruta</div>
                <div class="h4 fw-bold text-info mb-0 mt-1">
                    <?php 
                        $ruta = 0; 
                        if(!empty($entregas)) foreach($entregas as $e) if($e['estado'] == 'EN_CAMINO') $ruta++;
                        echo $ruta;
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-semibold">Entregados con Éxito</div>
                <div class="h4 fw-bold text-success mb-0 mt-1">
                    <?php 
                        $ent = 0; 
                        if(!empty($entregas)) foreach($entregas as $e) if($e['estado'] == 'ENTREGADO') $ent++;
                        echo $ent;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 shadow-sm p-3 mb-4" style="border-color: #e0e0e0 !important;">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
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
                    <?php foreach($tecnicos as $t): ?>
                        <option value="<?= $t['idusuario'] ?>"><?= esc($t['nombreusuario']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm" style="border-color: #e0e0e0 !important;">
        <div class="card-header py-3 px-4 bg-light border-bottom">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-truck text-primary me-2"></i> Listado de Entregas</h6>
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
                        <?php if(!empty($entregas)): ?>
                            <?php foreach($entregas as $item): ?>
                                <tr class="border-bottom">
                                    <td class="px-4 fw-bold text-dark"><?= esc($item['correlativo'] ?? 'VTA-'.$item['idventa']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= esc($item['cliente_nombre'] ?? 'Cliente General') ?></div>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= esc($item['telefono'] ?? $item['cliente_telefono'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary mb-1"><?= esc($item['tipoentrega']) ?></span>
                                        <div class="small text-dark"><?= esc($item['direccion'] ?? 'Recojo en Local') ?></div>
                                        <?php if(!empty($item['referencia'])): ?>
                                            <small class="text-muted italic">Ref: <?= esc($item['referencia']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary"><i class="bi bi-person-badge me-1"></i><?= esc($item['tecnico_nombre'] ?? 'Sin Asignar') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $badgeClass = 'warning';
                                            if($item['estado'] == 'EN_CAMINO') $badgeClass = 'info';
                                            if($item['estado'] == 'ENTREGADO') $badgeClass = 'success';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> border border-<?= $badgeClass ?> px-3 py-2 rounded-pill">
                                            <?= esc($item['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if($item['cobrocontraentrega'] == 1): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">S/ <?= number_format($item['total'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">PAGADO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light border text-primary" title="Gestionar Entrega" onclick="abrirModalEstado(<?= $item['identrega'] ?>, '<?= $item['estado'] ?>', '<?= $item['idtecnico'] ?>')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No hay registros de entregas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>