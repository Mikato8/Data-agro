<?php
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $stats */
/** @var array<int, array<string, mixed>> $visits */
/** @var array<int, array<string, mixed>> $lowStock */
/** @var array<int, array<string, mixed>> $recentPlots */
/** @var \DateTimeImmutable $today */
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= e($today->format('d/m/Y')) ?></span>
        <h1>Hola, <?= e(first_name((string) $user['name'])) ?></h1>
        <p>Este es el pulso de tu parcela hoy.</p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/visitas/nueva')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva visita</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>"><?= icon('map-pin', 'icon icon-sm') ?> Nueva parcela</a>
    </div>
</div>

<div class="panel-stat-grid">
    <a class="panel-stat" href="<?= e(url('/panel/parcelas')) ?>">
        <span class="panel-stat-icon"><?= icon('map-pin', 'icon') ?></span>
        <strong><?= number_format((int) $stats['plots']) ?></strong>
        <span>Parcelas</span>
    </a>
    <a class="panel-stat" href="<?= e(url('/panel/visitas')) ?>">
        <span class="panel-stat-icon"><?= icon('calendar', 'icon') ?></span>
        <strong><?= number_format((int) $stats['visits_today']) ?></strong>
        <span>Visitas hoy</span>
    </a>
    <a class="panel-stat <?= (int) $stats['low_stock'] > 0 ? 'is-warning' : '' ?>" href="<?= e(url('/panel/inventario?low=1')) ?>">
        <span class="panel-stat-icon"><?= icon('folder', 'icon') ?></span>
        <strong><?= number_format((int) $stats['low_stock']) ?></strong>
        <span>Stock bajo</span>
    </a>
    <a class="panel-stat" href="<?= e(url('/panel/ventas')) ?>">
        <span class="panel-stat-icon"><?= icon('trending-up', 'icon') ?></span>
        <strong>$<?= number_format((float) $stats['sales_today'], 2) ?></strong>
        <span>Ventas hoy</span>
    </a>
</div>

<div class="panel-dashboard-grid">
    <section class="panel-card">
        <div class="panel-card-head">
            <div><span class="panel-card-kicker">Agenda</span><h2>Visitas de hoy</h2></div>
            <a class="panel-text-link" href="<?= e(url('/panel/visitas')) ?>">Ver todas <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($visits === []): ?>
            <div class="panel-empty">
                <?= icon('calendar', 'icon') ?>
                <p>No hay visitas para hoy.</p>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/visitas/nueva')) ?>">Agendar una visita</a>
            </div>
        <?php else: ?>
            <div class="panel-visit-list">
                <?php foreach ($visits as $visit): ?>
                    <a class="panel-visit" href="<?= e(url('/panel/visitas?date=' . format_date($visit['starts_at'], 'Y-m-d'))) ?>">
                        <span class="panel-visit-time"><?= e(format_date($visit['starts_at'], 'H:i')) ?></span>
                        <span class="panel-visit-main">
                            <strong><?= e($visit['plot_name']) ?></strong>
                            <small><?= e($visit['producer_name']) ?> · <?= e($visit['reason']) ?></small>
                        </span>
                        <span class="badge badge-<?= $visit['status'] === 'realizada' ? 'success' : ($visit['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e(ucfirst($visit['status'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel-card">
        <div class="panel-card-head">
            <div><span class="panel-card-kicker">Inventario</span><h2>Reponer pronto</h2></div>
            <a class="panel-text-link" href="<?= e(url('/panel/inventario')) ?>">Abrir inventario <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($lowStock === []): ?>
            <div class="panel-empty"><span class="panel-empty-icon"><?= icon('check-circle', 'icon') ?></span><p>Todo el inventario está por encima del mínimo.</p></div>
        <?php else: ?>
            <div class="panel-stock-list">
                <?php foreach ($lowStock as $input): ?>
                    <div class="panel-stock-row">
                        <span><strong><?= e($input['name']) ?></strong><small><?= e($input['sku']) ?></small></span>
                        <span class="panel-stock-count <?= (int) $input['stock'] === 0 ? 'is-empty' : '' ?>"><?= (int) $input['stock'] ?> / <?= (int) $input['min_stock'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel-card">
    <div class="panel-card-head">
        <div><span class="panel-card-kicker">Parcelas</span><h2>Registros recientes</h2></div>
        <a class="panel-text-link" href="<?= e(url('/panel/parcelas')) ?>">Ver directorio <?= icon('arrow-right', 'icon icon-xs') ?></a>
    </div>
    <?php if ($recentPlots === []): ?>
        <div class="panel-empty">
            <?= icon('map-pin', 'icon') ?>
            <p>Aún no has registrado parcelas.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>">Registrar el primero</a>
        </div>
    <?php else: ?>
        <div class="panel-plot-grid">
            <?php foreach ($recentPlots as $plot): ?>
                <a class="panel-plot-card" href="<?= e(url('/panel/parcelas/' . $plot['id'])) ?>">
                    <span class="panel-plot-avatar"><?= icon(crop_icon((string) $plot['crop']), 'icon') ?></span>
                    <span>
                        <strong><?= e($plot['name']) ?></strong>
                        <small><?= e($plot['producer_name']) ?> · <?= e(crops_label((string) $plot['crop'])) ?></small>
                    </span>
                    <?= icon('chevron-right', 'icon icon-xs') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
