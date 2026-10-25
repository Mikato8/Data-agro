<?php
/** @var array<string, mixed>|null $plot */
/** @var array<int, array<string, mixed>> $cropsList */
$editing = $plot !== null;
$action = $editing ? '/panel/parcelas/' . (int) $plot['id'] : '/panel/parcelas';
?>
<a class="panel-back" href="<?= e($editing ? url('/panel/parcelas/' . (int) $plot['id']) : url('/panel/parcelas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver</a>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= $editing ? 'Ficha de parcela' : 'Nuevo registro' ?></span>
        <h1><?= $editing ? 'Editar parcela' : 'Registrar parcela' ?></h1>
        <p>Los datos del productor son privados: sólo se usan para tu seguimiento técnico.</p>
    </div>
</div>

<form class="panel-card panel-form" method="post" action="<?= e(url($action)) ?>">
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field">
            <label for="name">Nombre de la parcela <span class="req">*</span></label>
            <input id="name" name="name" type="text" required maxlength="80" value="<?= e(old('name', $plot['name'] ?? '')) ?>" placeholder="Ej. Parcela El Roble">
        </div>
        <div class="field">
            <label for="crop">Cultivo <span class="req">*</span></label>
            <select id="crop" name="crop" required>
                <?php foreach ($cropsList as $crop): ?>
                    <option value="<?= e((string) $crop['code']) ?>" <?= old('crop', $plot['crop'] ?? 'maiz') === $crop['code'] ? 'selected' : '' ?>><?= e($crop['emoji'] . ' ' . $crop['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="variety">Variedad</label>
            <input id="variety" name="variety" type="text" maxlength="80" value="<?= e(old('variety', $plot['variety'] ?? '')) ?>" placeholder="Ej. Híbrido comercial">
        </div>
        <div class="field">
            <label for="area_ha">Superficie (ha)</label>
            <input id="area_ha" name="area_ha" type="number" min="0.01" step="0.01" value="<?= e(old('area_ha', $plot['area_ha'] ?? '')) ?>" placeholder="Ej. 3.5">
        </div>
        <div class="field">
            <label for="municipality">Municipio</label>
            <input id="municipality" name="municipality" type="text" maxlength="120" value="<?= e(old('municipality', $plot['municipality'] ?? '')) ?>" placeholder="Ej. Culiacán">
        </div>
        <div class="field">
            <label for="state_code">Estado</label>
            <select id="state_code" name="state_code">
                <option value="">No especificado</option>
                <?php foreach (\App\Core\Database::select('select code, name from states order by name') as $state): ?>
                    <option value="<?= e((string) $state['code']) ?>" <?= old('state_code', $plot['state_code'] ?? '') === $state['code'] ? 'selected' : '' ?>><?= e((string) $state['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="irrigation">Riego</label>
            <select id="irrigation" name="irrigation">
                <?php foreach (['temporal' => 'Temporal', 'gravedad' => 'Gravedad', 'goteo' => 'Goteo', 'aspersion' => 'Aspersión', 'microaspersion' => 'Microaspersión', 'otro' => 'Otro'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('irrigation', $plot['irrigation'] ?? 'temporal') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="soil">Tipo de suelo</label>
            <input id="soil" name="soil" type="text" maxlength="120" value="<?= e(old('soil', $plot['soil'] ?? '')) ?>" placeholder="Ej. Vertisol, franco-arcilloso">
        </div>
    </div>

    <h2 class="panel-form-title">Datos del productor</h2>
    <div class="panel-form-grid">
        <div class="field">
            <label for="producer_name">Nombre completo <span class="req">*</span></label>
            <input id="producer_name" name="producer_name" type="text" required maxlength="120" value="<?= e(old('producer_name', $plot['producer_name'] ?? '')) ?>" placeholder="Nombre del productor">
        </div>
        <div class="field">
            <label for="producer_phone">Teléfono</label>
            <input id="producer_phone" name="producer_phone" type="tel" maxlength="30" value="<?= e(old('producer_phone', $plot['producer_phone'] ?? '')) ?>" placeholder="55 1234 5678">
        </div>
        <div class="field">
            <label for="producer_email">Correo</label>
            <input id="producer_email" name="producer_email" type="email" maxlength="180" value="<?= e(old('producer_email', $plot['producer_email'] ?? '')) ?>" placeholder="productor@correo.mx">
        </div>
    </div>

    <div class="field">
        <label for="notes">Notas de seguimiento</label>
        <textarea id="notes" name="notes" rows="4" maxlength="3000" placeholder="Antecedentes de la parcela, acuerdos, observaciones…"><?= e(old('notes', $plot['notes'] ?? '')) ?></textarea>
    </div>

    <div class="panel-form-actions">
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar parcela' ?></button>
        <a class="btn btn-outline" href="<?= e($editing ? url('/panel/parcelas/' . (int) $plot['id']) : url('/panel/parcelas')) ?>">Cancelar</a>
    </div>
</form>
