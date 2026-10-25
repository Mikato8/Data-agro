<?php
/** @var array<string, mixed>|null $product */
$editing = $product !== null;
$action = $editing ? '/panel/inventario/' . (int) $product['id'] : '/panel/inventario';
?>
<a class="panel-back" href="<?= e(url('/panel/inventario')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Inventario</a>
<div class="panel-page-head"><div><span class="panel-eyebrow"><?= $editing ? 'Editar producto' : 'Nuevo producto' ?></span><h1><?= $editing ? e($product['name']) : 'Agregar al inventario' ?></h1><p>Controla precio, existencias y mínimo de reposición.</p></div></div>

<form class="panel-card panel-form" method="post" action="<?= e(url($action)) ?>">
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field"><label for="name">Nombre <span class="req">*</span></label><input id="name" name="name" type="text" required maxlength="120" value="<?= e(old('name', $product['name'] ?? '')) ?>" placeholder="Ej. Meloxicam 5 mg/ml"></div>
        <div class="field"><label for="sku">SKU <span class="req">*</span></label><input id="sku" name="sku" type="text" required maxlength="40" value="<?= e(old('sku', $product['sku'] ?? '')) ?>" placeholder="MED-001"></div>
        <div class="field"><label for="category">Categoría</label><input id="category" name="category" type="text" maxlength="60" value="<?= e(old('category', $product['category'] ?? '')) ?>" placeholder="Medicamento, alimento, insumo…"></div>
        <div class="field"><label for="price">Precio (MXN) <span class="req">*</span></label><input id="price" name="price" type="number" min="0" step="0.01" required value="<?= e(old('price', $product['price'] ?? '0.00')) ?>"></div>
        <div class="field"><label for="stock">Existencias <span class="req">*</span></label><input id="stock" name="stock" type="number" min="0" step="1" required value="<?= e(old('stock', $product['stock'] ?? '0')) ?>"></div>
        <div class="field"><label for="min_stock">Mínimo para reponer <span class="req">*</span></label><input id="min_stock" name="min_stock" type="number" min="0" step="1" required value="<?= e(old('min_stock', $product['min_stock'] ?? '0')) ?>"></div>
    </div>
    <label class="panel-check"><input type="checkbox" name="active" value="1" <?= old('active', $product['active'] ?? true) ? 'checked' : '' ?>> Producto activo para ventas</label>
    <div class="panel-form-actions"><button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Agregar producto' ?></button><a class="btn btn-outline" href="<?= e(url('/panel/inventario')) ?>">Cancelar</a></div>
</form>
