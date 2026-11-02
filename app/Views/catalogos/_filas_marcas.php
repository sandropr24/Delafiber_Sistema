<?php if (!empty($marcas)): ?>
    <?php foreach ($marcas as $mar): ?>
        <?php $marcaActiva = ((int) ($mar['estado'] ?? 1) === 1); ?>
        <tr class="<?= $marcaActiva ? '' : 'text-muted' ?>">
            <td class="ps-4 fw-bold text-muted"><?= (int) $mar['idmarca'] ?></td>
            <td><span class="fw-semibold text-dark"><?= esc($mar['marca']) ?></span></td>
            <td class="text-center">
                <span class="badge bg-light text-dark border"><?= (int) ($mar['total_productos'] ?? 0) ?></span>
            </td>
            <td class="text-center">
                <?php if (array_key_exists('estado', $mar)): ?>
                    <form action="<?= base_url('catalogos/cambiar-estado-marca/' . $mar['idmarca']) ?>" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" title="Clic para alternar estado"
                            class="badge border <?= $marcaActiva ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> px-2 py-1 fw-semibold">
                            <?= $marcaActiva ? 'Activo' : 'Inactivo' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-end pe-4" style="white-space: nowrap;">
                <button type="button"
                    class="btn btn-sm btn-light text-primary border-0 shadow-sm"
                    onclick="editarMarca(<?= esc(json_encode($mar, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                    title="Editar Marca">
                    <i class="bi bi-pencil-square fs-5"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="text-center text-muted py-5">
            <i class="bi bi-c-circle fs-1 text-secondary-emphasis d-block mb-2"></i>
            <p class="mb-0">Aún no hay marcas registradas.</p>
        </td>
    </tr>
<?php endif; ?>