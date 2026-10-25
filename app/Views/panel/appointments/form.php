<?php
/** @var array<int, array<string, mixed>> $patients */
/** @var int $patientId */
$defaultStart = date('Y-m-d\TH:i', time() + 3600);
?>
<a class="panel-back" href="<?= e(url('/panel/citas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Citas</a>
<div class="panel-page-head"><div><span class="panel-eyebrow">Agenda</span><h1>Nueva cita</h1><p>Reserva una consulta para un paciente de tu clínica.</p></div></div>

<?php if ($patients === []): ?>
    <div class="panel-card"><div class="panel-empty"><p>Primero registra un paciente para poder agendar una cita.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>">Registrar paciente</a></div></div>
<?php else: ?>
    <form class="panel-card panel-form" method="post" action="<?= e(url('/panel/citas')) ?>">
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
            <label for="reason">Motivo de la cita <span class="req">*</span></label>
            <input id="reason" name="reason" type="text" required maxlength="180" value="<?= e(old('reason')) ?>" placeholder="Vacunación, revisión, cojera…">
        </div>
        <div class="field">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000" placeholder="Indicaciones para la recepción o el veterinario…"><?= e(old('notes')) ?></textarea>
        </div>
        <div class="panel-form-actions"><button class="btn btn-primary" type="submit">Agendar cita</button><a class="btn btn-outline" href="<?= e(url('/panel/citas')) ?>">Cancelar</a></div>
    </form>
<?php endif; ?>
