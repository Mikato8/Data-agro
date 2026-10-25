<?php
/** @var array<string, mixed>|null $patient */
/** @var array<int, string> $speciesList */
$editing = $patient !== null;
$action = $editing ? '/panel/pacientes/' . (int) $patient['id'] : '/panel/pacientes';
?>
<a class="panel-back" href="<?= e($editing ? url('/panel/pacientes/' . (int) $patient['id']) : url('/panel/pacientes')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver</a>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= $editing ? 'Expediente' : 'Nuevo ingreso' ?></span>
        <h1><?= $editing ? 'Editar paciente' : 'Registrar paciente' ?></h1>
        <p>Los datos del tutor no se muestran en el portal público.</p>
    </div>
</div>

<form class="panel-card panel-form" method="post" action="<?= e(url($action)) ?>">
    <?= csrf_field() ?>
    <div class="panel-form-grid">
        <div class="field">
            <label for="name">Nombre del paciente <span class="req">*</span></label>
            <input id="name" name="name" type="text" required maxlength="80" value="<?= e(old('name', $patient['name'] ?? '')) ?>" placeholder="Ej. Luna">
        </div>
        <div class="field">
            <label for="species">Especie <span class="req">*</span></label>
            <select id="species" name="species" required>
                <?php foreach ($speciesList as $species): ?>
                    <option value="<?= e($species) ?>" <?= old('species', $patient['species'] ?? 'canino') === $species ? 'selected' : '' ?>><?= e(species_label($species)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="breed">Raza</label>
            <input id="breed" name="breed" type="text" maxlength="80" value="<?= e(old('breed', $patient['breed'] ?? '')) ?>" placeholder="Mestizo, Labrador…">
        </div>
        <div class="field">
            <label for="sex">Sexo</label>
            <select id="sex" name="sex">
                <?php foreach (['macho' => 'Macho', 'hembra' => 'Hembra', 'desconocido' => 'Desconocido'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('sex', $patient['sex'] ?? 'desconocido') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="birth_date">Fecha de nacimiento</label>
            <input id="birth_date" name="birth_date" type="date" value="<?= e(old('birth_date', $patient['birth_date'] ?? '')) ?>">
        </div>
        <div class="field">
            <label for="weight_kg">Peso (kg)</label>
            <input id="weight_kg" name="weight_kg" type="number" min="0.01" step="0.01" value="<?= e(old('weight_kg', $patient['weight_kg'] ?? '')) ?>" placeholder="Ej. 8.5">
        </div>
    </div>

    <h2 class="panel-form-title">Datos del tutor</h2>
    <div class="panel-form-grid">
        <div class="field">
            <label for="tutor_name">Nombre completo <span class="req">*</span></label>
            <input id="tutor_name" name="tutor_name" type="text" required maxlength="120" value="<?= e(old('tutor_name', $patient['tutor_name'] ?? '')) ?>" placeholder="Nombre del propietario">
        </div>
        <div class="field">
            <label for="tutor_phone">Teléfono</label>
            <input id="tutor_phone" name="tutor_phone" type="tel" maxlength="30" value="<?= e(old('tutor_phone', $patient['tutor_phone'] ?? '')) ?>" placeholder="55 1234 5678">
        </div>
        <div class="field">
            <label for="tutor_email">Correo</label>
            <input id="tutor_email" name="tutor_email" type="email" maxlength="180" value="<?= e(old('tutor_email', $patient['tutor_email'] ?? '')) ?>" placeholder="tutor@correo.mx">
        </div>
    </div>

    <div class="field">
        <label for="notes">Notas clínicas</label>
        <textarea id="notes" name="notes" rows="4" maxlength="3000" placeholder="Alergias, antecedentes, observaciones…"><?= e(old('notes', $patient['notes'] ?? '')) ?></textarea>
    </div>

    <div class="panel-form-actions">
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar paciente' ?></button>
        <a class="btn btn-outline" href="<?= e($editing ? url('/panel/pacientes/' . (int) $patient['id']) : url('/panel/pacientes')) ?>">Cancelar</a>
    </div>
</form>
