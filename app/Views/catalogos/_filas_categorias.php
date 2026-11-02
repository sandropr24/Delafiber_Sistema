<?php if (!empty($categorias)): ?>
    <?php foreach ($categorias as $cat): ?>
        <?php
        $activa = ((int) ($cat['estado'] ?? 1) === 1);
        $esPadre = ($cat['nivel'] === 0);
        $tieneHijos = (!empty($cat['total_hijos']) && (int)$cat['total_hijos'] > 0);
        ?>
        <tr class="<?= $activa ? '' : 'text-muted' ?> <?= $esPadre ? 'fila-padre' : 'fila-hija hijo-de-' . $cat['idcategoriapadre'] . ' d-none bg-light-subtle' ?>"
            <?= $esPadre ? 'data-id="' . $cat['idcategoria'] . '"' : '' ?>>

            <td class="ps-4 fw-bold text-muted"><?= $esPadre ? (int) $cat['idcategoria'] : '' ?></td>
            <td>
                <span class="<?= $esPadre ? 'fw-bold' : 'fw-semibold text-secondary' ?>"
                    style="padding-left: <?= $cat['nivel'] * 1.5 ?>rem">
                    <?php if ($esPadre): ?>
                        <?php if ($tieneHijos): ?>
                            <span class="btn-toggle-hijos me-1 text-primary" style="cursor: pointer; display: inline-block; width: 15px;">▸</span>
                        <?php else: ?>
                            <span class="me-1 text-muted" style="display: inline-block; width: 15px;">•</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted me-1">└─</span>
                    <?php endif; ?>
                    <?= esc($cat['categoria']) ?>
                </span>
            </td>
            <td>
                <?php if (!$esPadre && !empty($cat['categoriapadre'])): ?>
                    <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.75rem;"><?= esc($cat['categoriapadre']) ?></span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border"><?= (int) $cat['total_productos'] ?></span>
                <?php if (!empty($cat['productos_hijos'])): ?>
                    <div class="text-muted" style="font-size: .7rem;">+<?= (int) $cat['productos_hijos'] ?> en subs</div>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <form action="<?= base_url('catalogos/cambiar-estado-categoria/' . $cat['idcategoria']) ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" title="Clic para alternar estado"
                        class="badge border <?= $activa ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> px-2 py-1 fw-semibold">
                        <?= $activa ? 'Activo' : 'Inactivo' ?>
                    </button>
                </form>
            </td>
            <td class="text-end pe-4" style="white-space: nowrap;">
                <?php if ($esPadre && $activa): ?>
                    <button type="button"
                        class="btn btn-sm btn-light text-success border-0 shadow-sm"
                        onclick="nuevaSubcategoria(<?= (int) $cat['idcategoria'] ?>)"
                        title="Agregar subcategoría">
                        <i class="bi bi-node-plus fs-5"></i>
                    </button>
                <?php endif; ?>
                <button type="button"
                    class="btn btn-sm btn-light text-primary border-0 shadow-sm"
                    onclick="editarCategoria(<?= esc(json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                    title="Editar Categoría">
                    <i class="bi bi-pencil-square fs-5"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="text-center text-muted py-5">
            <i class="bi bi-tags fs-1 text-secondary-emphasis d-block mb-2"></i>
            <p class="mb-0">No hay categorías registradas aún.</p>
        </td>
    </tr>
<?php endif; ?>