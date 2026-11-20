<?= $this->extend('tienda/layout') ?>
<?= $this->section('contenido') ?>
<?php
$tipos = ['PRODUCTO' => 'Producto', 'SERVICIO' => 'Servicio', 'COMBO' => 'Combo'];
$parametros = array_intersect_key($filtrosCatalogo, array_flip(['q', 'tipo', 'categoria', 'marca', 'precio_min', 'precio_max', 'orden']));
$urlCatalogo = static fn(array $cambios = []) => base_url('/') . '?' . http_build_query(array_filter(array_replace($parametros, $cambios), static fn($valor) => $valor !== '' && $valor !== 0)) . '#catalogo';
$filtrosActivos = [];
if ($q !== '') $filtrosActivos[] = ['texto' => 'Búsqueda: ' . $q, 'url' => $urlCatalogo(['q' => ''])];
if ($tipo !== '') $filtrosActivos[] = ['texto' => $tipos[$tipo], 'url' => $urlCatalogo(['tipo' => ''])];
if ($categoria > 0) {
    $nombresCategorias = array_column($categoriasTienda, 'categoria', 'idcategoria');
    $filtrosActivos[] = ['texto' => $nombresCategorias[$categoria] ?? 'Categoría seleccionada', 'url' => $urlCatalogo(['categoria' => 0])];
}
if ($marca > 0) {
    $nombresMarcas = array_column($marcasTienda, 'marca', 'idmarca');
    $filtrosActivos[] = ['texto' => $nombresMarcas[$marca] ?? 'Marca seleccionada', 'url' => $urlCatalogo(['marca' => 0])];
}
if ($precio_min !== '' || $precio_max !== '') {
    $rango = ($precio_min !== '' ? 'Desde S/ ' . $precio_min : '') . ($precio_min !== '' && $precio_max !== '' ? ' · ' : '') . ($precio_max !== '' ? 'Hasta S/ ' . $precio_max : '');
    $filtrosActivos[] = ['texto' => $rango, 'url' => $urlCatalogo(['precio_min' => '', 'precio_max' => ''])];
}
?>
<?php if (!$filtrosActivos && $pagina === 1 && $orden === 'nombre_asc'): ?>
<section class="campaign-hero" aria-label="Tecnología que conecta">
    <img src="<?= base_url('img/banner-rosa.png') ?>" alt="Tecnología que conecta, soluciones que venden. Productos electrónicos para un mundo en movimiento.">
    <div class="campaign-mobile-copy"><p>Tecnología que conecta</p><h2>Soluciones para seguir en movimiento.</h2><a class="button" href="#catalogo">Ver catálogo</a></div>
</section>

<section class="benefits" aria-label="Beneficios de compra">
    <div><span aria-hidden="true">✓</span><p><strong>Productos seleccionados</strong><small>Calidad para cada necesidad</small></p></div>
    <div><span aria-hidden="true">◇</span><p><strong>Garantía informada</strong><small>Compra con mayor confianza</small></p></div>
    <div><span aria-hidden="true">↗</span><p><strong>Atención especializada</strong><small>Te ayudamos a elegir</small></p></div>
    <div><span aria-hidden="true">◎</span><p><strong>Seguimiento de pedido</strong><small>Consulta el estado en línea</small></p></div>
</section>

<?php $categoriasDestacadas = array_values(array_filter($categoriasTienda, static fn($item) => empty($item['idcategoriapadre']))); ?>
<?php if ($categoriasDestacadas): ?><section class="category-shortcuts" aria-labelledby="category-title"><div class="section-row"><div><p class="eyebrow">ENCUENTRA LO QUE NECESITAS</p><h2 id="category-title">Compra por categoría</h2></div><a href="#catalogo">Ver catálogo</a></div><div class="category-list"><?php foreach (array_slice($categoriasDestacadas, 0, 8) as $indice => $categoriaItem): ?><a class="category-chip" href="<?= base_url('/') . '?' . http_build_query(['categoria' => $categoriaItem['idcategoria']]) ?>"><span aria-hidden="true"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span><strong><?= esc($categoriaItem['categoria']) ?></strong><small><?= (int) $categoriaItem['productos'] ?> <?= (int) $categoriaItem['productos'] === 1 ? 'artículo' : 'artículos' ?></small></a><?php endforeach; ?></div></section><?php endif; ?>
<?php endif; ?>

<?php foreach ($avisos as $aviso): ?><div class="notice" role="status"><?= esc($aviso) ?></div><?php endforeach; ?>
<form id="filtros-catalogo" class="filters catalog-filters" method="get" action="<?= base_url('/') ?>#catalogo" aria-label="Filtros del catálogo">
    <div class="filters-heading"><strong>Encuentra tu solución</strong><span>Combina filtros y compara precios referenciales en soles.</span></div>
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= esc($q, 'attr') ?>"><?php endif; ?>
    <label for="catalog-type"><span>Tipo de solución</span><select id="catalog-type" name="tipo">
        <option value="">Todos</option>
        <?php foreach (['PRODUCTO' => 'Productos', 'SERVICIO' => 'Servicios', 'COMBO' => 'Combos'] as $valor => $etiqueta): ?><option value="<?= $valor ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option><?php endforeach; ?>
    </select></label>
    <label for="catalog-category"><span>Categoría</span><select id="catalog-category" name="categoria"><option value="">Todas</option><?php foreach ($categoriasTienda as $categoriaFiltro): ?><option value="<?= (int) $categoriaFiltro['idcategoria'] ?>" <?= $categoria === (int) $categoriaFiltro['idcategoria'] ? 'selected' : '' ?>><?= !empty($categoriaFiltro['idcategoriapadre']) ? '— ' : '' ?><?= esc($categoriaFiltro['categoria']) ?></option><?php endforeach; ?></select></label>
    <label for="catalog-brand"><span>Marca</span><select id="catalog-brand" name="marca"><option value="">Todas las marcas</option><?php foreach ($marcasTienda as $marcaFiltro): ?><option value="<?= (int) $marcaFiltro['idmarca'] ?>" <?= $marca === (int) $marcaFiltro['idmarca'] ? 'selected' : '' ?>><?= esc($marcaFiltro['marca']) ?></option><?php endforeach; ?></select></label>
    <fieldset class="price-filter"><legend>Precio referencial (S/)</legend><div><label class="sr-only" for="precio-min">Precio mínimo en soles</label><input id="precio-min" type="number" name="precio_min" min="0" max="99999999.99" step="0.01" inputmode="decimal" placeholder="Desde" value="<?= esc($precio_min, 'attr') ?>"><span aria-hidden="true">—</span><label class="sr-only" for="precio-max">Precio máximo en soles</label><input id="precio-max" type="number" name="precio_max" min="0" max="99999999.99" step="0.01" inputmode="decimal" placeholder="Hasta" value="<?= esc($precio_max, 'attr') ?>"></div></fieldset>
    <label for="catalog-order"><span>Ordenar por</span><select id="catalog-order" name="orden"><?php foreach ($ordenesCatalogo as $valor => $etiqueta): ?><option value="<?= esc($valor, 'attr') ?>" <?= $orden === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option><?php endforeach; ?></select></label>
    <div class="filter-actions"><button type="submit">Ver resultados</button><?php if ($filtrosActivos || $orden !== 'nombre_asc'): ?><a class="clear-link" href="<?= base_url('/') ?>#catalogo">Limpiar filtros</a><?php endif; ?></div>
</form>

<section id="catalogo" aria-labelledby="catalog-title">
    <div class="catalog-head">
        <div><p class="eyebrow">EXPLORA DELAFIBER</p><h1 id="catalog-title"><?= $q !== '' ? 'Resultados para “' . esc($q) . '”' : ($filtrosActivos ? 'Tu selección' : 'Catálogo de soluciones') ?></h1></div>
        <p><strong><?= (int) $total ?></strong> <?= (int) $total === 1 ? 'resultado' : 'resultados' ?></p>
    </div>
    <?php if ($filtrosActivos): ?><nav class="active-filters" aria-label="Filtros aplicados"><?php foreach ($filtrosActivos as $filtro): ?><a href="<?= esc($filtro['url'], 'attr') ?>" aria-label="<?= esc('Quitar filtro: ' . $filtro['texto'], 'attr') ?>"><?= esc($filtro['texto']) ?><span aria-hidden="true">×</span></a><?php endforeach; ?></nav><?php endif; ?>
    <div class="product-grid">
        <?php foreach ($items as $item): ?>
            <article class="product-card">
                <a class="product-image" href="<?= base_url('producto/' . $item['idproducto']) ?>" aria-label="Ver <?= esc($item['nombre'], 'attr') ?>">
                    <span class="type-badge"><?= esc($tipos[$item['tipo']] ?? ucfirst(strtolower($item['tipo']))) ?></span>
                    <?php if ($item['imagen']): ?><img src="<?= base_url('uploads/productos/' . rawurlencode(basename($item['imagen']))) ?>" alt="<?= esc($item['nombre'], 'attr') ?>" loading="lazy" decoding="async"><?php else: ?><span class="image-placeholder" aria-hidden="true"><?= esc(substr($item['tipo'], 0, 1)) ?></span><?php endif; ?>
                </a>
                <div class="product-body">
                    <span class="product-category"><?= esc($item['categoria']) ?></span>
                    <h3><a href="<?= base_url('producto/' . $item['idproducto']) ?>"><?= esc($item['nombre']) ?></a></h3>
                    <?php if ($item['marca'] || $item['modelo']): ?><span class="product-model"><?= esc(implode(' · ', array_filter([$item['marca'], $item['modelo']]))) ?></span><?php endif; ?>
                    <p><?= esc($item['descripcion'] ?: 'Solución disponible en Delafiber.') ?></p>
                    <?php if ($item['tipo'] === 'PRODUCTO' && (int) $item['garantiameses'] > 0): ?><span class="product-warranty"><?= (int) $item['garantiameses'] ?> <?= (int) $item['garantiameses'] === 1 ? 'mes' : 'meses' ?> de garantía</span><?php endif; ?>
                    <div class="price-row"><div><span>Precio referencial</span><strong>S/ <?= number_format((float) $item['precioventa'], 2) ?></strong><small>por <?= esc($item['unidad'] ?: 'unidad') ?></small></div></div>
                    <div class="card-actions"><a class="detail-link" href="<?= base_url('producto/' . $item['idproducto']) ?>">Ver detalle</a><form method="post" action="<?= base_url('carrito/agregar') ?>" data-cart-add><?= csrf_field() ?><input type="hidden" name="idproducto" value="<?= (int) $item['idproducto'] ?>"><input type="hidden" name="cantidad" value="1"><button type="submit" aria-label="Agregar <?= esc($item['nombre'], 'attr') ?> al carrito">Agregar</button></form></div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$items): ?><div class="empty"><span class="empty-mark" aria-hidden="true">⌕</span><h2>No encontramos coincidencias</h2><p>Prueba con otro término o consulta todas nuestras soluciones.</p><a class="button" href="<?= base_url('/') ?>">Ver todo el catálogo</a></div><?php endif; ?>
    </div>
</section>
<?php if ($paginas > 1): ?><nav class="pager" aria-label="Paginación del catálogo"><?php if ($pagina > 1): ?><a rel="prev" href="<?= esc($urlCatalogo(['pagina' => $pagina - 1]), 'attr') ?>"><span aria-hidden="true">←</span> Anterior</a><?php else: ?><span class="pager-disabled">Anterior</span><?php endif; ?><span aria-current="page">Página <strong><?= $pagina ?></strong> de <?= $paginas ?></span><?php if ($pagina < $paginas): ?><a rel="next" href="<?= esc($urlCatalogo(['pagina' => $pagina + 1]), 'attr') ?>">Siguiente <span aria-hidden="true">→</span></a><?php else: ?><span class="pager-disabled">Siguiente</span><?php endif; ?></nav><?php endif; ?>
<?= $this->endSection() ?>
