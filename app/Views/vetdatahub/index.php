<?php
/** @var array<int, array<string, mixed>> $datasets */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, string> $sources */
/** @var int $catalogTotal */
/** @var string $commit */
/** @var string $importedAt */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['source'] !== '';
$categoryUrl = static function (string $key) use ($filters): string {
    $query = $filters;
    $query['category'] = $key;
    unset($query['page']);
    return url('/vetdatahub?' . http_build_query(array_filter($query, static fn ($value): bool => $value !== '')));
};
?>
<section class="vethub-hero">
    <div class="container">
        <nav class="breadcrumb breadcrumb-light" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>VetDataHub</span>
        </nav>
        <div class="vethub-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Catálogo integrado</span>
                <h1>VetDataHub</h1>
                <p class="vethub-lead">Datasets veterinarios abiertos para investigación, docencia y proyectos clínicos. Explora por área, busca por tema y abre la fuente original de cada conjunto de datos.</p>
                <div class="vethub-actions">
                    <a class="btn btn-sand" href="https://github.com/Mikato8/VetDataHub" target="_blank" rel="noopener noreferrer"><?= icon('external-link', 'icon icon-sm') ?> Repositorio fuente</a>
                    <a class="btn btn-ghost-light" href="<?= e(url('/recursos/vetdatahub')) ?>"><?= icon('book-open', 'icon icon-sm') ?> Guía de uso</a>
                </div>
            </div>
            <div class="vethub-stats">
                <div><strong><?= number_format($catalogTotal) ?></strong><span>datasets</span></div>
                <div><strong><?= count($categories) ?></strong><span>categorías</span></div>
                <div><strong><?= e($importedAt !== '' ? format_date($importedAt, 'M Y') : '—') ?></strong><span>última importación</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="vethub-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p>Este catálogo se genera desde <a href="https://github.com/Mikato8/VetDataHub" target="_blank" rel="noopener noreferrer">github.com/Mikato8/VetDataHub</a> (commit <code><?= e($commit) ?></code>). Los archivos viven en Kaggle, GitHub, figshare y otras fuentes; revisa su licencia y condiciones antes de descargarlos.</p>
        </div>

        <div class="vethub-categories">
            <a class="vethub-chip <?= $filters['category'] === '' ? 'is-active' : '' ?>" href="<?= e(url('/vetdatahub')) ?>">Todo <span><?= $catalogTotal ?></span></a>
            <?php foreach ($categories as $category): ?>
                <a class="vethub-chip <?= $filters['category'] === $category['key'] ? 'is-active' : '' ?>" href="<?= e($categoryUrl((string) $category['key'])) ?>">
                    <?= e($category['label']) ?> <span><?= (int) $category['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="filter-bar" method="get" action="<?= e(url('/vetdatahub')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar dataset: imagen, cáncer, bovino, genética…" aria-label="Buscar datasets">
            </div>
            <label class="filter-select">
                <span>Fuente</span>
                <select name="source">
                    <option value="">Todas</option>
                    <?php foreach ($sources as $source): ?>
                        <option value="<?= e($source) ?>" <?= $filters['source'] === $source ? 'selected' : '' ?>><?= e($source) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Categoría</span>
                <select name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['key']) ?>" <?= $filters['category'] === $category['key'] ? 'selected' : '' ?>><?= e($category['label']) ?> (<?= (int) $category['count'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/vetdatahub')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <div class="results-meta">
            <p>Mostrando <strong><?= $paginator->from() ?>–<?= $paginator->to() ?></strong> de <strong><?= number_format($total) ?></strong> datasets</p>
        </div>

        <?php if ($datasets === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('database', 'icon') ?></span>
                <h2>No encontramos datasets con esos filtros</h2>
                <p>Prueba con otra palabra, una categoría distinta o limpia la búsqueda.</p>
                <a class="btn btn-primary" href="<?= e(url('/vetdatahub')) ?>">Ver todo el catálogo</a>
            </div>
        <?php else: ?>
            <div class="vethub-grid">
                <?php foreach ($datasets as $dataset): ?>
                    <article class="vethub-card">
                        <div class="vethub-card-top">
                            <span class="badge badge-soft"><?= e($dataset['category_label'] ?? $dataset['category']) ?></span>
                            <span class="badge badge-<?= ($dataset['source'] ?? '') === 'Kaggle' ? 'warning' : 'success' ?>"><?= e($dataset['source'] ?? 'Fuente') ?></span>
                        </div>
                        <h2><?= e($dataset['title']) ?></h2>
                        <p><?= e(excerpt($dataset['description'] ?: 'Sin descripción en el catálogo original. Abre la fuente para revisar variables, tamaño y licencia.', 170)) ?></p>
                        <div class="vethub-card-foot">
                            <span><?= e($dataset['source'] ?? '') ?></span>
                            <a class="text-link" href="<?= e($dataset['url']) ?>" target="_blank" rel="noopener noreferrer">Abrir dataset <?= icon('arrow-up-right', 'icon icon-xs') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>

        <div class="vethub-footer-note">
            <strong>¿Quieres aportar un dataset?</strong>
            <p>El repositorio acepta conjuntos de datos abiertos, sin restricciones de uso, en formato ZIP/RAR y organizados por categoría. Consulta las instrucciones en <a href="https://github.com/Mikato8/VetDataHub" target="_blank" rel="noopener noreferrer">Mikato8/VetDataHub</a>.</p>
        </div>
    </div>
</section>
