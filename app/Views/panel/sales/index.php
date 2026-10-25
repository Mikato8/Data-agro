<?php
/** @var array<int, array<string, mixed>> $sales */
$payments = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];
?>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Caja</span><h1>Ventas</h1><p>Productos cobrados y movimientos de inventario.</p></div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/ventas/nueva')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva venta</a></div>
</div>
<?php if ($sales === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('trending-up', 'icon') ?></span><p>Aún no hay ventas registradas.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/ventas/nueva')) ?>">Registrar venta</a></div></div>
<?php else: ?>
<div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Venta</th><th>Fecha</th><th>Paciente</th><th>Pago</th><th>Artículos</th><th>Total</th><th></th></tr></thead><tbody>
<?php foreach ($sales as $sale): ?>
    <tr><td>#<?= (int) $sale['id'] ?></td><td><?= e(format_date($sale['created_at'], 'd/m/Y H:i')) ?></td><td><?= e($sale['patient_name'] ?? 'Venta general') ?></td><td><?= e($payments[$sale['payment_method']] ?? $sale['payment_method']) ?></td><td><?= (int) $sale['items_count'] ?></td><td><strong>$<?= number_format((float) $sale['total'], 2) ?></strong></td><td><a class="panel-text-link" href="<?= e(url('/panel/ventas/' . (int) $sale['id'])) ?>">Ver <?= icon('arrow-right', 'icon icon-xs') ?></a></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
