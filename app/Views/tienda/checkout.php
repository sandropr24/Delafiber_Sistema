<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<header class="page-title checkout-title">
    <p class="eyebrow">PAGO CONTRA ENTREGA</p>
    <h1>Completa tu pedido</h1>
    <p>No realizaremos ningún cobro en línea. Primero recibimos tu solicitud y confirmamos los detalles.</p>
</header>
<ol class="checkout-steps" aria-label="Proceso de compra">
    <li class="current"><span>1</span> Envías tu solicitud</li>
    <li><span>2</span> Validamos el pedido</li>
    <li><span>3</span> Coordinamos contigo</li>
</ol>
<form class="checkout" method="post" action="<?= base_url('checkout') ?>" data-checkout-form>
    <?= csrf_field() ?>
    <input type="hidden" name="request_token" value="<?= esc($tokenCheckout, 'attr') ?>">
    <section class="form-card" aria-labelledby="contact-title">
        <?php $nombreCuenta = $cuenta ? trim(($cuenta['razonsocial'] ?: ($cuenta['nombres'] . ' ' . $cuenta['apellidos']))) : ''; ?>
        <div class="form-section"><span class="section-number">01</span>
            <div>
                <h2 id="contact-title">Datos de contacto</h2>
                <p>Los usaremos únicamente para coordinar este pedido.</p>
            </div>
        </div>
        <div class="form-grid">
            <label for="nombre">Nombre o razón social<input id="nombre" name="nombre" maxlength="150" autocomplete="name" required value="<?= esc(old('nombre', $nombreCuenta), 'attr') ?>"></label>
            <label for="email">Correo electrónico<input id="email" type="email" name="email" maxlength="100" autocomplete="email" inputmode="email" required value="<?= esc(old('email', $cuenta['email'] ?? ''), 'attr') ?>"></label>
            <label for="telefono">Teléfono<input id="telefono" name="telefono" maxlength="9" pattern="[0-9+() -]{6,20}" autocomplete="tel" inputmode="tel" required value="<?= esc(old('telefono', $cuenta['telefono'] ?? ''), 'attr') ?>"><small class="field-hint">Incluye el código de país si corresponde.</small></label>
            <label for="tipodoc">Tipo de documento<select id="tipodoc" name="tipodoc" autocomplete="off" required><?php foreach (['DNI', 'RUC', 'CE', 'PASAPORTE'] as $tipoDoc): ?><option value="<?= $tipoDoc ?>" <?= old('tipodoc', $cuenta['tipodoc'] ?? 'DNI') === $tipoDoc ? 'selected' : '' ?>><?= $tipoDoc ?></option><?php endforeach; ?></select></label>
            <label for="numerodoc">Número de documento<input id="numerodoc" name="numerodoc" minlength="5" maxlength="8" autocomplete="off" inputmode="text" required value="<?= esc(old('numerodoc', $cuenta['numerodoc'] ?? ''), 'attr') ?>"></label>
        </div>
        <fieldset class="delivery-fieldset">
            <legend><span class="section-number">02</span><span><strong>Modalidad de entrega</strong><small>Elige cómo prefieres recibir tu pedido.</small></span></legend>
            <div class="delivery"><label><input type="radio" name="modalidad" value="RECOJO" <?= old('modalidad', 'RECOJO') === 'RECOJO' ? 'checked' : '' ?>><span><strong>Recojo coordinado</strong><small>Coordinaremos contigo el recojo en tienda.</small></span></label><label><input type="radio" name="modalidad" value="DOMICILIO" <?= old('modalidad') === 'DOMICILIO' ? 'checked' : '' ?>><span><strong>Entrega a domicilio sin costo</strong><small>Nuestros técnicos realizan la entrega. Confirmaremos cobertura y horario contigo.</small></span></label></div>
        </fieldset>
        <label for="direccion">Dirección para entrega a domicilio <span class="optional" data-address-optional>Opcional para recojo</span><input id="direccion" name="direccion" minlength="8" maxlength="200" autocomplete="street-address" aria-describedby="direccion-ayuda" value="<?= esc(old('direccion', $cuenta['direccion'] ?? ''), 'attr') ?>"><small class="field-hint" id="direccion-ayuda">Será obligatoria únicamente si eliges entrega a domicilio.</small></label>
        <label for="observaciones">Observaciones <span class="optional">Opcional</span><textarea id="observaciones" name="observaciones" maxlength="500" rows="4" placeholder="Referencias o indicaciones para coordinar tu pedido"><?= esc(old('observaciones')) ?></textarea></label>
    </section>
    <aside class="summary checkout-summary" aria-labelledby="order-title">
        <p class="summary-kicker">PASO FINAL</p>
        <h2 id="order-title">Resumen del pedido</h2>
        <div class="summary-items"><?php foreach ($resumen['items'] as $item): ?><div><span><strong><?= esc($item['nombre']) ?></strong><small>Cantidad: <?= esc($item['cantidad']) ?></small></span><strong>S/ <?= number_format((float) $item['importe'], 2) ?></strong></div><?php endforeach; ?></div>
        <div class="total"><span>Total referencial</span><strong>S/ <?= number_format((float) $resumen['total'], 2) ?></strong></div>
        <a class="edit-cart-link" href="<?= base_url('carrito') ?>"><span aria-hidden="true">←</span> Editar carrito</a>
        <button type="submit">Confirmar pedido</button>
        <p>Al confirmar, el pedido quedará pendiente. Delafiber validará stock, precio final y entrega contigo.</p>
    </aside>
</form>
<?= $this->endSection() ?>