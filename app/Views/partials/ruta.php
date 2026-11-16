<nav class="df-breadcrumb" aria-label="Ruta de navegación">
    <?php if (!empty($uiSectionUrl)): ?>
        <a href="<?= esc(base_url($uiSectionUrl), 'attr') ?>"><?= esc($uiSection) ?></a>
    <?php else: ?>
        <span><?= esc($uiSection) ?></span>
    <?php endif; ?>
    <i class="bi bi-chevron-right" aria-hidden="true"></i>
    <span aria-current="page"><?= esc($uiCurrent) ?></span>
</nav>
