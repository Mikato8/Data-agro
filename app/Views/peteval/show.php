<?php
/** @var array<string, mixed> $record */
/** @var array<string, mixed> $stats */
$diseases = $record['diseases'] ?? [];
$anonymisation = $record['anonymisation'] ?? [];
$icdLabels = $record['icd_labels'] ?? [];
$highlighted = App\Support\PetEvalText::highlight((string) $record['sentence'], $diseases, $anonymisation);
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/peteval')) ?>">PetEVAL</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Registro #<?= (int) $record['id'] ?></span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <div class="case-kicker">
                    <span class="badge badge-soft">Registro anonimizado</span>
                    <span class="badge badge-success"><?= count($icdLabels) ?> etiqueta<?= count($icdLabels) === 1 ? '' : 's' ?> ICD-11</span>
                    <?php if ($diseases !== []): ?><span class="badge badge-warning"><?= count($diseases) ?> enfermedad<?= count($diseases) === 1 ? '' : 'es' ?></span><?php endif; ?>
                </div>
                <h1>Registro PetEVAL <span class="peteval-id-lg">#<?= (int) $record['id'] ?></span></h1>
                <p class="page-hero-lead">Texto clínico anonimizado del split de prueba, con anotaciones ICD-11, enfermedades y datos identificables.</p>
            </div>
            <div class="page-hero-aside">
                <a class="btn btn-outline btn-sm" href="<?= e(url('/peteval')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver al explorador</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/descargas/peteval/test-00000-of-00001.parquet')) ?>" download><?= icon('download', 'icon icon-xs') ?> Descargar dataset</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container peteval-detail-layout">
        <article class="peteval-detail">
            <div class="peteval-text-card">
                <div class="peteval-text-head">
                    <span class="eyebrow">Texto clínico anonimizado</span>
                    <span class="peteval-legend"><span class="legend-disease"></span> Enfermedad <span class="legend-anon"></span> Dato anonimizado</span>
                </div>
                <div class="peteval-text"><?= $highlighted ?></div>
            </div>

            <div class="notice notice-warning">
                <?= icon('alert-triangle', 'icon') ?>
                <div>
                    <strong>No intentes reidentificar.</strong>
                    <p>Este texto fue anonimizado para investigación. No busques ni publiques información que permita identificar a tutores, animales, clínicas o ciudades. Usa el registro sólo para docencia, investigación o evaluación de modelos.</p>
                </div>
            </div>

            <?php if ($icdLabels !== []): ?>
                <section class="peteval-detail-block">
                    <h2>Etiquetas ICD-11</h2>
                    <div class="tag-row">
                        <?php foreach ($icdLabels as $label): ?><span class="tag"><?= e($label) ?></span><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($diseases !== []): ?>
                <section class="peteval-detail-block">
                    <h2>Entidades de enfermedad</h2>
                    <div class="peteval-entity-list">
                        <?php foreach ($diseases as $entity): ?>
                            <div>
                                <strong><?= e($entity['entity'] ?? '') ?></strong>
                                <small>Posición <?= (int) ($entity['start'] ?? 0) ?>–<?= (int) ($entity['end'] ?? 0) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($anonymisation !== []): ?>
                <section class="peteval-detail-block">
                    <h2>Datos anonimizados</h2>
                    <div class="tag-row">
                        <?php foreach ($anonymisation as $entity): ?><span class="tag tag-muted"><?= e($entity['label'] ?? '') ?></span><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>

        <aside class="case-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('activity', 'icon icon-sm') ?> Anotaciones</h2>
                <dl class="patient-data">
                    <div><dt>ID</dt><dd>#<?= (int) $record['id'] ?></dd></div>
                    <div><dt>Etiquetas ICD-11</dt><dd><?= count($icdLabels) ?></dd></div>
                    <div><dt>Enfermedades</dt><dd><?= count($diseases) ?></dd></div>
                    <div><dt>Anonimizaciones</dt><dd><?= count($anonymisation) ?></dd></div>
                    <div><dt>Split</dt><dd>Test</dd></div>
                </dl>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('database', 'icon icon-sm') ?> Sobre PetEVAL</h2>
                <p>Benchmark de historiales veterinarios reales del Reino Unido, anotados con ICD-11, NER de enfermedades y anonimización.</p>
                <a class="text-link" href="<?= e(url('/peteval')) ?>">Explorar más registros <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('download', 'icon icon-sm') ?> Descargas</h2>
                <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/descargas/peteval/test-00000-of-00001.parquet')) ?>" download>Parquet completo</a>
                <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('/descargas/peteval/peteval-paper.pdf')) ?>" target="_blank" rel="noopener">Artículo (PDF)</a>
                <a class="btn btn-ghost btn-block btn-sm" href="https://huggingface.co/buckets/Mikato8/PetEVAL-bucket" target="_blank" rel="noopener noreferrer">Abrir bucket</a>
            </div>
        </aside>
    </div>
</section>
