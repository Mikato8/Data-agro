<?php
/** @var array<string, mixed> $case */
/** @var array<int, array<string, mixed>> $comments */
/** @var array<int, array<string, mixed>> $similar */
/** @var bool $reacted */
/** @var bool $isPreview */
/** @var bool $canEdit */
/** @var bool $canModerate */
$caseUrl = url('/casos/' . $case['slug']);
$publishedYear = $case['published_at'] ? date('Y', strtotime((string) $case['published_at'])) : date('Y');
$citation = $case['author_name'] . ' (' . $publishedYear . '). ' . $case['title'] . '. Diagnóstico Veterinario. ' . $caseUrl;
?>
<section class="case-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/casos')) ?>">Casos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span><?= e(excerpt($case['title'], 60)) ?></span>
        </nav>

        <?php if ($isPreview): ?>
            <div class="notice notice-warning">
                <?= icon('eye', 'icon') ?>
                <div>
                    <strong>Vista previa editorial.</strong>
                    <p>Este caso no es público. Estado actual: <?= e(status_label($case['status'])) ?>. Solo el autor y el comité editorial pueden verlo.</p>
                </div>
                <?php if ($canModerate): ?>
                    <a class="btn btn-sm btn-dark" href="<?= e(url('/admin/casos/' . $case['id'])) ?>">Revisar en panel</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($case['is_demo']): ?>
            <div class="notice notice-info">
                <?= icon('info', 'icon') ?>
                <div>
                    <strong>Caso demostrativo.</strong>
                    <p>Este contenido fue elaborado para mostrar el flujo editorial del portal. No corresponde a un paciente real y los datos son ficticios.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="case-title-row">
            <div class="case-title-main">
                <div class="case-kicker">
                    <span class="species-pill species-<?= e($case['species_code']) ?>"><?= e($case['species_emoji'] ?? species_emoji($case['species_code'])) ?> <?= e($case['species_name']) ?></span>
                    <span class="badge badge-soft"><?= e(outcome_label($case['outcome'])) ?></span>
                    <?php if ($case['featured']): ?><span class="badge badge-accent"><?= icon('sparkles', 'icon icon-xs') ?> Destacado</span><?php endif; ?>
                </div>
                <h1><?= e($case['title']) ?></h1>
                <p class="case-deck"><?= e($case['final_diagnosis']) ?></p>
                <div class="case-byline">
                    <?= avatar_tag((string) $case['author_name'], $case['author_avatar_url'] ?? null, 'avatar-sm') ?>
                    <div>
                        <strong><?= e($case['author_name']) ?></strong>
                        <span><?= e($case['author_institution'] ?? 'Médico veterinario') ?><?php if ($case['author_status'] === 'verificado'): ?> · <span class="verified"><?= icon('check-circle', 'icon icon-xs') ?> Verificado</span><?php endif; ?></span>
                    </div>
                    <span class="byline-divider"></span>
                    <span class="byline-meta"><?= icon('calendar', 'icon icon-xs') ?> <?= e(format_date($case['published_at'], 'd M Y')) ?></span>
                    <span class="byline-meta"><?= icon('eye', 'icon icon-xs') ?> <?= number_format((int) $case['views']) ?> vistas</span>
                    <span class="byline-meta"><?= icon('message-circle', 'icon icon-xs') ?> <?= (int) $case['comments_count'] ?> comentarios</span>
                </div>
            </div>
            <div class="case-title-actions">
                <?php if ($canEdit): ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('/casos/' . $case['slug'] . '/editar')) ?>"><?= icon('edit', 'icon icon-xs') ?> Editar</a>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($citation) ?>"><?= icon('copy', 'icon icon-xs') ?> Citar</button>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/casos/' . $case['slug'] . '/pdf')) ?>"><?= icon('download', 'icon icon-xs') ?> PDF</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/casos/' . $case['slug'] . '/docx')) ?>"><?= icon('file-text', 'icon icon-xs') ?> Word</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/casos/' . $case['slug'] . '/ia-urgencia')) ?>"><?= icon('cpu', 'icon icon-xs') ?> IA</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container case-layout">
        <article class="case-article">
            <div class="case-summary">
                <div>
                    <span class="summary-label">Motivo de consulta</span>
                    <p><?= e($case['chief_complaint']) ?></p>
                </div>
                <div>
                    <span class="summary-label">Diagnóstico final</span>
                    <p class="summary-diagnosis"><?= e($case['final_diagnosis']) ?></p>
                    <?php if (!empty($case['icd11_code'])): ?><span class="summary-code">ICD-11: <?= e($case['icd11_code']) ?></span><?php endif; ?>
                </div>
            </div>

            <?php if (!empty($case['images'])): ?>
                <section class="case-section case-gallery-section">
                    <h2><?= icon('flask', 'icon icon-sm') ?> Imágenes y estudios</h2>
                    <div class="case-gallery">
                        <?php foreach ($case['images'] as $image): ?>
                            <figure>
                                <img src="<?= e(url('/uploads/cases/' . $image['path'])) ?>" alt="<?= e($image['alt_text'] ?: ($image['caption'] ?: 'Imagen del caso clínico')) ?>" loading="lazy">
                                <?php if (!empty($image['caption'])): ?><figcaption><?= e($image['caption']) ?></figcaption><?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                    <p class="case-disclaimer">Las imágenes se publican con consentimiento y fines educativos. No permiten identificar al tutor ni al paciente.</p>
                </section>
            <?php endif; ?>

            <section class="case-section">
                <h2><span class="section-index">01</span> Anamnesis</h2>
                <div class="prose"><?= nl2br(e($case['anamnesis'])) ?></div>
            </section>

            <section class="case-section">
                <h2><span class="section-index">02</span> Exploración física</h2>
                <div class="prose"><?= nl2br(e($case['physical_exam'])) ?></div>
            </section>

            <?php if (trim((string) $case['diagnostics']) !== ''): ?>
                <section class="case-section">
                    <h2><span class="section-index">03</span> Estudios y diagnóstico</h2>
                    <div class="prose"><?= nl2br(e($case['diagnostics'])) ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim((string) $case['differentials']) !== ''): ?>
                <section class="case-section">
                    <h2><span class="section-index">04</span> Diagnósticos diferenciales</h2>
                    <div class="prose"><?= nl2br(e($case['differentials'])) ?></div>
                </section>
            <?php endif; ?>

            <section class="case-section case-section-highlight">
                <h2><span class="section-index">05</span> Diagnóstico final</h2>
                <p class="final-diagnosis"><?= e($case['final_diagnosis']) ?></p>
                <?php if (!empty($case['icd11_code'])): ?><p class="case-muted">Código ICD-11: <?= e($case['icd11_code']) ?></p><?php endif; ?>
            </section>

            <?php if (trim((string) $case['treatment']) !== ''): ?>
                <section class="case-section">
                    <h2><span class="section-index">06</span> Tratamiento y manejo</h2>
                    <div class="prose"><?= nl2br(e($case['treatment'])) ?></div>
                </section>
            <?php endif; ?>

            <section class="case-section">
                <h2><span class="section-index">07</span> Desenlace</h2>
                <div class="outcome-panel outcome-<?= e($case['outcome']) ?>">
                    <?= icon($case['outcome'] === 'curado' ? 'check-circle' : ($case['outcome'] === 'fallecido' || $case['outcome'] === 'eutanasia' ? 'alert-triangle' : 'clock'), 'icon') ?>
                    <div>
                        <strong><?= e(outcome_label($case['outcome'])) ?></strong>
                        <p><?= e($case['outcome'] === 'curado' ? 'El paciente se recuperó o el problema se resolvió.' : ($case['outcome'] === 'en_tratamiento' ? 'El paciente continúa en seguimiento al momento de la publicación.' : 'El desenlace se documenta con fines educativos y de aprendizaje.')) ?></p>
                    </div>
                </div>
            </section>

            <?php if (trim((string) $case['discussion']) !== ''): ?>
                <section class="case-section">
                    <h2><span class="section-index">08</span> Discusión y aprendizaje</h2>
                    <div class="prose prose-discussion"><?= nl2br(e($case['discussion'])) ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim((string) $case['references_text']) !== ''): ?>
                <section class="case-section case-references">
                    <h2><span class="section-index">09</span> Referencias y lecturas</h2>
                    <div class="prose"><?= nl2br(e($case['references_text'])) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($case['tags'] !== []): ?>
                <div class="tag-row tag-row-case">
                    <span class="tag-row-label">Temas del caso:</span>
                    <?php foreach ($case['tags'] as $tag): ?>
                        <a class="tag" href="<?= e(url('/casos?tag=' . rawurlencode($tag))) ?>"><?= e($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="citation-box">
                <div>
                    <span class="summary-label">Cómo citar este caso</span>
                    <p><?= e($citation) ?></p>
                </div>
                <button class="btn btn-outline btn-sm" type="button" data-copy="<?= e($citation) ?>"><?= icon('copy', 'icon icon-xs') ?> Copiar</button>
            </div>

            <section class="comments" id="comentarios">
                <div class="comments-head">
                    <h2>Discusión clínica <span class="count-badge"><?= count($comments) ?></span></h2>
                    <p>Comentarios de colegas verificados. Mantén un tono respetuoso y basado en evidencia.</p>
                </div>

                <?php if ($comments === []): ?>
                    <div class="comments-empty">
                        <?= icon('message-circle', 'icon') ?>
                        <p>Aún no hay comentarios. Inicia la conversación con una observación clínica.</p>
                    </div>
                <?php else: ?>
                    <ol class="comment-list">
                        <?php foreach ($comments as $comment): ?>
                            <li class="comment <?= $comment['status'] === 'oculto' ? 'is-hidden' : '' ?>" id="comentario-<?= (int) $comment['id'] ?>">
                                <?= avatar_tag((string) $comment['author_name'], $comment['author_avatar_url'] ?? null, 'avatar-sm') ?>
                                <div class="comment-body">
                                    <div class="comment-head">
                                        <strong><?= e($comment['author_name']) ?></strong>
                                        <?php if ($comment['author_role'] !== 'veterinario'): ?><span class="badge badge-soft"><?= e((string) config('app.roles.' . $comment['author_role'], $comment['author_role'])) ?></span><?php endif; ?>
                                        <?php if ($comment['status'] === 'oculto'): ?><span class="badge badge-warning">Oculto</span><?php endif; ?>
                                        <time datetime="<?= e($comment['created_at']) ?>"><?= e(time_ago($comment['created_at'])) ?></time>
                                    </div>
                                    <p><?= nl2br(e($comment['body'])) ?></p>
                                    <?php if ($canModerate && $comment['status'] === 'visible'): ?>
                                        <form method="post" action="<?= e(url('/admin/comentarios/' . $comment['id'] . '/ocultar')) ?>" class="comment-moderation" data-confirm="¿Ocultar este comentario?">
                                            <?= csrf_field() ?>
                                            <button class="link-button" type="submit"><?= icon('eye', 'icon icon-xs') ?> Ocultar comentario</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if ($currentUser === null): ?>
                    <div class="comment-cta">
                        <p><strong>¿Quieres aportar a la discusión?</strong> Inicia sesión con tu cuenta verificada para comentar.</p>
                        <a class="btn btn-primary btn-sm" href="<?= e(url('/acceder')) ?>">Acceder</a>
                    </div>
                <?php elseif ($currentUser['status'] !== 'verificado'): ?>
                    <div class="comment-cta">
                        <p><strong>Tu cuenta está en revisión.</strong> Podrás comentar cuando el comité verifique tu cédula profesional.</p>
                        <a class="btn btn-outline btn-sm" href="<?= e(url('/perfil')) ?>">Ver mi perfil</a>
                    </div>
                <?php else: ?>
                    <form class="comment-form" method="post" action="<?= e(url('/casos/' . $case['slug'] . '/comentarios')) ?>">
                        <?= csrf_field() ?>
                        <label for="comment-body">Añadir un comentario clínico</label>
                        <textarea id="comment-body" name="body" rows="4" maxlength="3000" required placeholder="Comparte una observación, pregunta o experiencia relacionada…"></textarea>
                        <div class="comment-form-foot">
                            <span>Máximo 3,000 caracteres. No incluyas datos identificables.</span>
                            <button class="btn btn-primary btn-sm" type="submit">Publicar comentario</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </article>

        <aside class="case-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Ficha del paciente</h2>
                <dl class="patient-data">
                    <div><dt>Especie</dt><dd><?= e($case['species_name']) ?></dd></div>
                    <?php if (!empty($case['breed_name'])): ?><div><dt>Raza</dt><dd><?= e($case['breed_name']) ?></dd></div><?php endif; ?>
                    <div><dt>Sexo</dt><dd><?= e(sex_label($case['sex'])) ?></dd></div>
                    <div><dt>Edad</dt><dd><?= e(age_label($case['age_value'] !== null ? (int) $case['age_value'] : null, $case['age_unit'])) ?></dd></div>
                    <?php if ($case['weight_kg'] !== null): ?><div><dt>Peso</dt><dd><?= e($case['weight_kg']) ?> kg</dd></div><?php endif; ?>
                    <?php if (!empty($case['state_name'])): ?><div><dt>Estado</dt><dd><?= e($case['state_name']) ?></dd></div><?php endif; ?>
                    <div><dt>Entorno</dt><dd><?= e(setting_label($case['setting'])) ?></dd></div>
                    <div><dt>Folio</dt><dd><?= e($case['folio']) ?></dd></div>
                </dl>
            </div>

            <div class="sidebar-card author-card">
                <?= avatar_tag((string) $case['author_name'], $case['author_avatar_url'] ?? null, 'avatar-lg') ?>
                <h2><?= e($case['author_name']) ?></h2>
                <p class="author-inst"><?= e($case['author_institution'] ?? 'Médico veterinario') ?></p>
                <?php if ($case['author_status'] === 'verificado'): ?>
                    <span class="badge badge-success"><?= icon('check-circle', 'icon icon-xs') ?> Cédula verificada</span>
                <?php endif; ?>
                <?php if (!empty($case['author_bio'])): ?><p class="author-bio"><?= e(excerpt($case['author_bio'], 160)) ?></p><?php endif; ?>
            </div>

            <div class="sidebar-card utility-card">
                <h2 class="sidebar-title"><?= icon('heart-pulse', 'icon icon-sm') ?> ¿Te resultó útil?</h2>
                <p>Reconoce el trabajo del autor y ayuda a destacar casos con valor clínico.</p>
                <form method="post" action="<?= e(url('/casos/' . $case['slug'] . '/util')) ?>" data-reaction-form>
                    <?= csrf_field() ?>
                    <button class="btn <?= $reacted ? 'btn-primary' : 'btn-outline' ?> btn-block" type="submit" data-reaction-button>
                        <?= icon('heart-pulse', 'icon icon-sm') ?> <span data-reaction-label><?= $reacted ? 'Marcado como útil' : 'Me resultó útil' ?></span>
                        <span class="reaction-count" data-reaction-count><?= (int) $case['reactions_count'] ?></span>
                    </button>
                </form>
            </div>

            <div class="sidebar-card utility-card">
                <h2 class="sidebar-title"><?= icon('share', 'icon icon-sm') ?> Compartir</h2>
                <div class="share-grid">
                    <a class="share-link" href="https://wa.me/?text=<?= e(rawurlencode($case['title'] . ' ' . $caseUrl)) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                    <a class="share-link" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= e(rawurlencode($caseUrl)) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                    <a class="share-link" href="mailto:?subject=<?= e(rawurlencode($case['title'])) ?>&body=<?= e(rawurlencode($caseUrl)) ?>">Correo</a>
                    <button class="share-link" type="button" data-copy="<?= e($caseUrl) ?>">Copiar enlace</button>
                </div>
                <p class="license-note"><?= icon('shield', 'icon icon-xs') ?> Licencia: <?= e($case['license']) ?></p>
            </div>

            <?php if ($similar !== []): ?>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('book-open', 'icon icon-sm') ?> Casos similares</h2>
                    <div class="similar-list">
                        <?php foreach ($similar as $item): ?>
                            <a href="<?= e(url('/casos/' . $item['slug'])) ?>">
                                <span class="similar-species"><?= e($item['species_emoji'] ?? species_emoji($item['species_code'])) ?></span>
                                <span>
                                    <strong><?= e(excerpt($item['title'], 70)) ?></strong>
                                    <small><?= e($item['final_diagnosis']) ?> · <?= e($item['state_name'] ?? 'México') ?></small>
                                </span>
                                <?= icon('chevron-right', 'icon icon-xs') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
