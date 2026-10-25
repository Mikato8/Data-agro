<?php
/** @var array<string, mixed> $plot */
/** @var array<int, array<string, mixed>> $visits */
/** @var array<int, array<string, mixed>> $recommendations */
/** @var array<int, array<string, mixed>> $sales */
?>
<a class="panel-back" href="<?= e(url('/panel/parcelas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Parcelas</a>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= e(crops_label((string) $plot['crop'])) ?> · <?= e($plot['variety'] ?? 'Sin variedad') ?></span>
        <h1><?= e($plot['name']) ?></h1>
        <p>Productor: <?= e($plot['producer_name']) ?><?= !empty($plot['producer_phone']) ? ' · ' . e($plot['producer_phone']) : '' ?></p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/visitas/nueva?plot_id=' . (int) $plot['id'])) ?>"><?= icon('calendar', 'icon icon-sm') ?> Nueva visita</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/parcelas/' . (int) $plot['id'] . '/editar')) ?>"><?= icon('edit', 'icon icon-sm') ?> Editar</a>
    </div>
</div>

<div class="panel-detail-grid">
    <div>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Expediente</span><h2>Datos de la parcela</h2></div></div>
            <dl class="panel-detail-list">
                <div><dt>Cultivo</dt><dd><?= e(crops_label((string) $plot['crop'])) ?></dd></div>
                <div><dt>Variedad</dt><dd><?= e($plot['variety'] ?? '—') ?></dd></div>
                <div><dt>Superficie</dt><dd><?= $plot['area_ha'] !== null ? e((string) $plot['area_ha']) . ' ha' : '—' ?></dd></div>
                <div><dt>Municipio</dt><dd><?= e($plot['municipality'] ?? '—') ?></dd></div>
                <div><dt>Riego</dt><dd><?= e(irrigation_label((string) ($plot['irrigation'] ?? 'temporal'))) ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= e($plot['producer_phone'] ?? '—') ?></dd></div>
            </dl>
            <?php if (!empty($plot['notes'])): ?><p class="panel-note"><?= nl2br(e($plot['notes'])) ?></p><?php endif; ?>
        </section>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Historial</span><h2>Visitas</h2></div><a class="panel-text-link" href="<?= e(url('/panel/visitas?plot_id=' . (int) $plot['id'])) ?>">Ver todas <?= icon('arrow-right', 'icon icon-xs') ?></a></div>
            <?php if ($visits === []): ?><div class="panel-empty"><p>Sin visitas registradas.</p></div><?php else: ?>
                <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Motivo</th><th>Estado</th></tr></thead><tbody>
                <?php foreach ($visits as $visit): ?><tr><td><?= e(format_date($visit['starts_at'], 'd/m/Y H:i')) ?></td><td><?= e($visit['reason']) ?></td><td><span class="badge badge-<?= $visit['status'] === 'realizada' ? 'success' : ($visit['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e(ucfirst($visit['status'])) ?></span></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Historial</span><h2>Recomendaciones</h2></div><a class="panel-text-link" href="<?= e(url('/panel/recomendaciones/nueva?plot_id=' . (int) $plot['id'])) ?>">Nueva recomendación <?= icon('arrow-right', 'icon icon-xs') ?></a></div>
            <?php if ($recommendations === []): ?><div class="panel-empty"><p>Sin recomendaciones registradas.</p></div><?php else: ?>
                <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Diagnóstico</th><th></th></tr></thead><tbody>
                <?php foreach ($recommendations as $recommendation): ?><tr><td><?= e(format_date($recommendation['created_at'], 'd/m/Y')) ?></td><td><?= e($recommendation['diagnosis']) ?></td><td><a class="panel-text-link" href="<?= e(url('/panel/recomendaciones/' . (int) $recommendation['id'])) ?>">Abrir</a></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
    </div>
    <aside>
        <div class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Productor</span><h2>Contacto</h2></div></div>
            <dl class="panel-detail-list"><div><dt>Nombre</dt><dd><?= e($plot['producer_name']) ?></dd></div><div><dt>Teléfono</dt><dd><?= e($plot['producer_phone'] ?? '—') ?></dd></div><div><dt>Correo</dt><dd><?= e($plot['producer_email'] ?? '—') ?></dd></div><div><dt>Registro</dt><dd><?= e(format_date($plot['created_at'], 'd/m/Y')) ?></dd></div></dl>
        </div>
        <div class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Cobros</span><h2>Ventas</h2></div><a class="panel-text-link" href="<?= e(url('/panel/ventas/nueva?plot_id=' . (int) $plot['id'])) ?>">Nueva venta</a></div>
            <?php if ($sales === []): ?><div class="panel-empty"><p>Sin ventas registradas.</p></div><?php else: ?>
                <?php foreach ($sales as $sale): ?><div class="panel-stock-row"><span><strong>$<?= number_format((float) $sale['total'], 2) ?></strong><small><?= e(format_date($sale['created_at'], 'd/m/Y H:i')) ?></small></span><a class="panel-text-link" href="<?= e(url('/panel/ventas/' . (int) $sale['id'])) ?>">Ver</a></div><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>
