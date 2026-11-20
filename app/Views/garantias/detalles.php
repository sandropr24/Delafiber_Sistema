<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <?= view('partials/ruta', ['uiSection' => 'Garantías', 'uiCurrent' => 'GAR-' . $caso['idgarantia'], 'uiSectionUrl' => base_url('admin/garantias')]) ?>
    <div class="df-page-heading"><div><h1>Garantía GAR-<?= (int) $caso['idgarantia'] ?></h1><p>Seguimiento posventa del equipo recibido.</p></div><a href="<?= base_url('admin/garantias') ?>" class="btn btn-light border">Volver a garantías</a></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h6 mb-0">Equipo y venta de origen</h2></div>
                <div class="card-body">
                    <dl class="row gy-2 mb-0">
                        <dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><span class="badge <?= $caso['estado'] === 'ENTREGADO_CLIENTE' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= esc($etiquetas[$caso['estado']] ?? $caso['estado']) ?></span></dd>
                        <dt class="col-sm-4">Cliente</dt><dd class="col-sm-8"><?= esc($caso['cliente']) ?> · <?= esc($caso['numerodoc']) ?></dd>
                        <dt class="col-sm-4">Comprobante</dt><dd class="col-sm-8"><?= esc($caso['serie'] . '-' . $caso['numero']) ?></dd>
                        <dt class="col-sm-4">Fecha de venta</dt><dd class="col-sm-8"><?= esc($caso['fechaventa']) ?></dd>
                        <dt class="col-sm-4">Producto</dt><dd class="col-sm-8"><?= esc($caso['producto']) ?><?= $caso['idcomponenteventa'] ? ' (componente de combo)' : '' ?></dd>
                        <dt class="col-sm-4">Número de serie</dt><dd class="col-sm-8"><?= esc($caso['numeroserie'] ?: 'Sin serie') ?></dd>
                        <dt class="col-sm-4">Plazo / vencimiento</dt><dd class="col-sm-8"><?= $caso['garantiameses'] === null ? 'Sin plazo histórico acreditado' : (int) $caso['garantiameses'] . ' meses · ' . esc($caso['fechavencimiento'] ?? '') ?><?php if ($caso['plazo_verificado']): ?><small class="d-block text-muted">Plazo histórico verificado por administrador al recibir.</small><?php endif; ?></dd>
                        <dt class="col-sm-4">Recepción</dt><dd class="col-sm-8"><?= esc($caso['fechaingreso']) ?> · <?= esc($caso['responsable']) ?></dd>
                        <dt class="col-sm-4">Proveedor</dt><dd class="col-sm-8"><?= esc($caso['proveedor'] ?: 'Sin envío a proveedor') ?></dd>
                        <dt class="col-sm-4">Falla reportada</dt><dd class="col-sm-8"><?= nl2br(esc($caso['falla'])) ?></dd>
                        <dt class="col-sm-4">Observaciones iniciales</dt><dd class="col-sm-8"><?= nl2br(esc($caso['observacion'] ?: 'Sin observaciones')) ?></dd>
                    </dl>
                </div>
            </div>
            <div class="alert alert-info small">Este módulo registra la atención de garantía. Marcar «Reemplazado» no mueve stock, no cambia la serie vendida ni genera otra venta; documenta en la observación el equipo entregado y gestiona cualquier movimiento físico por el proceso de inventario autorizado.</div>
        </div>
        <div class="col-lg-5">
            <?php if ($puedeGestionar && $siguientes): ?>
            <form method="post" action="<?= base_url('admin/garantias/cambiar-estado/' . $caso['idgarantia']) ?>" class="card mb-4">
                <div class="card-header"><h2 class="h6 mb-0">Actualizar seguimiento</h2></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="estado_actual" value="<?= esc($caso['estado'], 'attr') ?>">
                    <div class="mb-3"><label for="nuevoEstadoGarantia" class="form-label">Nuevo estado</label><select id="nuevoEstadoGarantia" name="estado" class="form-select" required><option value="">Selecciona</option><?php foreach ($siguientes as $codigo): ?><option value="<?= $codigo ?>" <?= old('estado') === $codigo ? 'selected' : '' ?>><?= esc($etiquetas[$codigo]) ?></option><?php endforeach; ?></select></div>
                    <?php if (in_array('ENVIADO_PROVEEDOR', $siguientes, true)): ?>
                    <div class="mb-3"><label for="proveedorGarantia" class="form-label">Proveedor (obligatorio al enviar)</label><select id="proveedorGarantia" name="idproveedor" class="form-select"><option value="">Selecciona el proveedor</option><?php foreach ($proveedores as $proveedor): ?><option value="<?= (int) $proveedor['idproveedor'] ?>" <?= (int) old('idproveedor') === (int) $proveedor['idproveedor'] ? 'selected' : '' ?>><?= esc($proveedor['razonsocial']) ?></option><?php endforeach; ?></select></div>
                    <?php endif; ?>
                    <div class="mb-3"><label for="notaGarantia" class="form-label">Observación del cambio</label><textarea id="notaGarantia" name="observacion" class="form-control" rows="3" minlength="5" maxlength="255" required><?= esc(old('observacion')) ?></textarea><div class="form-text">Describe reparación, reemplazo o entrega. Incluye la nueva serie si hubo reemplazo.</div></div>
                    <button class="btn btn-primary" type="submit">Guardar seguimiento</button>
                </div>
            </form>
            <?php elseif (!$siguientes): ?><div class="alert alert-success">Caso cerrado: equipo entregado al cliente. El historial se conserva sin edición.</div><?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="h6 mb-0">Historial del caso</h2></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Fecha y hora</th><th>Estado</th><th>Responsable</th><th>Proveedor</th><th>Observaciones</th></tr></thead><tbody>
        <?php foreach ($historial as $evento): ?><tr><td class="text-nowrap"><?= esc($evento['fecha']) ?></td><td><?= esc($etiquetas[$evento['estado']] ?? $evento['estado']) ?></td><td><?= esc($evento['nombreusuario']) ?></td><td><?= esc($evento['proveedor'] ?: '—') ?></td><td><?= nl2br(esc($evento['observacion'] ?? '')) ?></td></tr><?php endforeach; ?>
        <?php if (!$historial): ?><tr><td colspan="5" class="text-center text-muted py-4">Este caso histórico no tiene eventos registrados.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
<?= $this->endSection() ?>
