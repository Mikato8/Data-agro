<?php
/** @var array<int, array<string, mixed>> $products */
/** @var string $q */
/** @var bool $low */
?>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Almacén</span><h1>Inventario</h1><p>Productos, existencias y puntos de reposición.</p></div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/inventario/nuevo')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nuevo producto</a></div>
</div>
<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/inventario')) ?>">
        <?= icon('search', 'icon icon-sm') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar producto, SKU o categoría…" aria-label="Buscar productos">
        <label class="panel-check"><input type="checkbox" name="low" value="1" <?= $low ? 'checked' : '' ?>> Solo stock bajo</label>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
    </form>
</div>
<?php if ($products === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('folder', 'icon') ?></span><p>No hay productos con esos filtros.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/inventario/nuevo')) ?>">Agregar producto</a></div></div>
<?php else: ?>
<div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Producto</th><th>SKU</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($products as $product): ?>
    <tr>
        <td><a href="<?= e(url('/panel/inventario/' . (int) $product['id'] . '/editar')) ?>"><?= e($product['name']) ?></a></td>
        <td><?= e($product['sku']) ?></td>
        <td><?= e($product['category'] ?? '—') ?></td>
        <td>$<?= number_format((float) $product['price'], 2) ?></td>
        <td><strong class="<?= (int) $product['stock'] <= (int) $product['min_stock'] ? 'text-warning' : '' ?>"><?= (int) $product['stock'] ?></strong></td>
        <td><?= (int) $product['min_stock'] ?></td>
        <td><span class="badge badge-<?= $product['active'] ? 'success' : 'muted' ?>"><?= $product['active'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td><a class="panel-text-link" href="<?= e(url('/panel/inventario/' . (int) $product['id'] . '/editar')) ?>">Editar</a></td>
    </tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
