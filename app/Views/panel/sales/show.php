<?php
/** @var array<string, mixed> $sale */
/** @var array<int, array<string, mixed>> $items */
$payments = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];
?>
<a class="panel-back" href="<?= e(url('/panel/ventas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Ventas</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Comprobante · <?= e(format_date($sale['created_at'], 'd/m/Y H:i')) ?></span><h1>Venta #<?= (int) $sale['id'] ?></h1><p><?= e($sale['patient_name'] ?? 'Venta general') ?><?= !empty($sale['tutor_name']) ? ' · ' . e($sale['tutor_name']) : '' ?></p></div><div class="panel-head-actions"><button class="btn btn-outline btn-sm" type="button" data-print><?= icon('printer', 'icon icon-sm') ?> Imprimir</button><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/ventas/nueva')) ?>">Nueva venta</a></div></div>

<article class="panel-card rx-sheet">
    <header class="rx-head"><div><strong><?= e((string) ($currentUser['institution'] ?? 'Clínica veterinaria')) ?></strong><span><?= e((string) ($currentUser['name'] ?? '')) ?></span></div><div class="rx-date"><?= e(format_date($sale['created_at'], 'd/m/Y H:i')) ?></div></header>
    <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td>$<?= number_format((float) $item['unit_price'], 2) ?></td><td>$<?= number_format((float) $item['subtotal'], 2) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="panel-sale-total">Total: <strong>$<?= number_format((float) $sale['total'], 2) ?></strong></div>
    <p class="panel-help">Pago: <?= e($payments[$sale['payment_method']] ?? $sale['payment_method']) ?><?= !empty($sale['notes']) ? ' · ' . e($sale['notes']) : '' ?></p>
    <footer class="rx-foot"><span>Gracias por su visita</span><strong><?= e((string) ($currentUser['name'] ?? '')) ?></strong><small><?= e((string) ($currentUser['license_number'] ?? '')) ?></small></footer>
</article>
