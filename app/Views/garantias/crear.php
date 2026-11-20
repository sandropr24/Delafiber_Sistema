<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <?= view('partials/ruta', ['uiSection' => 'Garantías', 'uiCurrent' => 'Registrar caso', 'uiSectionUrl' => base_url('admin/garantias')]) ?>
    <div class="df-page-heading"><div><h1>Registrar garantía</h1><p>Busca el comprobante original y selecciona el equipo recibido.</p></div><a class="btn btn-light border" href="<?= base_url('admin/garantias') ?>">Volver a garantías</a></div>
    <form method="get" action="<?= base_url('admin/garantias/crear') ?>" class="card card-body mb-4">
        <label for="buscarVentaGarantia" class="form-label">Número de serie, DNI/documento o número de venta</label>
        <div class="input-group"><input type="search" id="buscarVentaGarantia" name="q" maxlength="100" value="<?= esc($buscar, 'attr') ?>" class="form-control" required><button class="btn btn-primary" type="submit">Buscar venta</button></div>
        <p class="small text-muted mb-0 mt-2">También puedes escribir el comprobante completo, por ejemplo NV01-00000001. Se muestran hasta 100 equipos; afina la búsqueda si es necesario.</p>
    </form>
    <div class="alert alert-info small">La vigencia se calcula desde la fecha de venta. Solo se reciben productos con entrega confirmada y garantía vigente. No se admiten dos casos abiertos para la misma serie o el mismo artículo sin serie del detalle de venta.</div>
    <?php if (($buscar !== '' || $venta) && !$articulos): ?><div class="card card-body text-center text-muted py-5">No se encontraron productos físicos para esa búsqueda.</div><?php endif; ?>
    <?php foreach ($articulos as $indice => $articulo):
        $historico = $articulo['garantiameses'] === null;
        $puedeRecibir = $articulo['aviso'] === '' || ($esAdmin && $articulo['aviso'] === 'Plazo histórico: requiere verificación de administrador');
        $recuperar = (int) old('iddetventa') === (int) $articulo['iddetventa']
            && (int) old('idcomponenteventa') === (int) $articulo['idcomponenteventa'] && (int) old('idserie') === (int) $articulo['idserie'];
    ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div><h2 class="h5"><?= esc($articulo['producto']) ?></h2><p class="mb-1"><?= esc($articulo['cliente'] . ' · ' . $articulo['numerodoc']) ?></p><p class="small text-muted mb-0">Venta <?= esc($articulo['serie'] . '-' . $articulo['numero']) ?> · <?= esc($articulo['fecha']) ?><?= $articulo['idcomponenteventa'] ? ' · Componente de combo' : '' ?></p></div>
                    <div class="text-md-end"><strong class="d-block"><?= esc($articulo['numeroserie'] ?: 'Artículo sin serie') ?></strong><span class="small"><?= $historico ? 'Plazo histórico no acreditado' : (int) $articulo['garantiameses'] . ' meses de garantía' ?></span><?php if ($articulo['vencimiento']): ?><small class="d-block">Vence: <?= esc($articulo['vencimiento']) ?></small><?php endif; ?></div>
                </div>
                <?php if ($articulo['aviso']): ?><div class="alert <?= $puedeRecibir ? 'alert-warning' : 'alert-secondary' ?> small mt-3 mb-0"><?= esc($articulo['aviso']) ?></div><?php endif; ?>
                <?php if ($puedeRecibir): ?>
                    <details class="mt-3" <?= $recuperar ? 'open' : '' ?>>
                        <summary class="fw-semibold">Registrar recepción de este equipo</summary>
                        <form method="post" action="<?= base_url('admin/garantias/guardar') ?>" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_token" value="<?= esc($token, 'attr') ?>">
                            <input type="hidden" name="iddetventa" value="<?= (int) $articulo['iddetventa'] ?>">
                            <input type="hidden" name="idcomponenteventa" value="<?= $articulo['idcomponenteventa'] ? (int) $articulo['idcomponenteventa'] : '' ?>">
                            <input type="hidden" name="idserie" value="<?= $articulo['idserie'] ? (int) $articulo['idserie'] : '' ?>">
                            <input type="hidden" name="buscar_origen" value="<?= esc($buscar, 'attr') ?>">
                            <input type="hidden" name="venta_origen" value="<?= (int) $venta ?>">
                            <?php if ($historico): ?>
                                <div class="alert alert-warning small">Verifica el plazo en el comprobante o documento original. No uses el catálogo actual como evidencia. La verificación quedará vinculada a tu usuario y a este caso; no modifica la venta histórica.</div>
                                <div class="mb-3"><label class="form-label" for="meses-<?= $indice ?>">Meses de garantía verificados</label><input id="meses-<?= $indice ?>" type="number" name="meses_verificados" min="1" max="120" step="1" class="form-control" value="<?= esc($recuperar ? old('meses_verificados') : '', 'attr') ?>" required></div>
                            <?php endif; ?>
                            <div class="mb-3"><label class="form-label" for="falla-<?= $indice ?>">Descripción de la falla</label><textarea id="falla-<?= $indice ?>" name="falla" class="form-control" rows="2" minlength="5" maxlength="255" required><?= esc($recuperar ? old('falla') : '') ?></textarea></div>
                            <div class="mb-3"><label class="form-label" for="observacion-<?= $indice ?>"><?= $historico ? 'Evidencia de verificación del plazo (obligatoria)' : 'Observaciones de recepción (opcional)' ?></label><textarea id="observacion-<?= $indice ?>" name="observacion" class="form-control" rows="2" maxlength="255" <?= $historico ? 'minlength="10" required' : '' ?>><?= esc($recuperar ? old('observacion') : '') ?></textarea></div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-shield-check me-2"></i>Registrar como recibido</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
