<?php
/** @var array<int, array<string, mixed>> $prescriptions */
/** @var array<int, array<string, mixed>> $patients */
/** @var int $patientId */
?>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Historial clínico</span><h1>Recetas</h1><p>Medicamentos e indicaciones emitidas por la clínica.</p></div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/recetas/nueva' . ($patientId > 0 ? '?patient_id=' . $patientId : ''))) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva receta</a></div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/recetas')) ?>">
        <select name="patient_id" aria-label="Paciente">
            <option value="">Todos los pacientes</option>
            <?php foreach ($patients as $patient): ?><option value="<?= (int) $patient['id'] ?>" <?= $patientId === (int) $patient['id'] ? 'selected' : '' ?>><?= e($patient['name']) ?> · <?= e($patient['tutor_name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
    </form>
</div>

<?php if ($prescriptions === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('file-text', 'icon') ?></span><p>No hay recetas registradas.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/recetas/nueva')) ?>">Crear receta</a></div></div>
<?php else: ?>
    <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Paciente</th><th>Tutor</th><th>Diagnóstico</th><th></th></tr></thead><tbody>
    <?php foreach ($prescriptions as $prescription): ?>
        <tr><td><?= e(format_date($prescription['created_at'], 'd/m/Y H:i')) ?></td><td><a href="<?= e(url('/panel/pacientes/' . (int) $prescription['patient_id'])) ?>"><?= e($prescription['patient_name']) ?></a><small><?= e(species_label((string) $prescription['species'])) ?></small></td><td><?= e($prescription['tutor_name']) ?></td><td><?= e(excerpt($prescription['diagnosis'], 80)) ?></td><td><a class="panel-text-link" href="<?= e(url('/panel/recetas/' . (int) $prescription['id'])) ?>">Abrir <?= icon('arrow-right', 'icon icon-xs') ?></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
