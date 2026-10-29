<?php
$clasesTipo = [
    'PRODUCTO' => 'bg-primary-subtle text-primary border border-primary-subtle',
    'SERVICIO' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
    'COMBO'    => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
];
?>
<?php if (empty($productos)): ?>
    <tr>
        <td colspan="10" class="text-center text-muted py-4">No se encontraron productos.</td>
    </tr>
<?php else: ?>
    <?php foreach ($productos as $p): ?>
        <?php
        $activo   = ((int) $p['estado'] === 1);
        $simbolo  = ($p['monedacosto'] ?? 'USD') === 'USD' ? 'US$' : 'S/';
        ?>
        <tr class="<?= $activo ? '' : 'text-muted' ?>">
            <td class="ps-4">
                <?php if (!empty($p['imagen']) && is_file(FCPATH . 'uploads/productos/' . $p['imagen'])): ?>
                    <img src="<?= base_url('uploads/productos/' . esc($p['imagen'])) ?>"
                        alt="<?= esc($p['nombre']) ?>"
                        class="rounded border object-fit-cover shadow-sm"
                        style="width: 44px; height: 44px; background: #f8f9fa; padding: 2px;">
                <?php else: ?>
                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-secondary"
                        style="width: 44px; height: 44px;">
                        <i class="bi bi-image fs-5"></i>
                    </div>
                <?php endif; ?>
            </td>

            <td>
                <div class="fw-semibold text-dark"><?= esc($p['nombre']) ?></div>
                <?php if (!empty($p['descripcion']) || !empty($p['modelo'])): ?>
                    <div class="text-muted small text-truncate" style="max-width: 260px;">
                        <?= esc(trim(($p['modelo'] ?? '') . ' ' . ($p['descripcion'] ?? ''))) ?>
                    </div>
                <?php endif; ?>
            </td>

            <td>
                <span class="badge <?= $clasesTipo[$p['tipo']] ?? 'bg-light text-dark border' ?>"><?= esc($p['tipo']) ?></span>
            </td>

            <td><span class="badge bg-light text-dark border"><?= esc($p['categoria']) ?></span></td>
            <td><?= !empty($p['marca']) ? esc($p['marca']) : '<span class="text-muted">—</span>' ?></td>
            <td class="text-muted small font-monospace"><?= esc($p['codigobarras'] ?: '-') ?></td>

            <td class="text-end text-muted" style="white-space: nowrap;">
                <?= $simbolo ?> <?= number_format((float) $p['preciocosto'], 2) ?>
            </td>
            <td class="text-end fw-bold text-dark" style="white-space: nowrap;">
                S/ <?= number_format((float) $p['precioventa'], 2) ?>
            </td>

            <td class="text-center">
                <form action="<?= base_url('productos/cambiarestado/' . (int) $p['idproducto']) ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" title="Clic para cambiar estado"
                        class="badge border <?= $activo ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> px-2 py-1">
                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                    </button>
                </form>
            </td>

            <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-light text-primary border-0 me-1 shadow-sm"
                    onclick="editarProducto(<?= esc(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                    title="Editar Producto">
                    <i class="bi bi-pencil-square fs-5"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>