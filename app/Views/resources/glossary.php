<?php
/** @var array<int, array{término: string, definición: string}> $terms */
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Glosario</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Sin tecnicismos</span>
                <h1>Glosario para agrónomos</h1>
                <p class="page-hero-lead">Los términos que aparecen en los repositorios y en los datos abiertos, explicados con ejemplos del campo mexicano. Si no sabes qué es una API o un dataset, empieza aquí.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($terms) ?></span>
                <span>términos explicados</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="symptom-search glossary-search">
            <?= icon('search', 'icon icon-sm') ?>
            <input type="search" placeholder="Filtrar término: dataset, API, licencia…" aria-label="Filtrar glosario" data-glossary-filter>
        </div>

        <div class="glossary-grid" data-glossary-list>
            <?php foreach ($terms as $term): ?>
                <article class="glossary-item" data-glossary-item data-glossary-label="<?= e(mb_strtolower($term['término'] . ' ' . $term['definición'])) ?>">
                    <h2><?= e($term['término']) ?></h2>
                    <p><?= e($term['definición']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="empty-state" data-glossary-empty hidden>
            <span class="empty-icon"><?= icon('search', 'icon') ?></span>
            <h2>No encontramos ese término</h2>
            <p>Prueba con otra palabra o consulta el directorio de recursos.</p>
            <a class="btn btn-primary" href="<?= e(url('/recursos')) ?>">Volver a recursos</a>
        </div>

        <div class="glossary-cta">
            <div>
                <span class="eyebrow">Siguiente paso</span>
                <h2>Ahora que conoces los términos</h2>
                <p>Elige un recurso según tu objetivo o descarga una plantilla para empezar.</p>
            </div>
            <div class="glossary-actions">
                <a class="btn btn-primary" href="<?= e(url('/recursos')) ?>">Explorar recursos</a>
                <a class="btn btn-sand" href="<?= e(url('/recursos/kit')) ?>">Abrir kit de plantillas</a>
            </div>
        </div>
    </div>
</section>
