<?php
/** @var array<int, array<string, mixed>> $plots */
/** @var int $plotId */
$defaultStart = date('Y-m-d\TH:i', time() + 3600);
?>
<a class="panel-back" href="<?= e(url('/panel/visitas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Visitas</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Agenda</span><h1>Nueva visita</h1><p>Reserva una consulta para una parcela de tu parcela.</p></div></div>

<?php if ($plots === []): ?>
    <div class="panel-card"><div class="panel-empty"><p>Primero registra una parcela para poder agendar una visita.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>">Registrar parcela</a></div></div>
<?php else: ?>
    <form class="panel-card panel-form" method="post" action="<?= e(url('/panel/visitas')) ?>">
        <?= csrf_field() ?>
        <div class="panel-form-grid">
            <div class="field">
                <label for="plot_id">Cultivo <span class="req">*</span></label>
                <select id="plot_id" name="plot_id" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($plots as $plot): ?><option value="<?= (int) $plot['id'] ?>" <?= (int) old('plot_id', $plotId) === (int) $plot['id'] ? 'selected' : '' ?>><?= e($plot['name']) ?> · <?= e($plot['producer_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="starts_at">Fecha y hora <span class="req">*</span></label>
                <input id="starts_at" name="starts_at" type="datetime-local" required value="<?= e(old('starts_at', $defaultStart)) ?>">
            </div>
            <div class="field">
                <label for="duration_min">Duración (minutos)</label>
                <select id="duration_min" name="duration_min">
                    <?php foreach ([15, 30, 45, 60, 90] as $minutes): ?><option value="<?= $minutes ?>" <?= (int) old('duration_min', 30) === $minutes ? 'selected' : '' ?>><?= $minutes ?> min</option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="reason">Motivo de la visita <span class="req">*</span></label>
            <input id="reason" name="reason" type="text" required maxlength="180" value="<?= e(old('reason')) ?>" placeholder="Vacunación, revisión, cojera…">
        </div>
        <div class="field">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000" placeholder="Indicaciones para la recepción o el agrónomo…"><?= e(old('notes')) ?></textarea>
        </div>
        <div class="panel-form-actions"><button class="btn btn-primary" type="submit">Agendar visita</button><a class="btn btn-outline" href="<?= e(url('/panel/visitas')) ?>">Cancelar</a></div>
    </form>
<?php endif; ?>
