<?php
/** @var array<string, array<int, array{0:string,1:string,2:string,3:string,4:string}>> $groups */
$total = 0;
foreach ($groups as $items) { $total += count($items); }
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Revistas y directorios</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Bibliotecas y evidencia</span>
                <h1>Revistas y directorios de acceso abierto</h1>
                <p class="page-hero-lead">Buscadores, hemerotecas y publicaciones agronómicas gratuitas para leer, citar y mantenerte actualizado. La mayoría no requiere cuenta; algunas piden registro sin costo.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= $total ?></span>
                <span>revistas, directorios y plataformas</span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/recursos')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver a recursos</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="journals-toolbar">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" placeholder="Buscar revista, país o tema…" aria-label="Buscar revistas" data-journal-search>
            </div>
            <p><?= icon('info', 'icon icon-xs') ?> Verifica la licencia y las condiciones de cada revista antes de reutilizar un artículo.</p>
        </div>

        <?php foreach ($groups as $group => $items): ?>
            <section class="journals-group" data-journal-group>
                <div class="journals-group-head">
                    <h2><?= e($group) ?></h2>
                    <span><?= count($items) ?> recurso<?= count($items) === 1 ? '' : 's' ?></span>
                </div>
                <div class="journals-grid">
                    <?php foreach ($items as [$name, $url, $description, $region, $access]): ?>
                        <article class="journal-card" data-journal-card data-journal-text="<?= e(mb_strtolower($name . ' ' . $description . ' ' . $region . ' ' . $access)) ?>">
                            <div class="journal-card-top">
                                <span class="journal-region"><?= e($region) ?></span>
                                <span class="badge badge-<?= $access === 'Abierto' ? 'success' : 'warning' ?>"><?= e($access) ?></span>
                            </div>
                            <h3><?= e($name) ?></h3>
                            <p><?= e($description) ?></p>
                            <a class="journal-link" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Abrir recurso <?= icon('external-link', 'icon icon-xs') ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="journals-empty" data-journal-empty hidden>
            <?= icon('search', 'icon') ?>
            <p>No encontramos revistas con esa búsqueda.</p>
        </div>

        <div class="resource-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p>Los enlaces abren el sitio oficial de cada revista o directorio. Algunos textos completos pueden requerir registro gratuito o acceso institucional; revisa siempre la licencia de cada artículo.</p>
        </div>
    </div>
</section>
