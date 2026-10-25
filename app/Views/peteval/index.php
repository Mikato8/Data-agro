<?php
/** @var array<int, array<string, mixed>> $records */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
/** @var array<string, mixed> $stats */
/** @var array<int, array<string, mixed>> $icdLabels */
/** @var array<int, array<string, mixed>> $topDiseases */
/** @var array<int, array<string, mixed>> $anonymisationTypes */
/** @var int $catalogTotal */
$hasFilters = $filters['q'] !== '' || $filters['icd'] !== '' || $filters['content'] !== '';
$tasks = [
    ['icon' => 'shield', 'title' => 'Anonimización', 'text' => 'Detectar nombres, lugares, organizaciones y otros identificadores en texto clínico.'],
    ['icon' => 'activity', 'title' => 'Clasificación sindrómica', 'text' => 'Asignar capítulos ICD-11 a cada consulta, incluyendo síntomas y diagnósticos.'],
    ['icon' => 'search', 'title' => 'Extracción de enfermedades', 'text' => 'Identificar entidades de enfermedad con formato IOB2 para estudios epidemiológicos.'],
];
?>
<section class="peteval-hero">
    <div class="container">
        <nav class="breadcrumb breadcrumb-light" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>PetEVAL</span>
        </nav>
        <div class="peteval-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Benchmark integrado</span>
                <h1>PetEVAL</h1>
                <p class="peteval-lead">Historiales clínicos veterinarios reales, anonimizados y anotados profesionalmente con ICD-11, entidades de enfermedad y etiquetas de anonimización. Un recurso para investigación en NLP, vigilancia sindrómica y docencia.</p>
                <div class="peteval-actions">
                    <a class="btn btn-sand" href="<?= e(url('/descargas/peteval/test-00000-of-00001.parquet')) ?>" download><?= icon('download', 'icon icon-sm') ?> Descargar datos</a>
                    <a class="btn btn-ghost-light" href="<?= e(url('/descargas/peteval/peteval-paper.pdf')) ?>" target="_blank" rel="noopener"><?= icon('file-text', 'icon icon-sm') ?> Leer artículo</a>
                    <a class="btn btn-ghost-light" href="https://huggingface.co/buckets/Mikato8/PetEVAL-bucket" target="_blank" rel="noopener noreferrer"><?= icon('external-link', 'icon icon-sm') ?> Abrir bucket</a>
                </div>
            </div>
            <div class="peteval-stats">
                <div><strong><?= number_format($catalogTotal) ?></strong><span>registros de prueba</span></div>
                <div><strong><?= (int) ($stats['icd_labels'] ?? 0) ?></strong><span>etiquetas ICD-11</span></div>
                <div><strong><?= number_format((int) ($stats['disease_entities'] ?? 0)) ?></strong><span>entidades de enfermedad</span></div>
                <div><strong>3</strong><span>tareas del benchmark</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="peteval-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p><strong>Split de prueba:</strong> el bucket contiene 4,999 registros del conjunto de test. El benchmark completo descrito en el artículo tiene 17,600 registros (11,000 entrenamiento, 1,600 evaluación y 5,000 prueba). Los textos ya están anonimizados; no intentes reidentificar personas, animales o establecimientos.</p>
        </div>

        <div class="peteval-task-grid">
            <?php foreach ($tasks as $task): ?>
                <article class="peteval-task">
                    <span class="peteval-task-icon"><?= icon($task['icon'], 'icon') ?></span>
                    <h2><?= e($task['title']) ?></h2>
                    <p><?= e($task['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <form class="filter-bar" method="get" action="<?= e(url('/peteval')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar en el texto: otitis, vacuna, dolor…" aria-label="Buscar en PetEVAL">
            </div>
            <label class="filter-select">
                <span>ICD-11</span>
                <select name="icd">
                    <option value="">Todas</option>
                    <?php foreach ($icdLabels as $label): ?>
                        <option value="<?= e($label['label']) ?>" <?= $filters['icd'] === $label['label'] ? 'selected' : '' ?>><?= e($label['label']) ?> (<?= (int) $label['count'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Contenido</span>
                <select name="content">
                    <option value="">Todos</option>
                    <option value="disease" <?= $filters['content'] === 'disease' ? 'selected' : '' ?>>Con enfermedad</option>
                    <option value="anonymisation" <?= $filters['content'] === 'anonymisation' ? 'selected' : '' ?>>Con datos anonimizados</option>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/peteval')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <div class="results-meta">
            <p>Mostrando <strong><?= $paginator->from() ?>–<?= $paginator->to() ?></strong> de <strong><?= number_format($total) ?></strong> registros</p>
        </div>

        <div class="peteval-layout">
            <div>
                <?php if ($records === []): ?>
                    <div class="empty-state">
                        <span class="empty-icon"><?= icon('search', 'icon') ?></span>
                        <h2>No encontramos registros con esos filtros</h2>
                        <p>Prueba con otra palabra, una etiqueta ICD-11 distinta o limpia la búsqueda.</p>
                        <a class="btn btn-primary" href="<?= e(url('/peteval')) ?>">Ver todos los registros</a>
                    </div>
                <?php else: ?>
                    <div class="peteval-records">
                        <?php foreach ($records as $record): ?>
                            <?php $diseases = $record['diseases'] ?? []; $anonymisation = $record['anonymisation'] ?? []; ?>
                            <article class="peteval-record">
                                <div class="peteval-record-top">
                                    <span class="peteval-id">#<?= (int) $record['id'] ?></span>
                                    <span class="badge badge-soft"><?= count($record['icd_labels'] ?? []) ?> etiqueta<?= count($record['icd_labels'] ?? []) === 1 ? '' : 's' ?> ICD-11</span>
                                    <?php if ($diseases !== []): ?><span class="badge badge-success"><?= count($diseases) ?> enfermedad<?= count($diseases) === 1 ? '' : 'es' ?></span><?php endif; ?>
                                    <?php if ($anonymisation !== []): ?><span class="badge badge-warning"><?= count($anonymisation) ?> anonimización<?= count($anonymisation) === 1 ? '' : 'es' ?></span><?php endif; ?>
                                </div>
                                <p class="peteval-sentence"><?= e(excerpt($record['sentence'], 320)) ?></p>
                                <?php if (!empty($record['icd_labels'])): ?>
                                    <div class="tag-row">
                                        <?php foreach (array_slice($record['icd_labels'], 0, 3) as $label): ?><span class="tag tag-sm"><?= e($label) ?></span><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="peteval-record-foot">
                                    <span><?= e($diseases !== [] ? 'Enfermedades: ' . implode(' · ', array_slice(array_column($diseases, 'entity'), 0, 4)) : 'Sin entidad de enfermedad anotada') ?></span>
                                    <a class="text-link" href="<?= e(url('/peteval/' . $record['id'])) ?>">Ver registro <?= icon('arrow-right', 'icon icon-xs') ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
                <?php endif; ?>
            </div>

            <aside class="peteval-sidebar">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('database', 'icon icon-sm') ?> Distribución ICD-11</h2>
                    <div class="peteval-bar-list">
                        <?php $maxLabel = max(array_column($icdLabels, 'count') ?: [1]); ?>
                        <?php foreach (array_slice($icdLabels, 0, 8) as $label): ?>
                            <div class="peteval-bar-item">
                                <div><span><?= e($label['label']) ?></span><strong><?= (int) $label['count'] ?></strong></div>
                                <span class="peteval-bar"><span style="width: <?= round(((int) $label['count'] / max(1, $maxLabel)) * 100) ?>%"></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('activity', 'icon icon-sm') ?> Enfermedades frecuentes</h2>
                    <div class="tag-row">
                        <?php foreach (array_slice($topDiseases, 0, 12) as $disease): ?><span class="tag tag-sm"><?= e($disease['entity']) ?> · <?= (int) $disease['count'] ?></span><?php endforeach; ?>
                    </div>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('shield', 'icon icon-sm') ?> Tipos de anonimización</h2>
                    <div class="tag-row">
                        <?php foreach ($anonymisationTypes as $type): ?><span class="tag tag-sm"><?= e($type['label']) ?> · <?= (int) $type['count'] ?></span><?php endforeach; ?>
                    </div>
                </div>
                <div class="sidebar-card sidebar-cta">
                    <h2 class="sidebar-title"><?= icon('download', 'icon icon-sm') ?> Llévate el dataset</h2>
                    <p>Descarga el split de prueba en Parquet, el artículo y la ficha del bucket.</p>
                    <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/descargas/peteval/test-00000-of-00001.parquet')) ?>" download>Descargar Parquet</a>
                    <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('/descargas/peteval/peteval-paper.pdf')) ?>" target="_blank" rel="noopener">Leer artículo (PDF)</a>
                </div>
            </aside>
        </div>
    </div>
</section>
