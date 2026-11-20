<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<div class="auth-layout register-layout">
    <aside class="auth-intro">
        <p class="eyebrow">CUENTA DELAFIBER</p>
        <h1>Una experiencia de compra más simple.</h1>
        <p>Crea tu cuenta para consultar tus pedidos desde un solo lugar.</p>
    </aside>
    <section class="auth-card wide" aria-labelledby="register-title">
        <p class="eyebrow">REGISTRO</p>
        <h2 id="register-title">Crea tu cuenta</h2>
        <p>Completa tus datos. Te enviaremos un enlace para verificar tu correo antes de ingresar. Si ya solicitaste una cuenta pendiente, puedes repetir el formulario para recibir otro enlace.</p>
        <form method="post" action="<?= base_url('cliente/registro') ?>">
            <?= csrf_field() ?>
            <label for="nombre">Nombre o razón social<input id="nombre" name="nombre" maxlength="150" autocomplete="name" required value="<?= esc(old('nombre'), 'attr') ?>"></label>
            <label for="email">Correo electrónico<input id="email" type="email" name="email" maxlength="100" autocomplete="email" inputmode="email" required value="<?= esc(old('email'), 'attr') ?>"></label>
            <div class="form-grid">
                <label for="tipodoc">Tipo de documento<select id="tipodoc" name="tipodoc" required><?php foreach (['DNI', 'RUC', 'CE', 'PASAPORTE'] as $tipoDoc): ?><option value="<?= $tipoDoc ?>" <?= old('tipodoc', 'DNI') === $tipoDoc ? 'selected' : '' ?>><?= $tipoDoc ?></option><?php endforeach; ?></select></label>
                <label for="numerodoc">Número de documento<input id="numerodoc" name="numerodoc" minlength="5" maxlength="8" autocomplete="off" required value="<?= esc(old('numerodoc'), 'attr') ?>"></label>
            </div>
            <label for="clave">Contraseña <span class="label-hint">Mínimo 8 caracteres</span><input id="clave" type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" required></label>
            <button type="submit">Crear mi cuenta</button>
        </form>
        <p class="auth-switch">¿Ya tienes una cuenta? <a href="<?= base_url('cliente/login') ?>">Ingresa aquí</a></p>
    </section>
</div>
<?= $this->endSection() ?>