<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'Tienda Delafiber') ?></title>
    <meta name="description" content="<?= esc($descripcionPagina ?? 'Equipos, servicios técnicos y combos DELAFIBER. Explora el catálogo y coordina tu pedido sin pagos en línea.', 'attr') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('css/tienda.css') ?>?v=<?= filemtime(FCPATH . 'css/tienda.css') ?>">
</head>

<body>
    <?php
    $tipoActivo = $tipo ?? '';
    $categoriaActiva = (int) ($categoria ?? 0);
    $categoriasPrincipales = array_values(array_filter($categoriasTienda, static fn($item) => empty($item['idcategoriapadre'])));
    $subcategoriasPorPadre = [];
    foreach ($categoriasTienda as $categoriaMenu) if (!empty($categoriaMenu['idcategoriapadre'])) $subcategoriasPorPadre[(int) $categoriaMenu['idcategoriapadre']][] = $categoriaMenu;
    ?>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="store-header">
        <div class="utility-bar"><div><span>Compra segura y atención especializada</span><span>Precios en soles (S/)</span></div></div>
        <div class="header-inner header-main">
            <a class="brand" href="<?= base_url('/') ?>" aria-label="Delafiber, ir al catálogo"><img src="<?= base_url('img/Delafiber.png') ?>" alt="Delafiber"><span>Tienda</span></a>
            <form class="header-search" method="get" action="<?= base_url('/') ?>" role="search">
                <?php foreach (['tipo', 'categoria', 'marca', 'precio_min', 'precio_max', 'orden'] as $campoFiltro): ?><?php if (isset($filtrosCatalogo[$campoFiltro]) && $filtrosCatalogo[$campoFiltro] !== '' && $filtrosCatalogo[$campoFiltro] !== 0): ?><input type="hidden" name="<?= esc($campoFiltro, 'attr') ?>" value="<?= esc($filtrosCatalogo[$campoFiltro], 'attr') ?>"><?php endif; ?><?php endforeach; ?>
                <label class="sr-only" for="global-search">Buscar en la tienda</label>
                <input id="global-search" type="search" name="q" maxlength="100" value="<?= esc($busquedaGlobal, 'attr') ?>" placeholder="Buscar productos, marcas o categorías">
                <button type="submit" aria-label="Buscar"><span aria-hidden="true">⌕</span><span>Buscar</span></button>
            </form>
            <nav class="account-nav" aria-label="Cuenta y carrito">
                <?php if ($clienteWeb): ?>
                    <a class="account-link <?= url_is('cuenta') ? 'active' : '' ?>" href="<?= base_url('cuenta') ?>"><span>Hola</span><strong>Mi cuenta</strong></a>
                <?php else: ?>
                    <a class="account-link <?= url_is('cliente/login') || url_is('cliente/registro') ? 'active' : '' ?>" href="<?= base_url('cliente/login') ?>"><span>Identifícate</span><strong>Mi cuenta</strong></a>
                <?php endif; ?>
                <a class="cart-link <?= url_is('carrito') || url_is('checkout') ? 'active' : '' ?>" href="<?= base_url('carrito') ?>" <?= url_is('carrito') || url_is('checkout') ? 'aria-current="page"' : '' ?>>
                    <span aria-hidden="true">▣</span><span>Carrito</span><strong data-cart-count aria-label="<?= (int) $carritoCantidad ?> <?= (int) $carritoCantidad === 1 ? 'artículo distinto' : 'artículos distintos' ?>"><?= (int) $carritoCantidad ?></strong>
                </a>
            </nav>
        </div>
        <nav class="category-nav" aria-label="Categorías de la tienda"><div class="category-nav-inner">
            <details class="category-menu <?= $categoriaActiva > 0 ? 'active' : '' ?>" data-category-menu>
                <summary><span>Categorías</span><span class="category-chevron" aria-hidden="true">⌄</span></summary>
                <div class="category-menu-panel">
                    <div class="category-menu-head"><div><strong>Explora por categoría</strong><small>Encuentra productos y servicios más rápido</small></div><a href="<?= base_url('/') ?>">Ver todo</a></div>
                    <div class="category-menu-grid">
                        <?php foreach ($categoriasPrincipales as $principal): $hijas = $subcategoriasPorPadre[(int) $principal['idcategoria']] ?? []; ?>
                            <section class="category-menu-group">
                                <a class="category-parent <?= $categoriaActiva === (int) $principal['idcategoria'] ? 'active' : '' ?>" href="<?= base_url('/') . '?' . http_build_query(['categoria' => $principal['idcategoria']]) ?>" <?= $categoriaActiva === (int) $principal['idcategoria'] ? 'aria-current="page"' : '' ?>><span><?= esc($principal['categoria']) ?></span><small><?= (int) $principal['productos'] ?> <?= (int) $principal['productos'] === 1 ? 'artículo' : 'artículos' ?></small></a>
                                <?php if ($hijas): ?><div class="category-children"><?php foreach ($hijas as $hija): ?><a class="<?= $categoriaActiva === (int) $hija['idcategoria'] ? 'active' : '' ?>" href="<?= base_url('/') . '?' . http_build_query(['categoria' => $hija['idcategoria']]) ?>" <?= $categoriaActiva === (int) $hija['idcategoria'] ? 'aria-current="page"' : '' ?>><?= esc($hija['categoria']) ?></a><?php endforeach; ?></div><?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
            <div class="category-quick-links">
                <a class="<?= url_is('/') && $tipoActivo === '' && $categoriaActiva === 0 ? 'active' : '' ?>" href="<?= base_url('/') ?>" <?= url_is('/') && $tipoActivo === '' && $categoriaActiva === 0 ? 'aria-current="page"' : '' ?>>Inicio</a>
                <?php foreach (array_slice($categoriasPrincipales, 0, 4) as $categoriaNav): $categoriaEsActiva = url_is('/') && $categoriaActiva === (int) $categoriaNav['idcategoria']; ?><a class="<?= $categoriaEsActiva ? 'active' : '' ?>" href="<?= base_url('/') . '?' . http_build_query(['categoria' => $categoriaNav['idcategoria']]) ?>" <?= $categoriaEsActiva ? 'aria-current="page"' : '' ?>><?= esc($categoriaNav['categoria']) ?></a><?php endforeach; ?>
                <a class="<?= url_is('/') && $tipoActivo === 'SERVICIO' ? 'active' : '' ?>" href="<?= base_url('/') . '?tipo=SERVICIO' ?>" <?= url_is('/') && $tipoActivo === 'SERVICIO' ? 'aria-current="page"' : '' ?>>Servicios</a>
                <a class="<?= url_is('/') && $tipoActivo === 'COMBO' ? 'active' : '' ?>" href="<?= base_url('/') . '?tipo=COMBO' ?>" <?= url_is('/') && $tipoActivo === 'COMBO' ? 'aria-current="page"' : '' ?>>Combos</a>
            </div>
        </div></nav>
    </header>
    <main class="store-main" id="contenido" tabindex="-1">
        <?php if (session()->getFlashdata('mensaje')): ?><div class="notice success" role="status"><strong>Listo.</strong> <?= esc(session()->getFlashdata('mensaje')) ?></div><?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?><div class="notice error" role="alert"><strong>Revisa lo siguiente.</strong> <?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
        <?= $this->renderSection('contenido') ?>
    </main>
    <footer class="store-footer">
        <div class="footer-inner">
            <div class="footer-brand"><strong>DELAFIBER</strong><span>Tecnología, conectividad y soporte para mantenerte siempre en marcha.</span></div>
            <div class="footer-column"><strong>Compra</strong><nav aria-label="Navegación de compra"><a href="<?= base_url('/') ?>">Catálogo</a><a href="<?= base_url('/') . '?tipo=PRODUCTO' ?>">Productos</a><a href="<?= base_url('/') . '?tipo=SERVICIO' ?>">Servicios</a><a href="<?= base_url('carrito') ?>">Carrito</a></nav></div>
            <div class="footer-column"><strong>Tu cuenta</strong><nav aria-label="Navegación de cuenta"><?php if ($clienteWeb): ?><a href="<?= base_url('cuenta') ?>">Mis pedidos</a><?php else: ?><a href="<?= base_url('cliente/login') ?>">Ingresar</a><a href="<?= base_url('cliente/registro') ?>">Crear cuenta</a><?php endif; ?><a href="<?= base_url('carrito') ?>">Finalizar compra</a></nav></div>
            <div class="footer-trust"><strong>Compra con confianza</strong><p>Atención especializada, garantía informada y seguimiento de cada pedido.</p></div>
            <p class="footer-legal">© <?= date('Y') ?> Delafiber. Precios y disponibilidad sujetos a confirmación.</p>
        </div>
    </footer>
    <aside class="cart-feedback" data-cart-feedback hidden aria-label="Aviso del carrito">
        <div class="cart-feedback-heading"><strong data-cart-feedback-title>Tu carrito</strong><button type="button" class="cart-feedback-close" data-cart-dismiss aria-label="Cerrar aviso del carrito">×</button></div>
        <p class="cart-feedback-message" data-cart-announcement role="status" aria-live="polite" aria-atomic="true"></p>
        <p class="cart-feedback-product" data-cart-product hidden></p>
        <p class="cart-feedback-total" data-cart-total hidden></p>
        <div class="cart-feedback-actions">
            <button type="button" class="button secondary" data-cart-continue>Seguir comprando</button>
            <button type="button" class="button secondary" data-cart-reload hidden>Recargar página</button>
            <a class="button" href="<?= base_url('carrito') ?>">Ver carrito</a>
        </div>
        <small>Precios referenciales. No se ha realizado ningún cobro.</small>
    </aside>
    <script src="<?= base_url('js/tienda.js') ?>?v=<?= filemtime(FCPATH . 'js/tienda.js') ?>" defer></script>
    <script src="<?= base_url('js/tienda-carrito.js') ?>?v=<?= filemtime(FCPATH . 'js/tienda-carrito.js') ?>" defer></script>
    <?= $this->renderSection('scripts') ?>
</body>

</html>
