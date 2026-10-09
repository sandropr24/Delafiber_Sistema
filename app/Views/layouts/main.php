<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Inicio') ?> - Delafiber Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('css/estilos.css') ?>" rel="stylesheet">

    <?= $this->renderSection('styles') ?>
</head>

<body>
    <?php
    $userRol = session()->get('rol') ?? '';
    $userName = session()->get('nombreusuario') ?? 'Usuario';
    $userEmail = session()->get('email') ?? 'Sin email';
    ?>

    <div class="d-flex">
        <nav id="sidebar">
            <div class="brand d-flex flex-column align-items-start py-3 px-3">
                <img src="<?= base_url('img/Delafiber.png') ?>" alt="Logo DELAFIBER" style="height: 32px; width: auto; object-fit: contain;">
                <small class="text-white-50 mt-1" style="font-size: 0.72rem; line-height: 1.2;">Gestión de ventas y inventario</small>
            </div>

            <div class="py-2">
                <div class="nav-section-title">Principal</div>
                <a href="<?= base_url('dashboard') ?>" class="sidebar-link <?= url_is('dashboard*') ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                <?php if (in_array($userRol, ['Administrador', 'Almacenero'], true)): ?>
                    <div class="nav-section-title">Operaciones & Stock</div>
                    <?php if ($userRol === 'Administrador'): ?>
                        <a href="<?= base_url('locales') ?>" class="sidebar-link <?= url_is('locales*') ? 'active' : '' ?>">
                            <i class="bi bi-building"></i>
                            <span>Almacenes</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('categorias') ?>" class="sidebar-link <?= url_is('categorias*') ? 'active' : '' ?>">
                        <i class="bi bi-tags"></i>
                        <span>Categorías</span>
                    </a>
                    <a href="<?= base_url('marcas') ?>" class="sidebar-link <?= url_is('marcas*') ? 'active' : '' ?>">
                        <i class="bi bi-bag-check-fill"></i>
                        <span>Marcas</span>
                    </a>
                    <a href="<?= base_url('productos') ?>" class="sidebar-link <?= url_is('productos*') ? 'active' : '' ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>Productos</span>
                    </a>
                    <a href="<?= base_url('kardex') ?>" class="sidebar-link <?= url_is('kardex*') ? 'active' : '' ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>Kardex & Stock</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRol, ['Administrador', 'Cajero'], true)): ?>
                    <div class="nav-section-title">Comercial</div>
                    <a href="<?= base_url('clientes') ?>" class="sidebar-link <?= url_is('personas*') ? 'active' : '' ?>">
                        <i class="bi bi-people"></i>
                        <span>Clientes</span>
                    </a>
                    <a href="<?= base_url('ventas') ?>" class="sidebar-link <?= url_is('ventas*') ? 'active' : '' ?>">
                        <i class="bi bi-percent"></i>
                        <span>Ventas</span>
                    </a>
                    <a href="<?= base_url('pedidos') ?>" class="sidebar-link <?= url_is('pedidos*') ? 'active' : '' ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>Pedidos</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRol, ['Administrador', 'Almacenero'], true)): ?>
                    <div class="nav-section-title">Compras</div>
                    <a href="<?= base_url('proveedores') ?>" class="sidebar-link <?= url_is('proveedores*') ? 'active' : '' ?>">
                        <i class="bi bi-truck"></i>
                        <span>Proveedores</span>
                    </a>
                    <a href="<?= base_url('cotizaciones') ?>" class="sidebar-link <?= url_is('cotizaciones*') ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Cotizaciones</span>
                    </a>
                    <a href="<?= base_url('compras') ?>" class="sidebar-link <?= url_is('compras*') ? 'active' : '' ?>">
                        <i class="bi bi-cart-check"></i>
                        <span>Compras</span>
                    </a>
                <?php endif; ?>

                <?php if ($userRol === 'Administrador'): ?>
                    <div class="nav-section-title">Seguridad</div>
                    <a href="<?= base_url('usuarios') ?>" class="sidebar-link <?= url_is('usuarios*') ? 'active' : '' ?>">
                        <i class="bi bi-person-fill"></i>
                        <span>Usuarios</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <div id="main-content" class="w-100">
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <span class="text-muted small fw-medium">Panel de Administración</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <?php if (session()->get('idusuario')): ?>
                        <div class="text-end d-none d-sm-block">
                            <div class="fw-semibold text-dark" style="font-size: 0.88rem;"><?= esc($userName) ?></div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                <?= esc($userRol) ?>
                            </span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle border p-0 dropdown-toggle" type="button" data-bs-toggle="dropdown" style="width: 38px; height: 38px;">
                                <i class="bi bi-person-fill fs-5 text-secondary"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <h6 class="dropdown-header"><?= esc($userName) ?> (<?= esc($userRol) ?>)</h6>
                                </li>
                                <li>
                                    <span class="dropdown-item-text small text-muted"><?= esc($userEmail) ?></span>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a href="<?= base_url('logout') ?>" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <main class="content-area">
                <?php if (session()->getFlashdata('mensaje') || session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('mensaje') ?? session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('contenido') ?>
            </main>

            <footer class="bg-white border-top py-3 px-4 text-center text-muted small mt-auto">
                <span>&copy; <?= date('Y') ?> Delafiber - Sistema de Ventas - Todos los derechos reservados.</span>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
    <?= $this->renderSection('scripts') ?>
</body>

</html>