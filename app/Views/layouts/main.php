<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Inicio') ?> - Delafiber Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('css/estilos.css') ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

                    <a href="<?= base_url('catalogos') ?>" class="sidebar-link <?= url_is('catalogos*') ? 'active' : '' ?>">
                        <i class="bi bi-tags"></i>
                        <span>Catálogos</span>
                    </a>

                    <a href="<?= base_url('productos') ?>" class="sidebar-link <?= url_is('productos*') ? 'active' : '' ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>Productos</span>
                    </a>
                    <a href="<?= base_url('kardex') ?>" class="sidebar-link <?= url_is('kardex*') ? 'active' : '' ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>Kardex & Stock</span>
                    </a>
                    <a href="<?= base_url('entregas') ?>" class="sidebar-link <?= url_is('entregas*') ? 'active' : '' ?>">
                        <i class="bi bi-send"></i>
                        <span>Entregas</span>
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
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2" style="min-width: 220px; border-radius: 10px;">
                                <li class="px-3 py-2 border-bottom mb-1">
                                    <span class="d-block fw-bold text-dark small"><?= esc($userName) ?></span>
                                    <span class="text-muted small text-truncate d-block" style="font-size: 0.8rem;"><?= esc($userEmail) ?></span>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 px-3 text-danger d-flex align-items-center gap-2" href="<?= base_url('logout') ?>">
                                        <i class="bi bi-box-arrow-right fs-5"></i>
                                        <span class="fw-semibold small">Cerrar Sesión</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <main class="content-area position-relative">
                <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
                    <?php if (session()->getFlashdata('mensaje') || session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-lg border-0 text-white bg-success mb-2" role="alert" style="min-width: 280px;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                                <div><?= session()->getFlashdata('mensaje') ?? session()->getFlashdata('success') ?></div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-lg border-0 text-white bg-danger mb-2" role="alert" style="min-width: 280px;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                                <div><?= session()->getFlashdata('error') ?></div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>

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

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                let alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(() => alert.remove(), 300);
                });
            }, 3000);
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>

</html>