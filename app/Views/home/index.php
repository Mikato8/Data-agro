<?php
/** @var array<string, int> $stats */
/** @var array<int, array<string, mixed>> $featured */
/** @var array<int, array<string, mixed>> $latest */
/** @var array<int, array<string, mixed>> $resources */
/** @var array<int, array<string, mixed>> $problems */
/** @var array<int, array<string, mixed>> $crops */
/** @var array<int, string> $tags */
$hero = $featured[0] ?? null;
?>
<section class="hero">
    <div class="hero-pattern" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Comunidad agronómica de México</span>
            <h1>Casos de campo que <em>enseñan.</em><br>Decisiones que mejoran.</h1>
            <p class="hero-lead">La biblioteca colaborativa donde ingenieros agrónomos comparten casos reales, contrastan diagnósticos y construyen evidencia para la producción agrícola de México.</p>

            <form class="hero-search" action="<?= e(url('/casos')) ?>" method="get" role="search">
                <label class="sr-only" for="hero-q">Buscar casos agronómicos</label>
                <div class="hero-search-field">
                    <?= icon('search', 'icon') ?>
                    <input id="hero-q" type="search" name="q" placeholder="Diagnóstico o síntoma…">
                </div>
                <label class="sr-only" for="hero-crops">Cultivo</label>
                <select id="hero-crops" name="crops">
                    <option value="">Todos los cultivos</option>
                    <?php foreach ($crops as $item): ?>
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
                <div><dt><?= number_format($stats['agronomos'] ?? 0) ?></dt><dd>agrónomos verificados</dd></div>
                <div><dt><?= number_format($stats['states'] ?? 0) ?></dt><dd>estados representados</dd></div>
                <div><dt><?= number_format($stats['crops'] ?? 0) ?></dt><dd>cultivos documentados</dd></div>
            </dl>
        </div>

        <div class="hero-visual">
            <?php if ($hero !== null): ?>
                <article class="hero-case">
                    <div class="hero-case-top">
                        <span class="hero-case-crop"><?= e($hero['crop_emoji'] ?? crop_emoji($hero['crop_code'] ?? null)) ?> <?= e($hero['crop_name'] ?? '') ?></span>
                        <span class="hero-case-folio"><?= e($hero['folio'] ?? 'Caso agronómico') ?></span>
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
                    <span><?= icon('sprout', 'icon') ?></span>
                    <h2>El primer caso puede ser tuyo.</h2>
                    <p>Comparte tu experiencia de campo y ayuda a otros colegas a tomar mejores decisiones.</p>
                    <a class="btn btn-primary" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso</a>
                </div>
            <?php endif; ?>
            <div class="hero-note">
                <?= icon('check-circle', 'icon icon-sm') ?>
                <span>Casos revisados por un comité editorial de ingenieros agrónomos.</span>
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
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Herramienta de campo</span>
            <h2>Del síntoma al diferencial,<br>con criterio.</h2>
            <p>Selecciona el cultivo y los hallazgos en campo. El portal pondera coincidencias con más de 40 problemas fitosanitarios y te muestra síntomas clave, nivel de riesgo y alertas cuarentenarias.</p>
            <ul class="check-list check-list-light">
                <li><?= icon('check', 'icon icon-sm') ?> Diferenciales ordenados por similitud</li>
                <li><?= icon('check', 'icon icon-sm') ?> Síntomas clave que faltan explorar</li>
                <li><?= icon('check', 'icon icon-sm') ?> Alertas de enfermedades cuarentenarias</li>
            </ul>
            <a class="btn btn-sand" href="<?= e(url('/apoyo-diagnostico')) ?>">Abrir apoyo diagnóstico <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="diagnostic-preview" aria-hidden="true">
            <div class="preview-head">
                <span><?= icon('sprout', 'icon icon-sm') ?> Diferenciales sugeridos</span>
                <span class="badge badge-live">Demostración</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 86%"></div>
                <div><strong>Tizón tardío</strong><span>8 de 8 síntomas compatibles</span></div>
                <span class="preview-score">86%</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 64%"></div>
                <div><strong>Tizón temprano</strong><span>5 de 8 síntomas compatibles</span></div>
                <span class="preview-score">64%</span>
            </div>
            <div class="preview-row">
                <div class="preview-bar" style="--score: 51%"></div>
                <div><strong>Deficiencia de calcio</strong><span>4 de 8 síntomas compatibles</span></div>
                <span class="preview-score">51%</span>
            </div>
            <p class="preview-foot">Herramienta de orientación. No sustituye el criterio técnico ni las pruebas diagnósticas.</p>
        </div>
    </div>
</section>

<section class="section section-latest">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Archivo agronómico</span>
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
                <h2>Recursos para mirar más allá de la parcela</h2>
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

<section class="section section-problems">
    <div class="container problems-band">
        <div class="problems-intro">
            <span class="eyebrow">Sanidad vegetal</span>
            <h2>Plagas y enfermedades</h2>
            <p>Fichas en español con síntomas clave, diagnóstico, manejo y prevención para los cultivos de México. Punto de partida, no receta.</p>
            <a class="btn btn-outline" href="<?= e(url('/enfermedades')) ?>">Explorar fichas <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>
        <div class="problem-list">
            <?php foreach ($problems as $problem): ?>
                <a class="problem-row" href="<?= e(url('/enfermedades/' . $problem['slug'])) ?>">
                    <span class="risk-dot risk-<?= e($problem['risk']) ?>"></span>
                    <span class="problem-row-main">
                        <strong><?= e($problem['name']) ?></strong>
                        <small><?= e($problem['category']) ?> · <?= e($problem['crop_names'] ?? '') ?></small>
                    </span>
                    <?php if ($problem['reportable'] === true || $problem['reportable'] === 't'): ?>
                        <span class="badge badge-warning">Alerta cuarentenaria</span>
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
            <p>Comparte lo que aprendiste. El comité editorial revisa cada envío y cuida la confidencialidad del productor y de su parcela.</p>
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
