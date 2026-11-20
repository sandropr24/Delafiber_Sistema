<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<div class="auth-layout">
    <aside class="auth-intro">
        <p class="eyebrow">CLIENTES DELAFIBER</p>
        <h1>Todo tu historial, en un solo lugar.</h1>
        <p>Consulta el estado de tus pedidos y mantén tus datos listos para tu próxima compra.</p><span aria-hidden="true">D</span>
    </aside>
    <section class="auth-card" aria-labelledby="login-title">
        <p class="eyebrow">BIENVENIDO</p>
        <h2 id="login-title">Ingresa a tu cuenta</h2>
        <p>Accede con el correo que registraste.</p>
        <form method="post" action="<?= base_url('cliente/login') ?>">
            <?= csrf_field() ?>
            <label for="email">Correo electrónico<input id="email" type="email" name="email" maxlength="100" autocomplete="email" inputmode="email" required value="<?= esc(old('email'), 'attr') ?>"></label>
            <label for="clave">Contraseña<input id="clave" type="password" name="clave" maxlength="72" autocomplete="current-password" required></label>
            <button type="submit">Ingresar a mi cuenta</button>
        </form>
        <p class="auth-switch">¿Aún no tienes cuenta? <a href="<?= base_url('cliente/registro') ?>">Regístrate</a></p>
    </section>
</div>
<?= $this->endSection() ?>