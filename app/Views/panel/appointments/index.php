<?php
/** @var array<int, array<string, mixed>> $appointments */
/** @var string $date */
/** @var string $status */
/** @var int $patientId */
/** @var string $dateLabel */
$statusLabels = ['programada' => 'Programada', 'confirmada' => 'Confirmada', 'atendida' => 'Atendida', 'cancelada' => 'Cancelada'];
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow">Agenda<?= $dateLabel !== '' ? ' · ' . e($dateLabel) : '' ?></span>
        <h1>Citas</h1>
        <p>Agenda, confirma y da seguimiento a las consultas de tu clínica.</p>
    </div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/citas/nueva' . ($patientId > 0 ? '?patient_id=' . $patientId : ''))) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva cita</a></div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/citas')) ?>">
        <input type="date" name="date" value="<?= e($date === 'all' ? '' : $date) ?>" aria-label="Fecha">
        <select name="status" aria-label="Estado">
            <option value="">Todos los estados</option>
            <?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
        <a class="panel-text-link" href="<?= e(url('/panel/citas?date=all')) ?>">Ver todas</a>
    </form>
</div>

<?php if ($appointments === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('calendar', 'icon') ?></span><p>No hay citas con esos filtros.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/citas/nueva')) ?>">Agendar cita</a></div></div>
<?php else: ?>
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead><tr><th>Fecha y hora</th><th>Paciente</th><th>Tutor</th><th>Motivo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= e(format_date($appointment['starts_at'], 'd/m/Y')) ?><small><?= e(format_date($appointment['starts_at'], 'H:i')) ?> · <?= (int) $appointment['duration_min'] ?> min</small></td>
                    <td><a href="<?= e(url('/panel/pacientes/' . (int) $appointment['patient_id'])) ?>"><?= e($appointment['patient_name']) ?></a><small><?= e(species_label((string) $appointment['species'])) ?></small></td>
                    <td><?= e($appointment['tutor_name']) ?><?php if (!empty($appointment['tutor_phone'])): ?><small><?= e($appointment['tutor_phone']) ?></small><?php endif; ?></td>
                    <td><?= e($appointment['reason']) ?></td>
                    <td><span class="badge badge-<?= $appointment['status'] === 'atendida' ? 'success' : ($appointment['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e($statusLabels[$appointment['status']] ?? $appointment['status']) ?></span></td>
                    <td>
                        <div class="panel-row-actions">
                            <?php if ($appointment['status'] !== 'atendida' && $appointment['status'] !== 'cancelada'): ?>
                                <form method="post" action="<?= e(url('/panel/citas/' . (int) $appointment['id'] . '/estado')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="atendida"><input type="hidden" name="date" value="<?= e($date) ?>"><button class="btn btn-ghost btn-xs" type="submit">Atendida</button></form>
                                <form method="post" action="<?= e(url('/panel/citas/' . (int) $appointment['id'] . '/estado')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="cancelada"><input type="hidden" name="date" value="<?= e($date) ?>"><button class="btn btn-ghost btn-xs" type="submit">Cancelar</button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
