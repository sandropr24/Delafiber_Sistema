<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión</title>
  <link rel="icon" href="<?= base_url('img/Delafiber.png') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; }
    .login-card { max-width: 400px; width: 100%; }
    .login-logo { max-height: 90px; max-width: 100%; }
    .btn-marca { background: #0d6efd; border-color: #0d6efd; color: #fff; } /* cambia este color */
  </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
  <div class="login-card p-3">
    <div class="card shadow-sm">
      <div class="card-body p-4">

        <div class="text-center mb-4">
          <img src="<?= base_url('img/Delafiber.png') ?>" alt="Logo de la empresa" class="login-logo">
        </div>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="nombreusuario" class="form-control" maxlength="50"
                   value="<?= esc(old('nombreusuario')) ?>" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="claveacceso" class="form-control" maxlength="72" required>
          </div>
          <button class="btn btn-marca w-100">Ingresar</button>
        </form>

      </div>
    </div>
  </div>
</body>
</html>