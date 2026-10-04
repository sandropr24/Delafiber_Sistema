<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand">Sistema de ventas</span>
    <span class="text-white ms-auto me-3"><?= esc(session()->get('nombre')) ?> (<?= esc(session()->get('rol')) ?>)</span>
    <form action="<?= site_url('logout') ?>" method="post" class="d-inline">
      <?= csrf_field() ?>
      <button class="btn btn-outline-light btn-sm">Salir</button>
    </form>
  </nav>
  <div class="container mt-4">
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-warning"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <h4>Bienvenido</h4>
  </div>
</body>
</html>