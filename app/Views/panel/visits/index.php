<?php
/** @var array<int, array<string, mixed>> $visits */
/** @var string $date */
/** @var string $status */
/** @var int $plotId */
/** @var string $dateLabel */
$statusLabels = ['programada' => 'Programada', 'confirmada' => 'Confirmada', 'realizada' => 'Realizada', 'cancelada' => 'Cancelada'];
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow">Agenda<?= $dateLabel !== '' ? ' · ' . e($dateLabel) : '' ?></span>
        <h1>Visitas</h1>
        <p>Agenda, confirma y da seguimiento a las consultas de tu parcela.</p>
    </div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/visitas/nueva' . ($plotId > 0 ? '?plot_id=' . $plotId : ''))) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva visita</a></div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/visitas')) ?>">
        <input type="date" name="date" value="<?= e($date === 'all' ? '' : $date) ?>" aria-label="Fecha">
        <select name="status" aria-label="Estado">
            <option value="">Todos los estados</option>
            <?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
        <a class="panel-text-link" href="<?= e(url('/panel/visitas?date=all')) ?>">Ver todas</a>
    </form>
</div>

<?php if ($visits === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('calendar', 'icon') ?></span><p>No hay visitas con esos filtros.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/visitas/nueva')) ?>">Agendar visita</a></div></div>
<?php else: ?>
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead><tr><th>Fecha y hora</th><th>Cultivo</th><th>Productor</th><th>Motivo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($visits as $visit): ?>
                <tr>
                    <td><?= e(format_date($visit['starts_at'], 'd/m/Y')) ?><small><?= e(format_date($visit['starts_at'], 'H:i')) ?> · <?= (int) $visit['duration_min'] ?> min</small></td>
                    <td><a href="<?= e(url('/panel/parcelas/' . (int) $visit['plot_id'])) ?>"><?= e($visit['plot_name']) ?></a><small><?= e(crops_label((string) $visit['crop'])) ?></small></td>
                    <td><?= e($visit['producer_name']) ?><?php if (!empty($visit['producer_phone'])): ?><small><?= e($visit['producer_phone']) ?></small><?php endif; ?></td>
                    <td><?= e($visit['reason']) ?></td>
                    <td><span class="badge badge-<?= $visit['status'] === 'realizada' ? 'success' : ($visit['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e($statusLabels[$visit['status']] ?? $visit['status']) ?></span></td>
                    <td>
                        <div class="panel-row-actions">
                            <?php if ($visit['status'] !== 'realizada' && $visit['status'] !== 'cancelada'): ?>
                                <form method="post" action="<?= e(url('/panel/visitas/' . (int) $visit['id'] . '/estado')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="realizada"><input type="hidden" name="date" value="<?= e($date) ?>"><button class="btn btn-ghost btn-xs" type="submit">Realizada</button></form>
                                <form method="post" action="<?= e(url('/panel/visitas/' . (int) $visit['id'] . '/estado')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="cancelada"><input type="hidden" name="date" value="<?= e($date) ?>"><button class="btn btn-ghost btn-xs" type="submit">Cancelar</button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
