<?php
/** @var array<int, array<string, mixed>> $plots */
/** @var int $total */
/** @var string $q */
/** @var \App\Core\Paginator $paginator */
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow">Directorio de parcelas</span>
        <h1>Parcelas</h1>
        <p><?= number_format($total) ?> parcela<?= $total === 1 ? '' : 's' ?> registrada<?= $total === 1 ? '' : 's' ?>.</p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva parcela</a>
    </div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/parcelas')) ?>">
        <?= icon('search', 'icon icon-sm') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por parcela, productor, cultivo o teléfono…" aria-label="Buscar parcelas">
    </form>
</div>

<?php if ($plots === []): ?>
    <div class="panel-card">
        <div class="panel-empty">
            <?= icon('map-pin', 'icon') ?>
            <p><?= $q !== '' ? 'No encontramos parcelas con esa búsqueda.' : 'Aún no has registrado parcelas.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>">Registrar parcela</a>
        </div>
    </div>
<?php else: ?>
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead><tr><th>Parcela</th><th>Productor</th><th>Cultivo</th><th>Superficie</th><th>Contacto</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($plots as $plot): ?>
                <tr>
                    <td><a href="<?= e(url('/panel/parcelas/' . $plot['id'])) ?>"><?= e($plot['name']) ?></a><small><?= e($plot['variety'] ?? 'Sin variedad') ?> · <?= e(crops_label((string) $plot['crop'])) ?></small></td>
                    <td><?= e($plot['producer_name']) ?></td>
                    <td><?= e(crops_label((string) $plot['crop'])) ?></td>
                    <td><?= $plot['area_ha'] !== null ? e((string) $plot['area_ha']) . ' ha' : '—' ?></td>
                    <td><?= e($plot['producer_phone'] ?? '—') ?></td>
                    <td><?= e(format_date($plot['created_at'], 'd/m/Y')) ?></td>
                    <td><a class="panel-text-link" href="<?= e(url('/panel/parcelas/' . $plot['id'])) ?>">Abrir <?= icon('arrow-right', 'icon icon-xs') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
