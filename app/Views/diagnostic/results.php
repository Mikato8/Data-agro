<?php
/** @var array<string, mixed> $species */
/** @var array<int, array<string, mixed>> $selectedSigns */
/** @var array<int, int> $selectedIds */
/** @var array<int, array<string, mixed>> $results */
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/apoyo-diagnostico')) ?>">Apoyo diagnóstico</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Resultados</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Diferenciales sugeridos · <?= e($species['name']) ?></span>
                <h1><?= count($results) ?> posibles diagnósticos</h1>
                <p>Calculados a partir de <?= count($selectedSigns) ?> signo<?= count($selectedSigns) === 1 ? '' : 's' ?> clínico<?= count($selectedSigns) === 1 ? '' : 's' ?>. Interpreta los resultados junto con el contexto del paciente.</p>
            </div>
            <div class="page-hero-aside">
                <a class="btn btn-outline btn-sm" href="<?= e(url('/apoyo-diagnostico?species=' . rawurlencode((string) $species['code']))) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Ajustar signos</a>
            </div>
        </div>
        <div class="selected-signs-bar">
            <?php foreach ($selectedSigns as $sign): ?>
                <span class="tag"><?= e($sign['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container result-layout">
        <div class="result-list">
            <?php if ($results === []): ?>
                <div class="empty-state">
                    <span class="empty-icon"><?= icon('search', 'icon') ?></span>
                    <h2>No encontramos coincidencias suficientes</h2>
                    <p>Prueba agregando signos más específicos o revisa la especie seleccionada. También puedes consultar las fichas de enfermedades directamente.</p>
                    <div class="empty-actions">
                        <a class="btn btn-primary" href="<?= e(url('/apoyo-diagnostico?species=' . rawurlencode((string) $species['code']))) ?>">Cambiar signos</a>
                        <a class="btn btn-outline" href="<?= e(url('/enfermedades?species=' . rawurlencode((string) $species['code']))) ?>">Ver enfermedades</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($results as $index => $result): ?>
                    <article class="result-card">
                        <div class="result-rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="result-main">
                            <div class="result-head">
                                <div>
                                    <h2><a href="<?= e(url('/enfermedades/' . $result['slug'])) ?>"><?= e($result['name']) ?></a></h2>
                                    <p><?= e($result['scientific_name'] ?? '') ?> · <?= e($result['category']) ?></p>
                                </div>
                                <div class="result-badges">
                                    <?php if ($result['zoonotic']): ?><span class="badge badge-warning">Zoonosis</span><?php endif; ?>
                                    <span class="badge badge-urgency-<?= e($result['urgency']) ?>">Urgencia <?= e(urgency_label($result['urgency'])) ?></span>
                                </div>
                            </div>

                            <div class="match">
                                <div class="match-bar"><span style="width: <?= (int) $result['score'] ?>%"></span></div>
                                <div class="match-meta">
                                    <strong><?= (int) $result['score'] ?>% de coincidencia</strong>
                                    <span><?= (int) $result['matched_count'] ?> de <?= (int) $result['total_signs'] ?> signos del perfil</span>
                                </div>
                            </div>

                            <p class="result-summary"><?= e(excerpt($result['summary'], 220)) ?></p>

                            <div class="result-details">
                                <div>
                                    <span class="detail-label">Signos que coinciden</span>
                                    <div class="tag-row">
                                        <?php foreach (array_slice($result['matched_signs'], 0, 8) as $signName): ?>
                                            <span class="tag tag-success"><?= e($signName) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php if ($result['missing_key'] !== []): ?>
                                    <div>
                                        <span class="detail-label">Signos clave por explorar</span>
                                        <div class="tag-row">
                                            <?php foreach (array_slice($result['missing_key'], 0, 5) as $signName): ?>
                                                <span class="tag tag-muted"><?= e($signName) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a class="text-link" href="<?= e(url('/enfermedades/' . $result['slug'])) ?>">Ver ficha completa <?= icon('arrow-right', 'icon icon-xs') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="result-disclaimer">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>Esto no es un diagnóstico.</strong>
                        <p>Los puntajes son una guía de similitud entre los signos que registraste y el perfil de cada enfermedad. La confirmación requiere juicio clínico, contexto epidemiológico y pruebas diagnósticas. Si el paciente está inestable, prioriza su estabilización.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="result-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('filter', 'icon icon-sm') ?> Cómo leer el puntaje</h2>
                <p>Un puntaje alto indica que tus signos se parecen al perfil de la enfermedad, no que el paciente la tenga. Considera también edad, especie, región, vacunación y exposición.</p>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('activity', 'icon icon-sm') ?> Siguiente paso</h2>
                <ul class="check-list">
                    <li><?= icon('check', 'icon icon-xs') ?> Revisa los signos clave faltantes.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Selecciona pruebas con mejor rendimiento.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Documenta el razonamiento en el expediente.</li>
                </ul>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('cpu', 'icon icon-sm') ?> IA de urgencia</h2>
                <p>Contrasta el relato clínico con un clasificador SetFit + ONNX de cuatro niveles.</p>
                <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('/apoyo-diagnostico/ia')) ?>">Clasificar urgencia</a>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> ¿Tienes un caso parecido?</h2>
                <p>Compártelo con la comunidad y ayuda a ampliar el archivo clínico.</p>
                <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/casos/nuevo')) ?>">Compartir caso</a>
            </div>
        </aside>
    </div>
</section>
