<?php
/** @var array<string, mixed> $case */
/** @var array<int, array<string, mixed>> $reviews */
/** @var array<int, array<string, mixed>> $comments */
?>
<div class="admin-page-head">
    <div>
        <a class="back-link" href="<?= e(url('/admin/casos')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver a casos</a>
        <div class="case-kicker">
            <span class="badge badge-<?= e(status_tone($case['status'])) ?>"><?= e(status_label($case['status'])) ?></span>
            <span class="badge badge-soft"><?= e($case['folio']) ?></span>
            <?php if ($case['is_demo']): ?><span class="badge badge-demo">Demostrativo</span><?php endif; ?>
        </div>
        <h1><?= e($case['title']) ?></h1>
        <p>Por <?= e($case['author_name']) ?> · <?= e($case['author_institution'] ?? '') ?> · <?= e($case['species_name']) ?><?php if (!empty($case['breed_name'])): ?> / <?= e($case['breed_name']) ?><?php endif; ?></p>
    </div>
    <div class="admin-page-actions">
        <?php if ($case['status'] === 'publicado'): ?>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/casos/' . $case['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external-link', 'icon icon-xs') ?> Ver público</a>
        <?php else: ?>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/casos/' . $case['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon icon-xs') ?> Vista previa</a>
        <?php endif; ?>
    </div>
</div>

<div class="admin-review-layout">
    <div class="admin-review-main">
        <section class="admin-card">
            <h2 class="admin-card-title">Resumen del caso</h2>
            <div class="review-summary">
                <div><span>Motivo de consulta</span><p><?= e($case['chief_complaint']) ?></p></div>
                <div><span>Diagnóstico final</span><p><strong><?= e($case['final_diagnosis'] ?: 'Sin diagnóstico') ?></strong></p></div>
                <div><span>Desenlace</span><p><?= e(outcome_label($case['outcome'])) ?></p></div>
                <div><span>Consentimiento</span><p><?= $case['owner_consent'] ? 'Sí' : 'No' ?> · Anonimizado: <?= $case['anonymized'] ? 'Sí' : 'No' ?></p></div>
            </div>
        </section>

        <?php foreach ([
            'Anamnesis' => $case['anamnesis'],
            'Exploración física' => $case['physical_exam'],
            'Estudios y diagnóstico' => $case['diagnostics'],
            'Diferenciales' => $case['differentials'],
            'Tratamiento y manejo' => $case['treatment'],
            'Discusión' => $case['discussion'],
            'Referencias' => $case['references_text'],
        ] as $heading => $text): ?>
            <?php if (trim((string) $text) !== ''): ?>
                <section class="admin-card">
                    <h2 class="admin-card-title"><?= e($heading) ?></h2>
                    <div class="prose prose-sm"><?= nl2br(e($text)) ?></div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($case['images'])): ?>
            <section class="admin-card">
                <h2 class="admin-card-title">Imágenes</h2>
                <div class="case-gallery case-gallery-sm">
                    <?php foreach ($case['images'] as $image): ?>
                        <figure>
                            <img src="<?= e(url('/uploads/cases/' . $image['path'])) ?>" alt="<?= e($image['alt_text'] ?: 'Imagen del caso') ?>" loading="lazy">
                            <?php if (!empty($image['caption'])): ?><figcaption><?= e($image['caption']) ?></figcaption><?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="admin-card">
            <h2 class="admin-card-title">Comentarios (<?= count($comments) ?>)</h2>
            <?php if ($comments === []): ?>
                <p class="muted">Sin comentarios.</p>
            <?php else: ?>
                <div class="admin-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="admin-list-row">
                            <span class="avatar avatar-xs"><?= e(initials((string) $comment['author_name'])) ?></span>
                            <span class="admin-list-main">
                                <strong><?= e($comment['author_name']) ?> <?php if ($comment['status'] === 'oculto'): ?><span class="badge badge-warning">Oculto</span><?php endif; ?></strong>
                                <small><?= e(excerpt($comment['body'], 180)) ?></small>
                            </span>
                            <?php if ($comment['status'] === 'visible'): ?>
                                <form method="post" action="<?= e(url('/admin/comentarios/' . $comment['id'] . '/ocultar')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs" type="submit">Ocultar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="admin-review-side">
        <section class="admin-card admin-review-form">
            <h2 class="admin-card-title"><?= icon('check-circle', 'icon icon-sm') ?> Decisión editorial</h2>
            <?php if ($case['status'] === 'publicado'): ?>
                <p class="muted">Este caso ya está publicado. Puedes registrar una nueva revisión o dejar comentarios al autor.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/admin/casos/' . $case['id'] . '/revision')) ?>">
                <?= csrf_field() ?>
                <div class="decision-options">
                    <label class="decision-option decision-approve">
                        <input type="radio" name="decision" value="aprobado" <?= $case['status'] === 'publicado' ? 'checked' : '' ?>>
                        <span><strong>Publicar</strong><small>El caso cumple los criterios editoriales.</small></span>
                    </label>
                    <label class="decision-option decision-changes">
                        <input type="radio" name="decision" value="cambios">
                        <span><strong>Solicitar cambios</strong><small>El autor debe ajustar antes de publicar.</small></span>
                    </label>
                    <label class="decision-option decision-reject">
                        <input type="radio" name="decision" value="rechazado">
                        <span><strong>Rechazar</strong><small>No cumple los criterios o no es publicable.</small></span>
                    </label>
                </div>
                <div class="field">
                    <label for="review-comments">Comentarios para el autor</label>
                    <textarea id="review-comments" name="comments" rows="5" maxlength="3000" placeholder="Sé específico y respetuoso. Explica qué falta o qué debe corregirse."></textarea>
                    <span class="field-help">Obligatorio para solicitar cambios o rechazar.</span>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Registrar decisión</button>
            </form>
        </section>

        <section class="admin-card">
            <h2 class="admin-card-title">Datos del envío</h2>
            <dl class="patient-data">
                <div><dt>Autor</dt><dd><?= e($case['author_name']) ?></dd></div>
                <div><dt>Correo</dt><dd><?= e($case['author_email'] ?? '') ?></dd></div>
                <div><dt>Cédula</dt><dd><?= e($case['author_license'] ?? '') ?></dd></div>
                <div><dt>Especie</dt><dd><?= e($case['species_name']) ?></dd></div>
                <div><dt>Estado</dt><dd><?= e($case['state_name'] ?? 'No especificado') ?></dd></div>
                <div><dt>Entorno</dt><dd><?= e(setting_label($case['setting'])) ?></dd></div>
                <div><dt>Creado</dt><dd><?= e(format_date($case['created_at'], 'd/m/Y H:i')) ?></dd></div>
                <div><dt>Actualizado</dt><dd><?= e(format_date($case['updated_at'], 'd/m/Y H:i')) ?></dd></div>
                <div><dt>Licencia</dt><dd><?= e($case['license']) ?></dd></div>
            </dl>
        </section>

        <section class="admin-card">
            <h2 class="admin-card-title">Historial de revisiones</h2>
            <?php if ($reviews === []): ?>
                <p class="muted">Sin revisiones previas.</p>
            <?php else: ?>
                <ol class="review-history">
                    <?php foreach ($reviews as $review): ?>
                        <li>
                            <span class="badge badge-<?= $review['decision'] === 'aprobado' ? 'success' : ($review['decision'] === 'rechazado' ? 'danger' : 'warning') ?>"><?= e(ucfirst($review['decision'])) ?></span>
                            <strong><?= e($review['reviewer_name']) ?></strong>
                            <time><?= e(format_date($review['created_at'], 'd/m/Y H:i')) ?></time>
                            <?php if (!empty($review['comments'])): ?><p><?= e($review['comments']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </aside>
</div>
