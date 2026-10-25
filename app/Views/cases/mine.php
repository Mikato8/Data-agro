<?php
/** @var array<int, array<string, mixed>> $cases */
/** @var array<string, int> $counts */
/** @var array<string, mixed> $user */
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Mis casos</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Mi actividad</span>
                <h1>Mis casos agronómicos</h1>
                <p>Gestiona tus borradores, revisa la retroalimentación editorial y da seguimiento a tus casos publicados.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= (int) $counts['total'] ?></span>
                <span>caso<?= $counts['total'] === 1 ? '' : 's' ?> en tu historial</span>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/casos/nuevo')) ?>"><?= icon('plus', 'icon icon-xs') ?> Nuevo caso</a>
            </div>
        </div>

        <div class="stat-strip">
            <div><strong><?= (int) $counts['publicado'] ?></strong><span>Publicados</span></div>
            <div><strong><?= (int) $counts['en_revision'] ?></strong><span>En revisión</span></div>
            <div><strong><?= (int) $counts['borrador'] ?></strong><span>Borradores</span></div>
            <div><strong><?= (int) $counts['cambios_solicitados'] ?></strong><span>Con cambios</span></div>
            <div><strong><?= (int) $counts['rechazado'] ?></strong><span>Rechazados</span></div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <?php if ($user['status'] !== 'verificado'): ?>
            <div class="notice notice-warning">
                <?= icon('clock', 'icon') ?>
                <div>
                    <strong>Tu cuenta está pendiente de verificación.</strong>
                    <p>Puedes crear y guardar borradores. Para enviarlos a revisión, un administrador debe verificar tu cédula profesional.</p>
                </div>
                <a class="btn btn-sm btn-outline" href="<?= e(url('/perfil')) ?>">Ver mi perfil</a>
            </div>
        <?php endif; ?>

        <?php if ($cases === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('file-text', 'icon') ?></span>
                <h2>Tu primer caso está por escribirse</h2>
                <p>Documenta una experiencia de campo, compártela con la comunidad y recibe retroalimentación de colegas.</p>
                <div class="empty-actions">
                    <a class="btn btn-primary" href="<?= e(url('/casos/nuevo')) ?>">Compartir mi primer caso</a>
                    <a class="btn btn-outline" href="<?= e(url('/etica')) ?>">Leer la guía editorial</a>
                </div>
            </div>
        <?php else: ?>
            <div class="my-cases">
                <?php foreach ($cases as $case): ?>
                    <?php
                    $complete = 0;
                    foreach (['final_diagnosis', 'management', 'lab_tests', 'discussion'] as $field) {
                        if (trim((string) ($case[$field] ?? '')) !== '') {
                            $complete++;
                        }
                    }
                    if (!empty($case['producer_consent'])) { $complete++; }
                    if (!empty($case['anonymized'])) { $complete++; }
                    $percent = (int) round(($complete / 6) * 100);
                    $editable = in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true) || in_array($user['role'], (array) config('app.moderators', []), true);
                    ?>
                    <article class="my-case-row">
                        <div class="my-case-crops crops-<?= e($case['crop_code'] ?? 'exotico') ?>">
                            <span><?= e($case['crop_emoji'] ?? '🐾') ?></span>
                        </div>
                        <div class="my-case-main">
                            <div class="my-case-top">
                                <span class="badge badge-<?= e(status_tone($case['status'])) ?>"><?= e(status_label($case['status'])) ?></span>
                                <?php if (!empty($case['is_demo'])): ?><span class="badge badge-demo">Demostrativo</span><?php endif; ?>
                                <span class="my-case-date">Actualizado <?= e(time_ago($case['updated_at'])) ?></span>
                            </div>
                            <h2>
                                <?php if ($case['status'] === 'publicado'): ?>
                                    <a href="<?= e(url('/casos/' . $case['slug'])) ?>"><?= e($case['title']) ?></a>
                                <?php else: ?>
                                    <?= e($case['title']) ?>
                                <?php endif; ?>
                            </h2>
                            <p><?= e($case['final_diagnosis'] ?: $case['chief_complaint']) ?></p>
                            <div class="my-case-meta">
                                <span><?= e($case['crop_name'] ?? '') ?></span>
                                <span><?= icon('message-circle', 'icon icon-xs') ?> <?= (int) ($case['comments_count'] ?? 0) ?> comentarios</span>
                                <?php if (($case['reviews_count'] ?? 0) > 0): ?><span><?= icon('check-circle', 'icon icon-xs') ?> <?= (int) $case['reviews_count'] ?> revisión(es)</span><?php endif; ?>
                            </div>
                            <?php if (in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true)): ?>
                                <div class="completeness">
                                    <div class="completeness-bar"><span style="width: <?= $percent ?>%"></span></div>
                                    <span><?= $percent ?>% completo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="my-case-actions">
                            <?php if ($case['status'] === 'publicado'): ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('/casos/' . $case['slug'])) ?>">Ver caso</a>
                            <?php else: ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('/casos/' . $case['slug'])) ?>?preview=1">Vista previa</a>
                            <?php endif; ?>
                            <?php if ($editable): ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(url('/casos/' . $case['slug'] . '/editar')) ?>"><?= icon('edit', 'icon icon-xs') ?> Editar</a>
                            <?php endif; ?>
                            <?php if (in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true) && $user['status'] === 'verificado'): ?>
                                <form method="post" action="<?= e(url('/casos/' . $case['slug'] . '/enviar')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-primary btn-sm" type="submit">Enviar a revisión</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
