<?php
/** @var array<int, array<string, mixed>> $patients */
/** @var array<int, array<string, mixed>> $appointments */
/** @var int $patientId */
?>
<a class="panel-back" href="<?= e(url('/panel/recetas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Recetas</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Documento clínico</span><h1>Nueva receta</h1><p>Registra el diagnóstico, los medicamentos y las indicaciones.</p></div></div>
<?php if ($patients === []): ?>
    <div class="panel-card"><div class="panel-empty"><p>Primero registra un paciente.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>">Registrar paciente</a></div></div>
<?php else: ?>
<form class="panel-card panel-form" method="post" action="<?= e(url('/panel/recetas')) ?>">
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field">
            <label for="patient_id">Paciente <span class="req">*</span></label>
            <select id="patient_id" name="patient_id" required>
                <option value="">Selecciona…</option>
                <?php foreach ($patients as $patient): ?><option value="<?= (int) $patient['id'] ?>" <?= (int) old('patient_id', $patientId) === (int) $patient['id'] ? 'selected' : '' ?>><?= e($patient['name']) ?> · <?= e($patient['tutor_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="appointment_id">Cita relacionada <span class="optional">(opcional)</span></label>
            <select id="appointment_id" name="appointment_id">
                <option value="">Sin cita</option>
                <?php foreach ($appointments as $appointment): ?><option value="<?= (int) $appointment['id'] ?>" <?= (int) old('appointment_id') === (int) $appointment['id'] ? 'selected' : '' ?>><?= e(format_date($appointment['starts_at'], 'd/m/Y H:i')) ?> · <?= e($appointment['patient_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="field"><label for="diagnosis">Diagnóstico <span class="req">*</span></label><textarea id="diagnosis" name="diagnosis" rows="3" required maxlength="1000" placeholder="Diagnóstico clínico…"><?= e(old('diagnosis')) ?></textarea></div>
    <div class="field"><label for="medications">Medicamentos <span class="req">*</span></label><textarea id="medications" name="medications" rows="7" required maxlength="3000" placeholder="Un medicamento por línea: nombre, dosis, vía y frecuencia."><?= e(old('medications')) ?></textarea><span class="panel-help">Ejemplo: Meloxicam 0.1 mg/kg VO cada 24 h por 5 días.</span></div>
    <div class="field"><label for="instructions">Indicaciones para el tutor</label><textarea id="instructions" name="instructions" rows="4" maxlength="2000" placeholder="Reposo, alimentación, signos de alarma…"><?= e(old('instructions')) ?></textarea></div>
    <div class="panel-form-actions"><button class="btn btn-primary" type="submit">Guardar receta</button><a class="btn btn-outline" href="<?= e(url('/panel/recetas')) ?>">Cancelar</a></div>
</form>
<?php endif; ?>
