<?php
/** @var array<int, array<string, mixed>> $plots */
/** @var array<int, array<string, mixed>> $visits */
/** @var int $plotId */
?>
<a class="panel-back" href="<?= e(url('/panel/recomendaciones')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Recomendaciones</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Documento técnico</span><h1>Nueva recomendación</h1><p>Registra el diagnóstico, los insumos y dosis y las indicaciones.</p></div></div>
<?php if ($plots === []): ?>
    <div class="panel-card"><div class="panel-empty"><p>Primero registra una parcela.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/nuevo')) ?>">Registrar parcela</a></div></div>
<?php else: ?>
<form class="panel-card panel-form" method="post" action="<?= e(url('/panel/recomendaciones')) ?>">
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
            <label for="visit_id">Visita relacionada <span class="optional">(opcional)</span></label>
            <select id="visit_id" name="visit_id">
                <option value="">Sin visita</option>
                <?php foreach ($visits as $visit): ?><option value="<?= (int) $visit['id'] ?>" <?= (int) old('visit_id') === (int) $visit['id'] ? 'selected' : '' ?>><?= e(format_date($visit['starts_at'], 'd/m/Y H:i')) ?> · <?= e($visit['plot_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="field"><label for="diagnosis">Diagnóstico <span class="req">*</span></label><textarea id="diagnosis" name="diagnosis" rows="3" required maxlength="1000" placeholder="Diagnóstico de campo y nivel de riesgo…"><?= e(old('diagnosis')) ?></textarea></div>
    <div class="field"><label for="inputs_prescribed">Insumos y dosis <span class="req">*</span></label><textarea id="inputs_prescribed" name="inputs_prescribed" rows="7" required maxlength="3000" placeholder="Un insumo por línea: nombre, dosis, vía y frecuencia."><?= e(old('inputs_prescribed')) ?></textarea><span class="panel-help">Ejemplo: fertilizante foliar, dosis por hectárea y momento de aplicación.</span></div>
    <div class="field"><label for="instructions">Indicaciones para el productor</label><textarea id="instructions" name="instructions" rows="4" maxlength="2000" placeholder="Monitoreo, momento de aplicación, equipo de protección, seguimiento…"><?= e(old('instructions')) ?></textarea></div>
    <div class="panel-form-actions"><button class="btn btn-primary" type="submit">Guardar recomendación</button><a class="btn btn-outline" href="<?= e(url('/panel/recomendaciones')) ?>">Cancelar</a></div>
</form>
<?php endif; ?>
