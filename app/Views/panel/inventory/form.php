<?php
/** @var array<string, mixed>|null $input */
$editing = $input !== null;
$action = $editing ? '/panel/inventario/' . (int) $input['id'] : '/panel/inventario';
?>
<a class="panel-back" href="<?= e(url('/panel/inventario')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Inventario</a>
<div class="panel-page-head"><div><span class="panel-eyebrow"><?= $editing ? 'Editar insumo' : 'Nuevo insumo' ?></span><h1><?= $editing ? e($input['name']) : 'Agregar al inventario' ?></h1><p>Controla precio, existencias y mínimo de reposición.</p></div></div>

<form class="panel-card panel-form" method="post" action="<?= e(url($action)) ?>">
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field"><label for="name">Nombre <span class="req">*</span></label><input id="name" name="name" type="text" required maxlength="120" value="<?= e(old('name', $input['name'] ?? '')) ?>" placeholder="Ej. Urea 46%"></div>
        <div class="field"><label for="sku">SKU <span class="req">*</span></label><input id="sku" name="sku" type="text" required maxlength="40" value="<?= e(old('sku', $input['sku'] ?? '')) ?>" placeholder="FER-001"></div>
        <div class="field"><label for="category">Categoría</label><select id="category" name="category">
            <?php foreach (['fertilizante' => 'Fertilizante', 'plaguicida' => 'Plaguicida', 'semilla' => 'Semilla', 'herramienta' => 'Herramienta', 'otro' => 'Otro'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= old('category', $input['category'] ?? 'fertilizante') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="field"><label for="unit">Unidad</label><input id="unit" name="unit" type="text" maxlength="20" value="<?= e(old('unit', $input['unit'] ?? 'pieza')) ?>" placeholder="kg, L, bulto, pieza"></div>
        <div class="field"><label for="price">Precio (MXN) <span class="req">*</span></label><input id="price" name="price" type="number" min="0" step="0.01" required value="<?= e(old('price', $input['price'] ?? '0.00')) ?>"></div>
        <div class="field"><label for="stock">Existencias <span class="req">*</span></label><input id="stock" name="stock" type="number" min="0" step="1" required value="<?= e(old('stock', $input['stock'] ?? '0')) ?>"></div>
        <div class="field"><label for="min_stock">Mínimo para reponer <span class="req">*</span></label><input id="min_stock" name="min_stock" type="number" min="0" step="1" required value="<?= e(old('min_stock', $input['min_stock'] ?? '0')) ?>"></div>
    </div>
    <label class="panel-check"><input type="checkbox" name="active" value="1" <?= old('active', $input['active'] ?? true) ? 'checked' : '' ?>> Insumo activo para ventas</label>
    <div class="panel-form-actions"><button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Agregar insumo' ?></button><a class="btn btn-outline" href="<?= e(url('/panel/inventario')) ?>">Cancelar</a></div>
</form>
