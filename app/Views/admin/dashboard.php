<?php
/** @var array<string, int> $stats */
/** @var array<int, array<string, mixed>> $pendingCases */
/** @var array<int, array<string, mixed>> $pendingUsers */
/** @var array<int, array<string, mixed>> $recentComments */
?>
<div class="admin-page-head">
    <div>
        <span class="eyebrow">Resumen editorial</span>
        <h1>Buen trabajo, <?= e(first_name((string) ($currentUser['name'] ?? 'colega'))) ?>.</h1>
        <p>Esto es lo que requiere atención hoy en el portal.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/casos?status=en_revision')) ?>">Ver cola de revisión</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/casos/nuevo')) ?>"><?= icon('plus', 'icon icon-xs') ?> Nuevo caso</a>
    </div>
</div>

<div class="admin-stat-grid">
    <a class="admin-stat" href="<?= e(url('/admin/casos?status=en_revision')) ?>">
        <span class="admin-stat-icon admin-stat-warning"><?= icon('clock', 'icon') ?></span>
        <strong><?= (int) $stats['pending'] ?></strong>
        <span>Casos en revisión</span>
    </a>
    <a class="admin-stat" href="<?= e(url('/admin/casos?status=publicado')) ?>">
        <span class="admin-stat-icon admin-stat-success"><?= icon('check-circle', 'icon') ?></span>
        <strong><?= (int) $stats['published'] ?></strong>
        <span>Casos publicados</span>
    </a>
    <a class="admin-stat" href="<?= e(url('/admin/usuarios?status=pendiente')) ?>">
        <span class="admin-stat-icon admin-stat-info"><?= icon('users', 'icon') ?></span>
        <strong><?= (int) $stats['pending_users'] ?></strong>
        <span>Usuarios por verificar</span>
    </a>
    <a class="admin-stat" href="<?= e(url('/admin/usuarios')) ?>">
        <span class="admin-stat-icon"><?= icon('user', 'icon') ?></span>
        <strong><?= (int) $stats['users'] ?></strong>
        <span>Usuarios registrados</span>
    </a>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2><?= icon('clock', 'icon icon-sm') ?> Cola de revisión</h2>
            <a class="text-link" href="<?= e(url('/admin/casos?status=en_revision')) ?>">Ver todo <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($pendingCases === []): ?>
            <div class="admin-empty"><?= icon('check-circle', 'icon') ?><p>No hay casos pendientes. La cola está al día.</p></div>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($pendingCases as $case): ?>
                    <a class="admin-list-row" href="<?= e(url('/admin/casos/' . $case['id'])) ?>">
                        <span class="admin-list-emoji"><?= e($case['crop_emoji'] ?? '🐾') ?></span>
                        <span class="admin-list-main">
                            <strong><?= e($case['title']) ?></strong>
                            <small><?= e($case['author_name']) ?> · <?= e(time_ago($case['updated_at'] ?? $case['created_at'] ?? null)) ?></small>
                        </span>
                        <span class="badge badge-warning">En revisión</span>
                        <?= icon('chevron-right', 'icon icon-sm') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2><?= icon('users', 'icon icon-sm') ?> Verificaciones pendientes</h2>
            <a class="text-link" href="<?= e(url('/admin/usuarios?status=pendiente')) ?>">Ver todo <?= icon('arrow-right', 'icon icon-xs') ?></a>
        </div>
        <?php if ($pendingUsers === []): ?>
            <div class="admin-empty"><?= icon('check-circle', 'icon') ?><p>No hay cuentas por verificar.</p></div>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($pendingUsers as $user): ?>
                    <a class="admin-list-row" href="<?= e(url('/admin/usuarios?q=' . rawurlencode((string) $user['email']))) ?>">
                        <span class="avatar avatar-xs"><?= e(initials((string) $user['name'])) ?></span>
                        <span class="admin-list-main">
                            <strong><?= e($user['name']) ?></strong>
                            <small><?= e($user['email']) ?> · <?= e($user['state_name'] ?? 'Sin estado') ?></small>
                        </span>
                        <span class="badge badge-warning">Pendiente</span>
                        <?= icon('chevron-right', 'icon icon-sm') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2><?= icon('message-circle', 'icon icon-sm') ?> Comentarios recientes</h2>
        </div>
        <?php if ($recentComments === []): ?>
            <div class="admin-empty"><?= icon('message-circle', 'icon') ?><p>Aún no hay comentarios.</p></div>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($recentComments as $comment): ?>
                    <div class="admin-list-row admin-comment-row">
                        <span class="avatar avatar-xs"><?= e(initials((string) $comment['author_name'])) ?></span>
                        <span class="admin-list-main">
                            <strong><?= e($comment['author_name']) ?></strong>
                            <small><?= e(excerpt($comment['body'], 90)) ?></small>
                            <small class="muted">En <a href="<?= e(url('/casos/' . $comment['case_slug'] . '#comentario-' . $comment['id'])) ?>"><?= e(excerpt($comment['case_title'], 60)) ?></a></small>
                        </span>
                        <span class="admin-list-time"><?= e(time_ago($comment['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card admin-card-dark">
        <div class="admin-card-head">
            <h2><?= icon('sparkles', 'icon icon-sm') ?> Criterios editoriales</h2>
        </div>
        <ul class="check-list check-list-light">
            <li><?= icon('check', 'icon icon-xs') ?> Verificar consentimiento y anonimización antes de publicar.</li>
            <li><?= icon('check', 'icon icon-xs') ?> Revisar que no haya dosis interpretables como prescripción.</li>
            <li><?= icon('check', 'icon icon-xs') ?> Confirmar que el diagnóstico final esté sustentado.</li>
            <li><?= icon('check', 'icon icon-xs') ?> Dejar retroalimentación específica y respetuosa.</li>
        </ul>
        <a class="btn btn-sand btn-sm" href="<?= e(url('/etica')) ?>">Ver guía completa</a>
    </section>
</div>
