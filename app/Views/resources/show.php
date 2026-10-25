<?php
/** @var array<string, mixed> $resource */
/** @var array<int, array<string, mixed>> $related */
/** @var bool $isFavorite */
$accessTone = $resource['access'] === 'abierto' ? 'success' : 'warning';
$isRequest = $resource['access'] === 'solicitud';
$reviewed = !empty($resource['reviewed_at']) ? format_date((string) $resource['reviewed_at'], 'd M Y') : null;
$citation = trim(($resource['organization'] ?: 'AgroDiagnóstico') . '. ' . $resource['title'] . '. ' . $resource['url']);
$requestTemplate = "Asunto: Solicitud de acceso a " . $resource['title'] . "\n\n"
    . "Estimado equipo de " . ($resource['organization'] ?: 'la institución') . ":\n\n"
    . "Mi nombre es [nombre completo] y soy ingeniero agrónomo con cédula profesional [número]. "
    . "Me interesa utilizar " . $resource['title'] . " para [objetivo: docencia, investigación, tesis, análisis de casos].\n\n"
    . "¿Podrían indicarme el proceso de solicitud, los requisitos de uso y las condiciones de licencia? "
    . "Quedo atento a su respuesta.\n\n"
    . "Saludos cordiales,\n[nombre]\n[correo]\n[institución]";
?>
<section class="page-hero page-hero-compact resource-detail-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span><?= e($resource['title']) ?></span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <div class="case-kicker">
                    <span class="badge badge-soft"><?= e(category_label($resource['category'])) ?></span>
                    <span class="badge badge-<?= e($accessTone) ?>"><?= e(access_label($resource['access'])) ?></span>
                    <span class="badge badge-success"><?= icon('book-open', 'icon icon-xs') ?> Guía en español</span>
                </div>
                <h1><?= e($resource['title']) ?></h1>
                <p class="page-hero-lead"><?= e($resource['what_for'] ?: $resource['description']) ?></p>
                <p class="resource-byline"><?= e($resource['organization'] ?? '') ?><?php if (!empty($resource['country'])): ?> · <?= e($resource['country']) ?><?php endif; ?><?php if ($reviewed !== null): ?> · Revisado el <?= e($reviewed) ?><?php endif; ?></p>
            </div>
            <div class="page-hero-aside resource-hero-action">
                <a class="btn btn-primary" href="<?= e($resource['url']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= icon('external-link', 'icon icon-sm') ?> <?= e($resource['action_label'] ?: 'Abrir recurso') ?>
                </a>
                <span>Abre el recurso externo en una pestaña nueva.</span>
                <button class="btn btn-outline btn-sm" type="button" data-copy="<?= e($citation) ?>"><?= icon('copy', 'icon icon-xs') ?> Copiar referencia</button>
                <?php if ($currentUser !== null): ?>
                    <form method="post" action="<?= e(url('/recursos/' . $resource['slug'] . '/favorito')) ?>" id="favorito">
                        <?= csrf_field() ?>
                        <button class="btn <?= $isFavorite ? 'btn-primary' : 'btn-ghost' ?> btn-sm" type="submit">
                            <?= icon('bookmark', 'icon icon-xs') ?> <?= $isFavorite ? 'Guardado en favoritos' : 'Guardar en favoritos' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/acceder')) ?>"><?= icon('bookmark', 'icon icon-xs') ?> Accede para guardar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container resource-detail-layout">
        <article class="resource-detail">
            <section class="resource-block resource-block-highlight">
                <span class="eyebrow">Para qué te sirve</span>
                <h2>Qué puedes hacer con este recurso</h2>
                <p><?= nl2br(e($resource['what_for'] ?: $resource['description'])) ?></p>
            </section>

            <?php if (!empty($resource['how_to'])): ?>
                <section class="resource-block">
                    <span class="eyebrow">Paso a paso</span>
                    <h2>Cómo empezar</h2>
                    <ol class="resource-steps">
                        <?php foreach ($resource['how_to'] as $index => $step): ?>
                            <li>
                                <span class="resource-step-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <p><?= e($step) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>

            <div class="resource-info-grid">
                <section class="resource-block">
                    <span class="eyebrow">Requisitos</span>
                    <h2>Qué necesitas</h2>
                    <p><?= nl2br(e($resource['requirements'] ?: 'Navegador web. Revisa las condiciones de acceso antes de usar el recurso.')) ?></p>
                </section>
                <section class="resource-block">
                    <span class="eyebrow">Acceso</span>
                    <h2>Cómo se consulta</h2>
                    <p><?= e(access_label($resource['access'])) ?>. <?= $isRequest ? 'Deberás solicitar acceso al equipo responsable y esperar su respuesta.' : 'Puedes abrirlo directamente desde el botón principal.' ?></p>
                    <?php if (!empty($resource['license'])): ?><p class="resource-license">Licencia: <?= e($resource['license']) ?></p><?php endif; ?>
                </section>
            </div>

            <?php if (!empty($resource['guide'])): ?>
                <section class="resource-block resource-guide">
                    <span class="eyebrow">Lectura editorial</span>
                    <h2>Lo que debes saber antes de usarlo</h2>
                    <p><?= nl2br(e($resource['guide'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if ($isRequest): ?>
                <section class="resource-block request-box">
                    <div>
                        <span class="eyebrow">Plantilla de solicitud</span>
                        <h2>Pide acceso sin complicarte</h2>
                        <p>Copiamos por ti un correo base. Completa los datos entre corchetes, ajústalo a tu caso y envíalo al equipo responsable.</p>
                    </div>
                    <button class="btn btn-outline" type="button" data-copy="<?= e($requestTemplate) ?>"><?= icon('copy', 'icon icon-sm') ?> Copiar plantilla de solicitud</button>
                </section>
            <?php endif; ?>

            <div class="notice notice-info">
                <?= icon('alert-triangle', 'icon') ?>
                <div>
                    <strong>Recurso de terceros.</strong>
                    <p>AgroDiagnóstico no controla la disponibilidad, la licencia ni las condiciones de acceso de este recurso. Verifica la información en la fuente oficial antes de usarla en investigación, docencia o decisiones agronómicas.</p>
                </div>
            </div>

            <?php if ($related !== []): ?>
                <section class="resource-block">
                    <span class="eyebrow">Sigue explorando</span>
                    <h2>Recursos relacionados</h2>
                    <div class="related-resources">
                        <?php foreach ($related as $item): ?>
                            <a href="<?= e(url('/recursos/' . $item['slug'])) ?>">
                                <span class="resource-icon resource-icon-sm"><?= icon('folder', 'icon icon-sm') ?></span>
                                <span>
                                    <strong><?= e($item['title']) ?></strong>
                                    <small><?= e(category_label($item['category'])) ?> · <?= e(access_label($item['access'])) ?></small>
                                </span>
                                <?= icon('arrow-right', 'icon icon-sm') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>

        <aside class="case-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Ficha del recurso</h2>
                <dl class="plot-data">
                    <div><dt>Tipo</dt><dd><?= e(category_label($resource['category'])) ?></dd></div>
                    <div><dt>Acceso</dt><dd><?= e(access_label($resource['access'])) ?></dd></div>
                    <div><dt>Organización</dt><dd><?= e($resource['organization'] ?: 'No especificada') ?></dd></div>
                    <div><dt>País</dt><dd><?= e($resource['country'] ?: 'Global') ?></dd></div>
                    <div><dt>Idioma</dt><dd><?= e(strtoupper((string) $resource['language'])) ?></dd></div>
                    <?php if (!empty($resource['license'])): ?><div><dt>Licencia</dt><dd><?= e($resource['license']) ?></dd></div><?php endif; ?>
                    <?php if ($reviewed !== null): ?><div><dt>Revisión</dt><dd><?= e($reviewed) ?></dd></div><?php endif; ?>
                </dl>
            </div>

            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('external-link', 'icon icon-sm') ?> Ir al recurso</h2>
                <p>El recurso vive en un sitio externo. Esta ficha te acompaña para que sepas qué hacer al llegar.</p>
                <a class="btn btn-primary btn-block" href="<?= e($resource['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($resource['action_label'] ?: 'Abrir recurso') ?></a>
            </div>

            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('book-open', 'icon icon-sm') ?> ¿No sabes por dónde empezar?</h2>
                <p>Consulta el glosario o descarga una plantilla para solicitar acceso.</p>
                <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('/recursos/glosario')) ?>">Abrir glosario</a>
                <a class="btn btn-ghost btn-block btn-sm" href="<?= e(url('/recursos/kit')) ?>">Ver kit de plantillas</a>
            </div>
        </aside>
    </div>
</section>
