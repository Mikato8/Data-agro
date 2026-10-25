<?php
/** @var array<int, array<string, mixed>> $plots */
/** @var array<int, array<string, mixed>> $inputs */
/** @var int $plotId */
$payments = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];
?>
<a class="panel-back" href="<?= e(url('/panel/ventas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Ventas</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Caja</span><h1>Nueva venta</h1><p>Selecciona insumos; las existencias se descuentan al guardar.</p></div></div>
<?php if ($inputs === []): ?>
    <div class="panel-card"><div class="panel-empty"><p>No hay insumos activos en el inventario.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/inventario/nuevo')) ?>">Agregar insumo</a></div></div>
<?php else: ?>
<form class="panel-card panel-form" method="post" action="<?= e(url('/panel/ventas')) ?>" data-sale-form>
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field"><label for="plot_id">Cultivo <span class="optional">(opcional)</span></label><select id="plot_id" name="plot_id"><option value="">Venta general</option><?php foreach ($plots as $plot): ?><option value="<?= (int) $plot['id'] ?>" <?= (int) old('plot_id', $plotId) === (int) $plot['id'] ? 'selected' : '' ?>><?= e($plot['name']) ?> · <?= e($plot['producer_name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="payment_method">Forma de pago</label><select id="payment_method" name="payment_method"><?php foreach ($payments as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
    </div>

    <div class="panel-table-wrap">
        <table class="panel-table panel-sale-table"><thead><tr><th>Insumo</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody>
        <?php for ($i = 0; $i < 8; $i++): ?>
            <tr data-sale-row>
                <td><select name="input_id[]" data-sale-input aria-label="Insumo"><option value="">Selecciona…</option><?php foreach ($inputs as $input): ?><option value="<?= (int) $input['id'] ?>" data-price="<?= e((string) $input['price']) ?>" data-stock="<?= (int) $input['stock'] ?>"><?= e($input['name']) ?> · <?= e($input['sku']) ?> (<?= (int) $input['stock'] ?> disponibles)</option><?php endforeach; ?></select></td>
                <td data-sale-price>$0.00</td>
                <td><input type="number" name="quantity[]" min="0" max="9999" step="1" value="0" data-sale-quantity aria-label="Cantidad" class="panel-qty"></td>
                <td data-sale-subtotal>$0.00</td>
            </tr>
        <?php endfor; ?>
        </tbody></table>
    </div>

    <div class="panel-sale-total">Total: <strong data-sale-total>$0.00</strong></div>
    <div class="field"><label for="notes">Notas de la venta</label><textarea id="notes" name="notes" rows="2" maxlength="1000" placeholder="Observaciones, recomendación, referencia…"><?= e(old('notes')) ?></textarea></div>
    <div class="panel-form-actions"><button class="btn btn-primary" type="submit">Registrar venta</button><a class="btn btn-outline" href="<?= e(url('/panel/ventas')) ?>">Cancelar</a></div>
</form>
<?php endif; ?>
