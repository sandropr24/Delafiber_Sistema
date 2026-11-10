<?php
$total = (int) $paginacion['total'];
$pagina = (int) $paginacion['pagina'];
$totalPaginas = (int) $paginacion['totalPaginas'];
$desde = (int) $paginacion['desde'];
$hasta = (int) $paginacion['hasta'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="small text-muted">
        <?php if ($total > 0): ?>
            Mostrando <strong><?= $desde ?>–<?= $hasta ?></strong> de <strong><?= $total ?></strong> artículos
        <?php else: ?>
            No hay artículos con los filtros seleccionados
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <label class="small text-muted" for="porPaginaProductos">Mostrar</label>
        <select class="form-select form-select-sm" id="porPaginaProductos" style="width: 76px" aria-label="Productos por página">
            <?php foreach ([10, 20, 50] as $cantidad): ?>
                <option value="<?= $cantidad ?>" <?= (int) $paginacion['porPagina'] === $cantidad ? 'selected' : '' ?>><?= $cantidad ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Paginación de productos">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" data-pagina="<?= $pagina - 1 ?>" <?= $pagina <= 1 ? 'disabled' : '' ?> aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>
                    <?php
                    $inicio = max(1, min($pagina - 2, $totalPaginas - 4));
                    $fin = min($totalPaginas, $inicio + 4);
                    for ($numero = $inicio; $numero <= $fin; $numero++):
                    ?>
                        <button type="button" class="btn <?= $numero === $pagina ? 'btn-primary' : 'btn-outline-secondary' ?>" data-pagina="<?= $numero ?>" <?= $numero === $pagina ? 'aria-current="page"' : '' ?>><?= $numero ?></button>
                    <?php endfor; ?>
                    <button type="button" class="btn btn-outline-secondary" data-pagina="<?= $pagina + 1 ?>" <?= $pagina >= $totalPaginas ? 'disabled' : '' ?> aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>
