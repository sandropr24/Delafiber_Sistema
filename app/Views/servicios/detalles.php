<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?><link rel="stylesheet" href="<?= base_url('css/servicios/index.css') ?>"><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$estados = ['PENDIENTE' => 'Pendiente', 'PROGRAMADO' => 'Programado', 'EN_PROCESO' => 'En proceso', 'FINALIZADO' => 'Finalizado', 'CANCELADO' => 'Cancelado'];
$estado = $orden['estado'];
$codigo = 'OS-' . str_pad((string) $orden['idorden'], 5, '0', STR_PAD_LEFT);
$etiquetasEventos = [
    'CREACION' => 'Orden registrada', 'CAMBIO_ESTADO' => 'Cambio de estado',
    'REPROGRAMACION' => 'Programación actualizada', 'CONSUMO_MATERIAL' => 'Material registrado'
];
?>
<div class="container-fluid px-3 px-lg-4 py-4 df-servicios">
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4"><div><p class="df-sv-eyebrow mb-1">Servicios / Detalle</p><h1 class="h3 fw-bold mb-1">Orden <?= esc($codigo) ?></h1><p class="text-muted small mb-0"><?= esc($orden['titulo']) ?></p></div><div class="d-flex gap-2 align-items-center"><span class="df-sv-status df-sv-status-<?= esc($estado, 'attr') ?>"><?= esc($estados[$estado] ?? $estado) ?></span><a class="btn btn-light border" href="<?= base_url('servicios') ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a></div></div>
    <div class="row g-4 align-items-start">
        <div class="col-lg-7"><div class="card df-sv-card mb-4"><div class="card-header df-sv-section-title">Información de la orden</div><div class="card-body p-3 p-lg-4"><dl class="row mb-0 df-sv-description">
            <dt class="col-sm-4">Cliente</dt><dd class="col-sm-8"><?= esc($orden['cliente']) ?></dd>
            <dt class="col-sm-4">Servicio</dt><dd class="col-sm-8"><?= esc($orden['servicio'] ?: 'Servicio personalizado') ?></dd>
            <dt class="col-sm-4">Trabajo</dt><dd class="col-sm-8"><?= esc($orden['titulo']) ?></dd>
            <dt class="col-sm-4">Descripción</dt><dd class="col-sm-8"><?= nl2br(esc($orden['descripcion'] ?: 'Sin descripción')) ?></dd>
            <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8"><?= esc($orden['direccion'] ?: 'No indicada') ?></dd>
            <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8"><?= esc($orden['telefono'] ?: 'No indicado') ?></dd>
            <dt class="col-sm-4">Observaciones</dt><dd class="col-sm-8"><?= nl2br(esc($orden['observaciones'] ?: 'Sin observaciones')) ?></dd>
            <dt class="col-sm-4">Venta vinculada</dt><dd class="col-sm-8"><?= $orden['idventa'] ? '<a href="' . base_url('ventas/detalles/' . $orden['idventa']) . '">Venta N.º ' . (int) $orden['idventa'] . '</a>' : '<span class="text-muted">Sin vincular</span>' ?></dd>
            <?php if ($orden['resultado']): ?><dt class="col-sm-4">Trabajo realizado</dt><dd class="col-sm-8"><?= nl2br(esc($orden['resultado'])) ?></dd><?php endif; ?>
        </dl></div></div>
        <div class="card df-sv-card mb-4">
            <div class="card-header df-sv-section-title"><i class="bi bi-box-seam me-2"></i>Materiales utilizados</div>
            <div class="card-body p-3 p-lg-4">
                <p class="small text-muted mb-3">Registra solo materiales adicionales usados en este trabajo. Los productos vendidos se controlan desde Entregas.</p>
                <?php if (!empty($materiales)): ?>
                    <div class="table-responsive mb-3"><table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Material</th><th>Almacén</th><th class="text-end">Cantidad</th><th class="text-end">Movimiento</th></tr></thead>
                        <tbody><?php foreach ($materiales as $m): ?><tr>
                            <td><?= esc($m['producto']) ?><div class="small text-muted"><?= esc($m['fecha']) ?></div></td>
                            <td><?= esc($m['nombrelocal']) ?></td>
                            <td class="text-end"><?= number_format((float) $m['cantidad'], 2) ?></td>
                            <td class="text-end">#<?= (int) $m['idmovimiento'] ?></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php else: ?><div class="text-muted small mb-3">Todavía no se han utilizado materiales adicionales.</div><?php endif; ?>
                <?php if ($estado === 'EN_PROCESO'): ?>
                    <form action="<?= base_url('servicios/registrar-material/' . $orden['idorden']) ?>" method="post" id="formConsumoServicio">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token_operacion" value="<?= esc($tokenConsumo, 'attr') ?>">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label" for="materialKardex">Material y almacén <span class="text-danger">*</span></label>
                                <select id="materialKardex" name="idkardex" class="form-select" required>
                                    <option value="">Selecciona material</option>
                                    <?php foreach ($materialesDisponibles as $p): ?>
                                        <option value="<?= (int) $p['idkardex'] ?>" data-disponible="<?= esc(number_format((float) $p['disponible'], 2, '.', ''), 'attr') ?>">
                                            <?= esc($p['producto'] . ' · ' . $p['nombrelocal'] . ' · Disp.: ' . number_format((float) $p['disponible'], 2)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="stockDisponibleServicio" class="form-text" aria-live="polite">Selecciona un material para consultar su disponibilidad.</div>
                            </div>
                            <div class="col-md-4"><label for="cantidadConsumo" class="form-label">Cantidad <span class="text-danger">*</span></label>
                                <input id="cantidadConsumo" name="cantidad" class="form-control" type="number" min="0.01" step="0.01" required placeholder="0.00">
                            </div>
                            <div class="col-md-8"><label for="observacionConsumo" class="form-label">Observación</label>
                                <input id="observacionConsumo" name="observacion" class="form-control" maxlength="255" placeholder="Ej.: cable instalado en oficina">
                            </div>
                            <div class="col-12"><button class="btn df-sv-btn" type="submit">
                                <i class="bi bi-box-arrow-up me-1"></i> Registrar consumo
                            </button></div>
                        </div>
                    </form>
                <?php else: ?><p class="small text-muted mb-0">Para registrar consumos, la orden debe estar <strong>En proceso</strong>.</p><?php endif; ?>
            </div>
        </div>
        <div class="card df-sv-card"><div class="card-header df-sv-section-title">Historial y seguimiento</div><div class="card-body p-3 p-lg-4"><div class="df-sv-timeline">
            <?php foreach ($historial as $evento): ?><div class="df-sv-event"><div class="d-flex flex-wrap justify-content-between gap-2"><strong><?= esc($etiquetasEventos[$evento['tipoevento']] ?? ucfirst(strtolower(str_replace('_', ' ', $evento['tipoevento'])))) ?></strong><span class="text-muted small"><?= esc(date('d/m/Y H:i', strtotime($evento['fecha']))) ?></span></div><div class="text-muted small mb-1">Por <?= esc($evento['nombreusuario']) ?><?php if ($evento['estado_nuevo']): ?> · <?= esc($estados[$evento['estado_nuevo']] ?? $evento['estado_nuevo']) ?><?php endif; ?></div><div class="small"><?= esc($evento['observacion'] ?: 'Sin observaciones') ?></div></div><?php endforeach; ?>
        </div></div></div></div>
        <div class="col-lg-5"><div class="card df-sv-card mb-4"><div class="card-header df-sv-section-title">Programación y técnico</div><div class="card-body p-3 p-lg-4"><dl class="row mb-0 df-sv-description"><dt class="col-5">Técnico</dt><dd class="col-7"><?= $orden['idtecnico'] ? esc(trim($orden['tecnico'])) : 'Sin asignar' ?></dd><dt class="col-5">Inicio</dt><dd class="col-7"><?= $orden['fecha_inicio'] ? esc(date('d/m/Y H:i', strtotime($orden['fecha_inicio']))) : 'Sin programar' ?></dd><dt class="col-5">Fin estimado</dt><dd class="col-7"><?= $orden['fecha_fin'] ? esc(date('d/m/Y H:i', strtotime($orden['fecha_fin']))) : 'Sin programar' ?></dd><?php if ($orden['fecha_finalizacion']): ?><dt class="col-5">Finalizado</dt><dd class="col-7"><?= esc(date('d/m/Y H:i', strtotime($orden['fecha_finalizacion']))) ?></dd><?php endif; ?></dl></div></div>
        <?php if (in_array($estado, ['PENDIENTE', 'PROGRAMADO'], true)): ?>
            <div class="card df-sv-card mb-4"><div class="card-header df-sv-section-title"><i class="bi bi-calendar2-plus me-2"></i><?= $estado === 'PENDIENTE' ? 'Programar orden' : 'Reprogramar orden' ?></div><div class="card-body p-3 p-lg-4"><form method="post" action="<?= base_url('servicios/reprogramar/' . $orden['idorden']) ?>" class="df-sv-confirm-form" data-confirmacion="¿Confirmas la programación de esta orden?">
                <?= csrf_field() ?>
                <div class="mb-3"><label for="idtecnico" class="form-label">Técnico <span class="text-danger">*</span></label><select class="form-select" id="idtecnico" name="idtecnico" required><option value="">Selecciona un técnico</option><?php foreach ($tecnicos as $t): ?><option value="<?= (int) $t['idtecnico'] ?>" <?= (int) $orden['idtecnico'] === (int) $t['idtecnico'] ? 'selected' : '' ?>><?= esc($t['nombres'] . ' ' . $t['apellidos']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label for="fecha_inicio" class="form-label">Inicio <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?= $orden['fecha_inicio'] ? esc(date('Y-m-d\TH:i', strtotime($orden['fecha_inicio'])), 'attr') : '' ?>"></div>
                <div class="mb-3"><label for="fecha_fin" class="form-label">Fin <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin" required value="<?= $orden['fecha_fin'] ? esc(date('Y-m-d\TH:i', strtotime($orden['fecha_fin'])), 'attr') : '' ?>"></div>
                <div class="mb-3"><label for="notaProgramacion" class="form-label">Motivo / observación</label><textarea id="notaProgramacion" class="form-control" name="observacion" rows="2" maxlength="255" placeholder="Opcional"></textarea></div>
                <button type="submit" class="btn df-sv-btn w-100">Guardar programación</button>
            </form></div></div>
        <?php endif; ?>
        <?php if (in_array($estado, ['PENDIENTE', 'PROGRAMADO', 'EN_PROCESO'], true)): ?>
            <div class="card df-sv-card"><div class="card-header df-sv-section-title">Actualizar el trabajo</div><div class="card-body p-3 p-lg-4"><form action="<?= base_url('servicios/cambiar-estado/' . $orden['idorden']) ?>" method="post" class="df-sv-confirm-form" data-confirmacion="¿Confirmas el cambio de estado de esta orden?">
                <?= csrf_field() ?>
                <div class="mb-3"><label for="notaEstado" class="form-label"><?= $estado === 'EN_PROCESO' ? 'Trabajo realizado *' : 'Observación / motivo' ?></label><textarea class="form-control" id="notaEstado" name="observacion" rows="3" maxlength="255" <?= in_array($estado, ['PENDIENTE', 'EN_PROCESO'], true) ? 'required' : '' ?> placeholder="Describe el avance o el motivo de la actualización"></textarea><div class="form-text">Para finalizar o cancelar se requiere un texto de al menos 5 caracteres.</div></div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($estado === 'PENDIENTE' || $estado === 'PROGRAMADO'): ?><button type="submit" class="btn btn-outline-danger btn-sm" name="estado" value="CANCELADO">Cancelar orden</button><?php endif; ?>
                    <?php if ($estado === 'PROGRAMADO'): ?><button type="submit" class="btn df-sv-btn btn-sm" name="estado" value="EN_PROCESO">Iniciar trabajo</button><?php endif; ?>
                    <?php if ($estado === 'EN_PROCESO'): ?><button type="submit" class="btn df-sv-btn btn-sm" name="estado" value="FINALIZADO">Finalizar servicio</button><?php endif; ?>
                </div>
            </form></div></div>
        <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script src="<?= base_url('js/servicios/detalles.js') ?>"></script><?= $this->endSection() ?>
