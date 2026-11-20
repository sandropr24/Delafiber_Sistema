<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<?php
$tipos = ['PRODUCTO' => 'Producto', 'SERVICIO' => 'Servicio', 'COMBO' => 'Combo'];
$admiteDecimal = $producto['tipo'] === 'PRODUCTO' && strtoupper((string) ($producto['unidad'] ?? '')) === 'M';
$esCombo = $producto['tipo'] === 'COMBO';
$esServicio = $producto['tipo'] === 'SERVICIO';
$imagenUrl = $producto['imagen'] ? base_url('uploads/productos/' . rawurlencode(basename($producto['imagen']))) : null;
?>
<nav class="breadcrumb" aria-label="Migas de pan"><a href="<?= base_url('/') ?>#catalogo">Catálogo</a><span aria-hidden="true">/</span><a href="<?= base_url('/') . '?categoria=' . (int) $producto['idcategoria'] . '#catalogo' ?>"><?= esc($producto['categoria']) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= esc($producto['nombre']) ?></span></nav>
<article class="detail">
    <div class="detail-visual">
        <div class="detail-image">
            <span class="detail-type"><?= esc($tipos[$producto['tipo']]) ?></span>
            <?php if ($imagenUrl): ?><a class="detail-image-link" href="<?= esc($imagenUrl, 'attr') ?>" target="_blank" rel="noopener" data-image-zoom aria-label="<?= esc('Ampliar imagen de ' . $producto['nombre'], 'attr') ?>"><img src="<?= esc($imagenUrl, 'attr') ?>" alt="<?= esc($producto['nombre'], 'attr') ?>" decoding="async"><span>Ampliar imagen <span aria-hidden="true">↗</span></span></a><?php else: ?><div class="detail-no-image"><span class="image-placeholder" aria-hidden="true"><?= esc(substr($producto['tipo'], 0, 1)) ?></span><p>Imagen no disponible</p></div><?php endif; ?>
        </div>
        <p class="detail-image-note"><?= $imagenUrl ? 'Imagen referencial del artículo publicado.' : 'Consulta los detalles con nuestro equipo antes de confirmar tu pedido.' ?></p>
        <div class="detail-process"><strong>¿Cómo pedir?</strong><ol><li>Agrega lo que necesitas al carrito.</li><li>Envía tus datos y elige la entrega.</li><li>Confirmamos precio y disponibilidad contigo.</li></ol><span>No necesitas pagar en línea.</span></div>
    </div>
    <div class="detail-content">
        <p class="eyebrow"><?= esc($tipos[$producto['tipo']] ?? ucfirst(strtolower($producto['tipo']))) ?> · <?= esc($producto['categoria']) ?></p>
        <h1><?= esc($producto['nombre']) ?></h1>
        <span class="availability-note"><?= $esServicio ? 'Atención sujeta a coordinación' : 'Disponibilidad por confirmar' ?></span>
        <p class="lead"><?= nl2br(esc($producto['descripcion'] ?: 'Consulta disponibilidad y condiciones con nuestro equipo.')) ?></p>
        <dl class="spec-list">
            <?php if (!empty($producto['marca'])): ?><div><dt>Marca</dt><dd><?= esc($producto['marca']) ?></dd></div><?php endif; ?>
            <?php if (!empty($producto['modelo'])): ?><div><dt>Modelo</dt><dd><?= esc($producto['modelo']) ?></dd></div><?php endif; ?>
            <?php if (!empty($producto['unidad'])): ?><div><dt>Unidad de venta</dt><dd><?= esc($producto['unidad']) ?></dd></div><?php endif; ?>
            <?php if (!$esCombo && !$esServicio && (int) $producto['garantiameses'] > 0): ?><div><dt>Garantía</dt><dd><?= (int) $producto['garantiameses'] ?> <?= (int) $producto['garantiameses'] === 1 ? 'mes' : 'meses' ?></dd></div><?php endif; ?>
            <?php if ($esCombo): ?><div><dt>Garantía de equipos</dt><dd>Según cada componente</dd></div><?php endif; ?>
        </dl>
        <div class="detail-buy">
            <div class="detail-price"><span>Precio referencial</span><strong>S/ <?= number_format((float) $producto['precioventa'], 2) ?></strong><small>por <?= esc($producto['unidad'] ?: 'unidad') ?></small></div>
            <form method="post" action="<?= base_url('carrito/agregar') ?>" data-cart-add>
                <?= csrf_field() ?>
                <input type="hidden" name="idproducto" value="<?= (int) $producto['idproducto'] ?>">
                <label for="cantidad">Cantidad <input id="cantidad" type="number" name="cantidad" min="<?= $admiteDecimal ? '0.01' : '1' ?>" max="9999" step="<?= $admiteDecimal ? '0.01' : '1' ?>" value="1" inputmode="<?= $admiteDecimal ? 'decimal' : 'numeric' ?>" required></label>
                <button type="submit"><?= $esServicio ? 'Agregar servicio' : 'Agregar al carrito' ?></button>
            </form>
            <p class="fine">El precio y el stock se confirman al procesar el pedido. No realizamos cobros en línea.</p>
        </div>
        <div class="detail-assurances" aria-label="Condiciones de compra"><span><strong>Sin pago en línea</strong><small>Coordinamos contigo antes de cobrar.</small></span><span><strong>Atención especializada</strong><small>Validamos que la solución sea adecuada.</small></span><span><strong>Seguimiento incluido</strong><small>Consulta el estado de tu pedido.</small></span></div>
        <?php if ($esCombo): ?>
            <section class="combo-contents" aria-labelledby="combo-title">
                <div class="section-row"><div><p class="eyebrow">UNA SOLUCIÓN COMPLETA</p><h2 id="combo-title">¿Qué incluye este combo?</h2></div></div>
                <?php if ($componentes): ?>
                    <p class="detail-section-note">Componentes publicados por cada unidad del combo. Confirmaremos la composición y disponibilidad al atender tu pedido.</p>
                    <ul class="component-list">
                        <?php foreach ($componentes as $componente): ?>
                            <li><div><span class="component-type"><?= $componente['tipo'] === 'SERVICIO' ? 'Servicio' : 'Equipo / material' ?></span><a href="<?= base_url('producto/' . (int) $componente['idproducto']) ?>"><?= esc($componente['nombre']) ?></a><?php if (!empty($componente['modelo'])): ?><small>Modelo: <?= esc($componente['modelo']) ?></small><?php endif; ?><?php if ($componente['tipo'] === 'PRODUCTO' && (int) $componente['garantiameses'] > 0): ?><small>Garantía: <?= (int) $componente['garantiameses'] ?> <?= (int) $componente['garantiameses'] === 1 ? 'mes' : 'meses' ?></small><?php endif; ?></div><strong><?= esc(rtrim(rtrim(number_format((float) $componente['cantidad'], 2, '.', ''), '0'), '.')) ?><small><?= esc($componente['unidad'] ?: 'unidad') ?></small></strong></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="detail-section-note">Consulta con nuestro equipo el detalle de equipos, materiales y servicios incluidos.</p><?php endif; ?>
            </section>
        <?php endif; ?>
        <details class="purchase-conditions" open>
            <summary>Entrega y condiciones del pedido</summary>
            <div><p><strong>Recojo en tienda:</strong> coordinamos contigo el local y horario.</p><p><strong>Entrega a domicilio sin costo:</strong> realizada por nuestros técnicos, previa confirmación de cobertura y horario.</p><p><?= $esServicio ? 'La fecha, el alcance y los materiales del servicio se coordinan antes de confirmar la atención.' : 'Los precios son referenciales. El pedido no reserva stock ni confirma la venta automáticamente.' ?></p></div>
        </details>
    </div>
</article>
<?php if ($imagenUrl): ?>
<dialog class="image-dialog" id="imagen-producto" aria-labelledby="imagen-producto-titulo">
    <div class="image-dialog-header"><h2 id="imagen-producto-titulo"><?= esc($producto['nombre']) ?></h2><form method="dialog"><button type="submit" aria-label="Cerrar imagen" autofocus>×</button></form></div>
    <img src="<?= esc($imagenUrl, 'attr') ?>" alt="<?= esc($producto['nombre'], 'attr') ?>">
</dialog>
<?php endif; ?>
<?= $this->endSection() ?>
