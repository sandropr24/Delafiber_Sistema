<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<?php $tipos = ['PRODUCTO' => 'Producto', 'SERVICIO' => 'Servicio', 'COMBO' => 'Combo']; ?>
<header class="page-title">
    <p class="eyebrow">TU SELECCIÓN</p>
    <h1>Carrito</h1>
    <p>Revisa tus soluciones antes de continuar.</p>
</header>
<?php if ($resumen['items']): ?>
    <form method="post" action="<?= base_url('carrito/actualizar') ?>">
        <?= csrf_field() ?>
        <div class="cart-layout">
            <section class="cart-list" aria-labelledby="cart-items-title">
                <div class="section-heading">
                    <h2 id="cart-items-title">Productos seleccionados</h2><span><?= count($resumen['items']) ?> <?= count($resumen['items']) === 1 ? 'ítem' : 'ítems' ?></span>
                </div>
                <?php foreach ($resumen['items'] as $item): ?><?php $admiteDecimal = $item['tipo'] === 'PRODUCTO' && strtoupper((string) ($item['unidad'] ?? '')) === 'M'; ?>
                <article class="cart-item">
                    <a class="cart-thumb" href="<?= base_url('producto/' . $item['idproducto']) ?>" aria-label="Ver <?= esc($item['nombre'], 'attr') ?>"><?php if ($item['imagen']): ?><img src="<?= base_url('uploads/productos/' . rawurlencode(basename($item['imagen']))) ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true"><?= esc(substr($item['tipo'], 0, 1)) ?></span><?php endif; ?></a>
                    <div class="cart-product"><small><?= esc($tipos[$item['tipo']] ?? ucfirst(strtolower($item['tipo']))) ?></small>
                        <h3><a href="<?= base_url('producto/' . $item['idproducto']) ?>"><?= esc($item['nombre']) ?></a></h3><span>S/ <?= number_format((float) $item['precioventa'], 2) ?> por <?= esc($item['unidad'] ?: 'unidad') ?></span>
                    </div>
                    <label for="cantidad-<?= (int) $item['idproducto'] ?>">Cantidad<input id="cantidad-<?= (int) $item['idproducto'] ?>" type="number" name="cantidades[<?= (int) $item['idproducto'] ?>]" value="<?= esc($item['cantidad'], 'attr') ?>" min="0" max="9999" step="<?= $admiteDecimal ? '0.01' : '1' ?>" inputmode="<?= $admiteDecimal ? 'decimal' : 'numeric' ?>"></label>
                    <div class="cart-amount"><span>Subtotal</span><strong>S/ <?= number_format((float) $item['importe'], 2) ?></strong></div>
                    <button class="remove" type="submit" formaction="<?= base_url('carrito/eliminar/' . $item['idproducto']) ?>" formmethod="post" aria-label="Eliminar <?= esc($item['nombre'], 'attr') ?> del carrito"><span aria-hidden="true">×</span> Eliminar</button>
                </article>
            <?php endforeach; ?>
            <div class="cart-actions"><button class="button secondary" type="submit">Actualizar cantidades</button><a href="<?= base_url('/') ?>">Seguir comprando</a></div>
            </section>
            <aside class="summary" aria-labelledby="summary-title">
                <p class="summary-kicker">RESUMEN DEL PEDIDO</p>
                <h2 id="summary-title">Tu selección</h2>
                <div><span>Artículos</span><span><?= (int) $resumen['cantidad'] ?></span></div>
                <div class="total"><span>Total referencial</span><strong>S/ <?= number_format((float) $resumen['total'], 2) ?></strong></div>
                <a class="button" href="<?= base_url('checkout') ?>">Continuar con el pedido</a>
                <p>Pago contra entrega. Confirmaremos precio, stock y condiciones antes de atenderlo.</p>
            </aside>
        </div>
    </form>
<?php else: ?>
    <div class="empty"><span class="empty-mark" aria-hidden="true">D</span>
        <h2>Tu carrito está vacío</h2>
        <p>Explora equipos, servicios y combos disponibles para encontrar la solución que necesitas.</p><a class="button" href="<?= base_url('/') ?>">Ver catálogo</a>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
