<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<?php
$mensajesEstado = [
    'PENDIENTE' => 'Esperando validación de stock y condiciones.',
    'CONFIRMADO' => 'Pedido confirmado por nuestro equipo.',
    'CONVERTIDO' => 'Pedido procesado y convertido en venta.',
    'CANCELADO' => 'Este pedido fue cancelado.',
];
?>
<header class="account-head">
    <div><p class="eyebrow">MI CUENTA</p><h1><?= esc($cuenta['razonsocial'] ?: trim($cuenta['nombres'] . ' ' . $cuenta['apellidos'])) ?></h1><p><?= esc($cuenta['email']) ?></p></div>
    <form method="post" action="<?= base_url('cliente/logout') ?>"><?= csrf_field() ?><button class="button secondary" type="submit">Cerrar sesión</button></form>
</header>
<section class="orders" aria-labelledby="orders-title">
    <div class="section-heading"><div><p class="eyebrow">HISTORIAL</p><h2 id="orders-title">Mis pedidos</h2></div><span><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?></span></div>
    <div class="orders-list">
        <?php foreach ($pedidos as $pedido): ?>
            <article>
                <div class="order-icon" aria-hidden="true"><?= esc(substr($pedido['codigo'], -2)) ?></div>
                <div class="order-main"><span>Pedido</span><strong><?= esc($pedido['codigo']) ?></strong><small><?= esc(date('d/m/Y · H:i', strtotime($pedido['fecha']))) ?> · <?= esc(ucfirst(strtolower($pedido['modalidad']))) ?></small></div>
                <div class="order-status"><span class="status status-<?= esc(strtolower($pedido['estado_codigo']), 'attr') ?>"><?= esc($pedido['estado']) ?></span><small><?= esc($mensajesEstado[$pedido['estado_codigo']] ?? 'Consulta el estado actualizado de tu pedido.') ?></small></div>
                <div class="order-total"><span>Total referencial</span><strong>S/ <?= number_format((float) $pedido['total'], 2) ?></strong></div>
            </article>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?><div class="empty"><span class="empty-mark" aria-hidden="true">D</span><h3>Aún no tienes pedidos</h3><p>Cuando realices uno, podrás consultar su estado desde aquí.</p><a class="button" href="<?= base_url('/') ?>">Explorar catálogo</a></div><?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
