<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/pedidos/index.css') ?>?v=<?= filemtime(FCPATH . 'css/pedidos/index.css') ?>"><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php $estados = ['PENDIENTE' => 'Pendiente', 'CONFIRMADO' => 'Confirmado', 'CONVERTIDO' => 'Convertido a venta', 'CANCELADO' => 'Cancelado'];
$cantidades = array_column($resumen, 'cantidad', 'codigo'); ?>
<div class="container-fluid px-3 px-lg-4 py-4 df-pedidos">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="pedido-eyebrow">Comercial / Pedidos</p>
            <h1 class="h3 fw-bold mb-1">Pedidos</h1>
            <p class="text-muted small mb-0">Prepara lo solicitado por el cliente y conviértelo a venta cuando vaya a pagar.</p>
        </div>
        <a href="<?= base_url('admin/pedidos/crear') ?>" class="btn pedido-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo pedido</a>
    </div>
    <div class="row g-3 mb-4"><?php foreach ($estados as $codigo => $nombre): ?><div class="col-6 col-xl-3"><a class="pedido-kpi" href="<?= base_url('admin/pedidos') . '?estado=' . $codigo ?>"><span><?= $nombre ?></span><strong><?= (int) ($cantidades[$codigo] ?? 0) ?></strong></a></div><?php endforeach; ?></div>
    <div class="card pedido-card">
        <div class="card-body p-3 p-lg-4">
            <form method="get" action="<?= base_url('admin/pedidos') ?>" class="row g-2 mb-3">
                <div class="col-lg-6"><label for="buscarPedido" class="form-label">Código, cliente o documento</label><input id="buscarPedido" name="q" type="search" maxlength="150" class="form-control" value="<?= esc($filtros['q'], 'attr') ?>" placeholder="PED-000001, nombre o DNI"></div>
                <div class="col-lg-3"><label for="estadoPedido" class="form-label">Estado</label><select id="estadoPedido" name="estado" class="form-select">
                        <option value="">Todos</option><?php foreach ($estados as $codigo => $nombre): ?><option value="<?= $codigo ?>" <?= $filtros['estado'] === $codigo ? 'selected' : '' ?>><?= $nombre ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-lg-3"><label for="vendedorPedido" class="form-label">Responsable</label><select id="vendedorPedido" name="vendedor" class="form-select">
                        <option value="">Todos</option><?php foreach ($vendedores as $v): ?><option value="<?= (int) $v['idusuario'] ?>" <?= $filtros['vendedor'] === (int) $v['idusuario'] ? 'selected' : '' ?>><?= esc($v['nombreusuario']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-12 d-flex gap-2"><button class="btn pedido-primary" type="submit">Buscar pedidos</button><a href="<?= base_url('admin/pedidos') ?>" class="btn btn-light border">Limpiar filtros</a></div>
            </form>
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 fw-bold">Pedidos registrados</h2><span class="small text-muted"><?= (int) $total ?> resultado(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>Pedido / Fecha</th>
                            <th>Cliente</th>
                            <th>Local / Responsable</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?><tr class="fila-pedido">
                                <td><strong><?= esc($p['codigo']) ?></strong><small class="d-block text-muted"><?= esc(date('d/m/Y H:i', strtotime($p['fecha']))) ?></small></td>
                                <td><?= esc($p['cliente']) ?></td>
                                <td><?= esc($p['nombrelocal']) ?><small class="d-block text-muted"><?= esc($p['nombreusuario']) ?></small></td>
                                <td><span class="pedido-estado estado-<?= esc($p['estado_codigo'], 'attr') ?>"><?= esc($p['estado']) ?></span></td>
                                <td class="text-end text-nowrap">S/ <?= number_format((float) $p['total'], 2) ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('admin/pedidos/detalles/' . $p['idpedido']) ?>">Ver pedido</a></td>
                            </tr><?php endforeach; ?>
                        <?php if (!$pedidos): ?><tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay pedidos que coincidan con la búsqueda.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($paginas > 1): ?><nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Páginas de pedidos"><?php if ($pagina > 1): ?><a class="btn btn-light border btn-sm" href="<?= base_url('admin/pedidos') . '?' . esc(http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])), 'attr') ?>">Anterior</a><?php else: ?><span></span><?php endif; ?><span class="small text-muted">Página <?= $pagina ?> de <?= $paginas ?></span><?php if ($pagina < $paginas): ?><a class="btn btn-light border btn-sm" href="<?= base_url('admin/pedidos') . '?' . esc(http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])), 'attr') ?>">Siguiente</a><?php else: ?><span></span><?php endif; ?></nav><?php endif; ?>
        </div>
    </div>
    <p class="small text-muted mt-3">También puedes registrar una venta directa sin crear un pedido.</p>
</div>
<?= $this->endSection() ?>