<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<?php
$estados = [
    'PENDIENTE' => ['Solicitud recibida', 'Estamos validando el stock, el precio y las condiciones de entrega.'],
    'CONFIRMADO' => ['Pedido confirmado', 'Nuestro equipo confirmó tu pedido y coordinará contigo los siguientes pasos.'],
    'CONVERTIDO' => ['Pedido procesado', 'Tu pedido fue convertido en venta y continúa con el proceso de atención.'],
    'CANCELADO' => ['Pedido cancelado', 'Este pedido fue cancelado. Si necesitas ayuda, comunícate con Delafiber.'],
];
$estadoActual = $estados[$pedido['estado_codigo']] ?? [$pedido['estado'], 'Consulta aquí la información actualizada de tu pedido.'];
?>
<section class="tracking" aria-labelledby="tracking-title">
    <header class="tracking-head">
        <div>
            <p class="eyebrow">SEGUIMIENTO DE PEDIDO</p>
            <h1 id="tracking-title"><?= esc($pedido['codigo']) ?></h1>
            <p>Solicitud recibida el <?= esc(date('d/m/Y \a \l\a\s H:i', strtotime($pedido['fecha']))) ?>.</p>
        </div><span class="status status-<?= esc(strtolower($pedido['estado_codigo']), 'attr') ?>"><?= esc($pedido['estado']) ?></span>
    </header>
    <div class="tracking-state state-<?= esc(strtolower($pedido['estado_codigo']), 'attr') ?>"><span class="state-icon" aria-hidden="true"><?= $pedido['estado_codigo'] === 'CANCELADO' ? '×' : '✓' ?></span>
        <div>
            <h2><?= esc($estadoActual[0]) ?></h2>
            <p><?= esc($estadoActual[1]) ?></p>
        </div>
    </div>
    <div class="tracking-grid">
        <section aria-labelledby="delivery-title">
            <p class="summary-kicker">DETALLES</p>
            <h2 id="delivery-title">Información de entrega</h2>
            <dl class="tracking-details">
                <div>
                    <dt>Contacto</dt>
                    <dd><?= esc($pedido['nombre_contacto']) ?></dd>
                </div>
                <div>
                    <dt>Modalidad</dt>
                    <dd><?= esc(ucfirst(strtolower($pedido['modalidad']))) ?></dd>
                </div><?php if ($pedido['direccion_entrega']): ?><div>
                        <dt>Dirección</dt>
                        <dd><?= esc($pedido['direccion_entrega']) ?></dd>
                    </div><?php endif; ?>
            </dl>
        </section>
        <aside class="tracking-total"><span>Total referencial</span><strong>S/ <?= number_format((float) $pedido['total'], 2) ?></strong><small>El importe final se confirma durante la atención.</small></aside>
    </div>
    <div class="tracking-actions"><a class="button secondary" href="<?= base_url('/') ?>"><span aria-hidden="true">←</span> Volver a la tienda</a></div>
</section>
<?= $this->endSection() ?>