<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Delafiber</title>
  <link rel="icon" href="<?= base_url('img/Delafiber.png') ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= base_url("css/login.css") ?>" rel="stylesheet">
</head>

<body class="login-body-custom">
  <div class="login-wrapper-box">
    <div class="card login-card-clean p-3">
      <div class="card-body p-4">

        <div class="text-center mb-4">
          <div class="mb-2">
            <img src="<?= base_url('img/Delafiber.png') ?>" alt="Logo Delafiber" class="login-logo-fixed">
          </div>
          <p class="text-muted small">Ingresa tus credenciales para continuar</p>
        </div>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger py-2 border-0 shadow-sm small mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
          </div>
        <?php endif; ?>


        <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Usuario</label>
            <div class="input-group">
              <span class="input-group-text border-end-0"><i class="fas fa-user"></i></span>
              <input type="text" name="nombreusuario" class="form-control border-start-0 ps-0" maxlength="50"
                value="<?= esc(old('nombreusuario')) ?>" placeholder="Ingrese su usuario" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-secondary">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text border-end-0"><i class="fas fa-lock"></i></span>
              <input type="password" name="claveacceso" id="claveacceso" class="form-control border-start-0 border-end-0 ps-0" maxlength="72" placeholder="••••••••" required>
              <button class="btn btn-outline-secondary input-group-text bg-light border-start-0" type="button" id="togglePassword">
                <i class="fas fa-eye" id="iconEye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-login-marca w-100 shadow-sm">
            <i class="fas fa-sign-in-alt me-2"></i> Ingresar
          </button>
        </form>

        <div class="text-center mt-4">
          <span class="text-muted small">&copy; 2026 Delafiber. Todos los derechos reservados.</span>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('claveacceso');
      const iconEye = document.getElementById('iconEye');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        iconEye.classList.remove('fa-eye');
        iconEye.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        iconEye.classList.remove('fa-eye-slash');
        iconEye.classList.add('fa-eye');
      }
    });
  </script>
</body>

</html>