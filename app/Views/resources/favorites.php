<?php
/** @var array<int, array<string, mixed>> $resources */
/** @var array<string, mixed> $user */
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Mis recursos guardados</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Tu biblioteca personal</span>
                <h1>Recursos guardados</h1>
                <p class="page-hero-lead">Las fichas que guardaste para volver a consultar. Puedes quitar un recurso desde su ficha.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($resources) ?></span>
                <span>recurso<?= count($resources) === 1 ? '' : 's' ?> guardado<?= count($resources) === 1 ? '' : 's' ?></span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/recursos')) ?>">Explorar más recursos</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <?php if ($resources === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('bookmark', 'icon') ?></span>
                <h2>Aún no tienes recursos guardados</h2>
                <p>Abre una ficha y usa el botón «Guardar en favoritos» para encontrarla aquí después.</p>
                <div class="empty-actions">
                    <a class="btn btn-primary" href="<?= e(url('/recursos')) ?>">Explorar recursos</a>
                    <a class="btn btn-outline" href="<?= e(url('/recursos/kit')) ?>">Abrir kit de plantillas</a>
                </div>
            </div>
        <?php else: ?>
            <div class="resource-grid">
                <?php foreach ($resources as $resource): ?>
                    <a class="resource-card resource-card-lg" href="<?= e(url('/recursos/' . $resource['slug'])) ?>">
                        <div class="resource-card-top">
                            <span class="resource-icon"><?= icon($resource['category'] === 'dataset' ? 'database' : ($resource['category'] === 'api' ? 'activity' : ($resource['category'] === 'vigilancia' ? 'globe' : ($resource['category'] === 'institucion' ? 'shield' : 'folder'))), 'icon') ?></span>
                            <span class="badge badge-<?= $resource['access'] === 'abierto' ? 'success' : 'warning' ?>"><?= e(access_label($resource['access'])) ?></span>
                        </div>
                        <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e(excerpt($resource['what_for'] ?: $resource['description'], 150)) ?></p>
                        <div class="resource-foot">
                            <span><?= e($resource['organization'] ?? '') ?></span>
                            <span class="resource-guide-link">Leer guía <?= icon('arrow-right', 'icon icon-sm') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
