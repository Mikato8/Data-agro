<?php
/** @var array<string, int> $stats */
/** @var array<int, array<string, mixed>> $featured */
/** @var array<int, array<string, mixed>> $latest */
/** @var array<int, array<string, mixed>> $resources */
/** @var array<int, array<string, mixed>> $diseases */
/** @var array<int, array<string, mixed>> $species */
/** @var array<int, string> $tags */
$hero = $featured[0] ?? null;
?>
<section class="hero">
    <div class="hero-pattern" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Comunidad veterinaria de México</span>
            <h1>Casos clínicos que <em>enseñan.</em><br>Decisiones que mejoran.</h1>
            <p class="hero-lead">La biblioteca colaborativa donde médicos veterinarios comparten casos reales, contrastan diagnósticos y construyen evidencia para la práctica clínica en México.</p>

            <form class="hero-search" action="<?= e(url('/casos')) ?>" method="get" role="search">
                <label class="sr-only" for="hero-q">Buscar casos clínicos</label>
                <div class="hero-search-field">
                    <?= icon('search', 'icon') ?>
                    <input id="hero-q" type="search" name="q" placeholder="Busca por diagnóstico, signo o especie…">
                </div>
                <label class="sr-only" for="hero-species">Especie</label>
                <select id="hero-species" name="species">
                    <option value="">Todas las especies</option>
                    <?php foreach ($species as $item): ?>
                        <option value="<?= e($item['code']) ?>"><?= e($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Buscar casos <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </form>

            <?php if ($tags !== []): ?>
                <div class="hero-tags">
                    <span>Búsquedas frecuentes:</span>
                    <?php foreach (array_slice($tags, 0, 5) as $tag): ?>
                        <a href="<?= e(url('/casos?tag=' . rawurlencode($tag))) ?>"><?= e($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <dl class="hero-stats">
                <div><dt><?= number_format($stats['cases'] ?? 0) ?></dt><dd>casos publicados</dd></div>
                <div><dt><?= number_format($stats['vets'] ?? 0) ?></dt><dd>veterinarios verificados</dd></div>
                <div><dt><?= number_format($stats['states'] ?? 0) ?></dt><dd>estados representados</dd></div>
                <div><dt><?= number_format($stats['species'] ?? 0) ?></dt><dd>especies documentadas</dd></div>
            </dl>
        </div>

        <div class="hero-visual">
            <?php if ($hero !== null): ?>
                <article class="hero-case">
                    <div class="hero-case-top">
                        <span class="hero-case-species"><?= e($hero['species_emoji'] ?? species_emoji($hero['species_code'] ?? null)) ?> <?= e($hero['species_name'] ?? '') ?></span>
                        <span class="hero-case-folio"><?= e($hero['folio'] ?? 'Caso clínico') ?></span>
                    </div>
                    <div class="hero-case-diagnosis">
                        <span>Diagnóstico documentado</span>
                        <strong><?= e($hero['final_diagnosis'] ?? '') ?></strong>
                    </div>
                    <h2><a href="<?= e(url('/casos/' . $hero['slug'])) ?>"><?= e($hero['title']) ?></a></h2>
                    <p><?= e(excerpt($hero['chief_complaint'] ?? '', 130)) ?></p>
                    <div class="hero-case-meta">
                        <span><?= icon('map-pin', 'icon icon-xs') ?> <?= e($hero['state_name'] ?? 'México') ?></span>
                        <span><?= icon('calendar', 'icon icon-xs') ?> <?= e(format_date($hero['published_at'] ?? null, 'M Y')) ?></span>
                    </div>
                    <div class="hero-case-footer">
                        <span class="case-author">
                            <span class="avatar avatar-xs"><?= e(initials((string) ($hero['author_name'] ?? 'DV'))) ?></span>
                            <span><?= e($hero['author_name'] ?? '') ?></span>
                        </span>
                        <a class="text-link text-link-light" href="<?= e(url('/casos/' . $hero['slug'])) ?>">Leer caso <?= icon('arrow-right', 'icon icon-xs') ?></a>
                    </div>
                    <span class="hero-case-badge"><?= icon('shield', 'icon icon-xs') ?> Revisión editorial</span>
                </article>
            <?php else: ?>
                <div class="hero-empty">
                    <span><?= icon('paw', 'icon') ?></span>
                    <h2>El primer caso puede ser tuyo.</h2>
                    <p>Comparte tu experiencia clínica y ayuda a otros colegas a tomar mejores decisiones.</p>
                    <a class="btn btn-primary" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso</a>
                </div>
            <?php endif; ?>
            <div class="hero-note">
                <?= icon('check-circle', 'icon icon-sm') ?>
                <span>Casos revisados por un comité editorial de médicos veterinarios.</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-cases">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Selección editorial</span>
                <h2>Casos que vale la pena leer</h2>
            </div>
            <a class="text-link" href="<?= e(url('/casos')) ?>">Ver todos los casos <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="case-grid">
            <?php foreach (array_slice($featured, 0, 3) as $case): ?>
                <?php \App\Core\View::partial('partials/case-card', ['case' => $case]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-diagnostic">
    <div class="container diagnostic-band">
        <div class="diagnostic-copy">
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Herramienta clínica</span>
            <h2>Del signo al diferencial,<br>con criterio.</h2>
            <p>Selecciona la especie y los hallazgos clínicos. El portal pondera coincidencias con más de 30 enfermedades y te muestra signos clave, urgencia y riesgo zoonótico.</p>
            <ul class="check-list check-list-light">
                <li><?= icon('check', 'icon icon-sm') ?> Diferenciales ordenados por similitud</li>
                <li><?= icon('check', 'icon icon-sm') ?> Signos clave que faltan explorar</li>
                <li><?= icon('check', 'icon icon-sm') ?> Alertas de enfermedades zoonóticas</li>
            </ul>
            <a class="btn btn-sand" href="<?= e(url('/apoyo-diagnostico')) ?>">Abrir apoyo diagnóstico <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="diagnostic-preview" aria-hidden="true">
            <div class="preview-head">
                <span><?= icon('stethoscope', 'icon icon-sm') ?> Diferenciales sugeridos</span>
                <span class="badge badge-live">Demostración</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 86%"></div>
                <div><strong>Parvovirus canino</strong><span>8 de 8 signos compatibles</span></div>
                <span class="preview-score">86%</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 64%"></div>
                <div><strong>Gastroenteritis bacteriana</strong><span>5 de 8 signos compatibles</span></div>
                <span class="preview-score">64%</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 51%"></div>
                <div><strong>Coronavirus canino</strong><span>4 de 8 signos compatibles</span></div>
                <span class="preview-score">51%</span>
            </div>
            <p class="preview-foot">Herramienta de orientación. No sustituye el juicio clínico ni las pruebas diagnósticas.</p>
        </div>
    </div>
</section>

<section class="section section-latest">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Archivo clínico</span>
                <h2>Publicaciones recientes</h2>
            </div>
            <a class="text-link" href="<?= e(url('/casos?sort=recientes')) ?>">Explorar el archivo <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="case-grid case-grid-3">
            <?php foreach (array_slice($latest, 0, 6) as $case): ?>
                <?php \App\Core\View::partial('partials/case-card', ['case' => $case]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-resources">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Investigación y datos</span>
                <h2>Recursos para mirar más allá del consultorio</h2>
                <p class="section-sub">Datasets, APIs y sistemas de vigilancia curados para investigación, docencia y toma de decisiones.</p>
            </div>
            <a class="text-link" href="<?= e(url('/recursos')) ?>">Ver directorio completo <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="resource-grid resource-grid-4">
            <?php foreach ($resources as $resource): ?>
                <a class="resource-card" href="<?= e(url('/recursos/' . $resource['slug'])) ?>">
                    <span class="resource-icon"><?= icon($resource['category'] === 'dataset' ? 'database' : ($resource['category'] === 'api' ? 'activity' : ($resource['category'] === 'vigilancia' ? 'globe' : 'folder')), 'icon') ?></span>
                    <span class="badge badge-soft"><?= e(category_label($resource['category'])) ?></span>
                    <h3><?= e($resource['title']) ?></h3>
                    <p><?= e(excerpt($resource['description'], 95)) ?></p>
                    <span class="resource-foot"><?= e($resource['organization'] ?? '') ?> Leer guía <?= icon('arrow-right', 'icon icon-xs') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-diseases">
    <div class="container diseases-band">
        <div class="diseases-intro">
            <span class="eyebrow">Biblioteca clínica</span>
            <h2>Fichas de enfermedades</h2>
            <p>Resúmenes en español con signos clave, diagnóstico, tratamiento y prevención. Diseñadas como punto de partida, no como receta.</p>
            <a class="btn btn-outline" href="<?= e(url('/enfermedades')) ?>">Explorar fichas <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="disease-list">
            <?php foreach ($diseases as $disease): ?>
                <a class="disease-row" href="<?= e(url('/enfermedades/' . $disease['slug'])) ?>">
                    <span class="urgency-dot urgency-<?= e($disease['urgency']) ?>"></span>
                    <span class="disease-row-main">
                        <strong><?= e($disease['name']) ?></strong>
                        <small><?= e($disease['category']) ?> · <?= e($disease['species_names'] ?? '') ?></small>
                    </span>
                    <?php if ($disease['zoonotic'] === true || $disease['zoonotic'] === 't'): ?>
                        <span class="badge badge-warning">Zoonosis</span>
                    <?php endif; ?>
                    <?= icon('chevron-right', 'icon icon-sm') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-cta">
    <div class="container cta-band">
        <div>
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Tu experiencia importa</span>
            <h2>Un caso bien documentado<br>puede cambiar una decisión.</h2>
            <p>Comparte lo que aprendiste. El comité editorial revisa cada envío y cuida la confidencialidad del tutor y del paciente.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-sand" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso <?= icon('arrow-right', 'icon icon-sm') ?></a>
            <a class="btn btn-ghost-light" href="<?= e(url('/etica')) ?>">Leer criterios de publicación</a>
        </div>
        <ol class="cta-steps">
            <li><span>01</span> Documenta el caso con la guía del portal.</li>
            <li><span>02</span> Anonimiza y confirma el consentimiento.</li>
            <li><span>03</span> Envía a revisión editorial.</li>
        </ol>
    </div>
</section>
