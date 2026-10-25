<?php
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $stats */
/** @var array<int, array<string, mixed>> $appointments */
/** @var array<int, array<string, mixed>> $lowStock */
/** @var array<int, array<string, mixed>> $recentPatients */
/** @var \DateTimeImmutable $today */
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow"><?= e($today->format('d/m/Y')) ?></span>
        <h1>Hola, <?= e(first_name((string) $user['name'])) ?></h1>
        <p>Este es el pulso de tu clínica hoy.</p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/citas/nueva')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva cita</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>"><?= icon('paw', 'icon icon-sm') ?> Nuevo paciente</a>
    </div>
</div>

<div class="panel-stat-grid">
    <a class="panel-stat" href="<?= e(url('/panel/pacientes')) ?>">
        <span class="panel-stat-icon"><?= icon('paw', 'icon') ?></span>
        <strong><?= number_format((int) $stats['patients']) ?></strong>
        <span>Pacientes</span>
    </a>
    <a class="panel-stat" href="<?= e(url('/panel/citas')) ?>">
        <span class="panel-stat-icon"><?= icon('calendar', 'icon') ?></span>
        <strong><?= number_format((int) $stats['appointments_today']) ?></strong>
        <span>Citas hoy</span>
    </a>
    <a class="panel-stat <?= (int) $stats['low_stock'] > 0 ? 'is-warning' : '' ?>" href="<?= e(url('/panel/inventario?low=1')) ?>">
        <span class="panel-stat-icon"><?= icon('folder', 'icon') ?></span>
        <strong><?= number_format((int) $stats['low_stock']) ?></strong>
        <span>Stock bajo</span>
    </a>
    <a class="panel-stat" href="<?= e(url('/panel/ventas')) ?>">
        <span class="panel-stat-icon"><?= icon('trending-up', 'icon') ?></span>
        <strong>$<?= number_format((float) $stats['sales_today'], 2) ?></strong>
        <span>Ventas hoy</span>
    </a>
</div>

<div class="panel-dashboard-grid">
    <section class="panel-card">
        <div class="panel-card-head">
            <div><span class="panel-card-kicker">Agenda</span><h2>Citas de hoy</h2></div>
            <a class="panel-text-link" href="<?= e(url('/panel/citas')) ?>">Ver todas <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($appointments === []): ?>
            <div class="panel-empty">
                <?= icon('calendar', 'icon') ?>
                <p>No hay citas para hoy.</p>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/panel/citas/nueva')) ?>">Agendar una cita</a>
            </div>
        <?php else: ?>
            <div class="panel-appointment-list">
                <?php foreach ($appointments as $appointment): ?>
                    <a class="panel-appointment" href="<?= e(url('/panel/citas?date=' . format_date($appointment['starts_at'], 'Y-m-d'))) ?>">
                        <span class="panel-appointment-time"><?= e(format_date($appointment['starts_at'], 'H:i')) ?></span>
                        <span class="panel-appointment-main">
                            <strong><?= e($appointment['patient_name']) ?></strong>
                            <small><?= e($appointment['tutor_name']) ?> · <?= e($appointment['reason']) ?></small>
                        </span>
                        <span class="badge badge-<?= $appointment['status'] === 'atendida' ? 'success' : ($appointment['status'] === 'cancelada' ? 'danger' : 'warning') ?>"><?= e(ucfirst($appointment['status'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel-card">
        <div class="panel-card-head">
            <div><span class="panel-card-kicker">Inventario</span><h2>Reponer pronto</h2></div>
            <a class="panel-text-link" href="<?= e(url('/panel/inventario')) ?>">Abrir inventario <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($lowStock === []): ?>
            <div class="panel-empty"><span class="panel-empty-icon"><?= icon('check-circle', 'icon') ?></span><p>Todo el inventario está por encima del mínimo.</p></div>
        <?php else: ?>
            <div class="panel-stock-list">
                <?php foreach ($lowStock as $product): ?>
                    <div class="panel-stock-row">
                        <span><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?></small></span>
                        <span class="panel-stock-count <?= (int) $product['stock'] === 0 ? 'is-empty' : '' ?>"><?= (int) $product['stock'] ?> / <?= (int) $product['min_stock'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel-card">
    <div class="panel-card-head">
        <div><span class="panel-card-kicker">Pacientes</span><h2>Ingresos recientes</h2></div>
        <a class="panel-text-link" href="<?= e(url('/panel/pacientes')) ?>">Ver directorio <?= icon('arrow-right', 'icon icon-xs') ?></a>
    </div>
    <?php if ($recentPatients === []): ?>
        <div class="panel-empty">
            <?= icon('paw', 'icon') ?>
            <p>Aún no has registrado pacientes.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>">Registrar el primero</a>
        </div>
    <?php else: ?>
        <div class="panel-patient-grid">
            <?php foreach ($recentPatients as $patient): ?>
                <a class="panel-patient-card" href="<?= e(url('/panel/pacientes/' . $patient['id'])) ?>">
                    <span class="panel-patient-avatar"><?= icon(species_icon((string) $patient['species']), 'icon') ?></span>
                    <span>
                        <strong><?= e($patient['name']) ?></strong>
                        <small><?= e($patient['tutor_name']) ?> · <?= e(species_label((string) $patient['species'])) ?></small>
                    </span>
                    <?= icon('chevron-right', 'icon icon-xs') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
