<?php
/** @var array<string, mixed> $patient */
/** @var array<int, array<string, mixed>> $appointments */
/** @var array<int, array<string, mixed>> $prescriptions */
/** @var array<int, array<string, mixed>> $sales */
?>
<a class="panel-back" href="<?= e(url('/panel/pacientes')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Pacientes</a>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= e(species_label((string) $patient['species'])) ?> · <?= e($patient['breed'] ?? 'Sin raza') ?></span>
        <h1><?= e($patient['name']) ?></h1>
        <p>Tutor: <?= e($patient['tutor_name']) ?><?= !empty($patient['tutor_phone']) ? ' · ' . e($patient['tutor_phone']) : '' ?></p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/citas/nueva?patient_id=' . (int) $patient['id'])) ?>"><?= icon('calendar', 'icon icon-sm') ?> Nueva cita</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/pacientes/' . (int) $patient['id'] . '/editar')) ?>"><?= icon('edit', 'icon icon-sm') ?> Editar</a>
    </div>
</div>

<div class="panel-detail-grid">
    <div>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Expediente</span><h2>Datos del paciente</h2></div></div>
            <dl class="panel-detail-list">
                <div><dt>Especie</dt><dd><?= e(species_label((string) $patient['species'])) ?></dd></div>
                <div><dt>Raza</dt><dd><?= e($patient['breed'] ?? '—') ?></dd></div>
                <div><dt>Sexo</dt><dd><?= e(ucfirst((string) ($patient['sex'] ?? 'desconocido'))) ?></dd></div>
                <div><dt>Nacimiento</dt><dd><?= e(format_date($patient['birth_date'], 'd/m/Y')) ?></dd></div>
                <div><dt>Peso</dt><dd><?= $patient['weight_kg'] !== null ? e((string) $patient['weight_kg']) . ' kg' : '—' ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= e($patient['tutor_phone'] ?? '—') ?></dd></div>
            </dl>
            <?php if (!empty($patient['notes'])): ?><p class="panel-note"><?= nl2br(e($patient['notes'])) ?></p><?php endif; ?>
        </section>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Historial</span><h2>Citas</h2></div><a class="panel-text-link" href="<?= e(url('/panel/citas?patient_id=' . (int) $patient['id'])) ?>">Ver todas <?= icon('arrow-right', 'icon icon-xs') ?></a></div>
            <?php if ($appointments === []): ?><div class="panel-empty"><p>Sin citas registradas.</p></div><?php else: ?>
                <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Motivo</th><th>Estado</th></tr></thead><tbody>
                <?php foreach ($appointments as $appointment): ?><tr><td><?= e(format_date($appointment['starts_at'], 'd/m/Y H:i')) ?></td><td><?= e($appointment['reason']) ?></td><td><span class="badge badge-<?= $appointment['status'] === 'atendida' ? 'success' : ($appointment['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e(ucfirst($appointment['status'])) ?></span></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
        <section class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Historial</span><h2>Recetas</h2></div><a class="panel-text-link" href="<?= e(url('/panel/recetas/nueva?patient_id=' . (int) $patient['id'])) ?>">Nueva receta <?= icon('arrow-right', 'icon icon-xs') ?></a></div>
            <?php if ($prescriptions === []): ?><div class="panel-empty"><p>Sin recetas registradas.</p></div><?php else: ?>
                <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Diagnóstico</th><th></th></tr></thead><tbody>
                <?php foreach ($prescriptions as $prescription): ?><tr><td><?= e(format_date($prescription['created_at'], 'd/m/Y')) ?></td><td><?= e($prescription['diagnosis']) ?></td><td><a class="panel-text-link" href="<?= e(url('/panel/recetas/' . (int) $prescription['id'])) ?>">Abrir</a></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
    </div>
    <aside>
        <div class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Tutor</span><h2>Contacto</h2></div></div>
            <dl class="panel-detail-list"><div><dt>Nombre</dt><dd><?= e($patient['tutor_name']) ?></dd></div><div><dt>Teléfono</dt><dd><?= e($patient['tutor_phone'] ?? '—') ?></dd></div><div><dt>Correo</dt><dd><?= e($patient['tutor_email'] ?? '—') ?></dd></div><div><dt>Registro</dt><dd><?= e(format_date($patient['created_at'], 'd/m/Y')) ?></dd></div></dl>
        </div>
        <div class="panel-card">
            <div class="panel-card-head"><div><span class="panel-card-kicker">Cobros</span><h2>Ventas</h2></div><a class="panel-text-link" href="<?= e(url('/panel/ventas/nueva?patient_id=' . (int) $patient['id'])) ?>">Nueva venta</a></div>
            <?php if ($sales === []): ?><div class="panel-empty"><p>Sin ventas registradas.</p></div><?php else: ?>
                <?php foreach ($sales as $sale): ?><div class="panel-stock-row"><span><strong>$<?= number_format((float) $sale['total'], 2) ?></strong><small><?= e(format_date($sale['created_at'], 'd/m/Y H:i')) ?></small></span><a class="panel-text-link" href="<?= e(url('/panel/ventas/' . (int) $sale['id'])) ?>">Ver</a></div><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>
