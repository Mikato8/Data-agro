<?php
/** @var array<int, array<string, mixed>> $inputs */
/** @var string $q */
/** @var bool $low */
?>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Almacén</span><h1>Inventario</h1><p>Insumos, existencias y puntos de reposición.</p></div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/inventario/nuevo')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nuevo insumo</a></div>
</div>
<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/inventario')) ?>">
        <?= icon('search', 'icon icon-sm') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar insumo, SKU o categoría…" aria-label="Buscar insumos">
        <label class="panel-check"><input type="checkbox" name="low" value="1" <?= $low ? 'checked' : '' ?>> Solo stock bajo</label>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
    </form>
</div>
<?php if ($inputs === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('folder', 'icon') ?></span><p>No hay insumos con esos filtros.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/inventario/nuevo')) ?>">Agregar insumo</a></div></div>
<?php else: ?>
<div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Insumo</th><th>SKU</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($inputs as $input): ?>
    <tr>
        <td><a href="<?= e(url('/panel/inventario/' . (int) $input['id'] . '/editar')) ?>"><?= e($input['name']) ?></a></td>
        <td><?= e($input['sku']) ?></td>
        <td><?= e($input['category'] ?? '—') ?></td>
        <td>$<?= number_format((float) $input['price'], 2) ?></td>
        <td><strong class="<?= (int) $input['stock'] <= (int) $input['min_stock'] ? 'text-warning' : '' ?>"><?= (int) $input['stock'] ?></strong></td>
        <td><?= (int) $input['min_stock'] ?></td>
        <td><span class="badge badge-<?= $input['active'] ? 'success' : 'muted' ?>"><?= $input['active'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td><a class="panel-text-link" href="<?= e(url('/panel/inventario/' . (int) $input['id'] . '/editar')) ?>">Editar</a></td>
    </tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
