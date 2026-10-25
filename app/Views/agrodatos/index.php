<?php
/** @var array<int, array<string, mixed>> $datasets */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, string> $sources */
/** @var int $catalogTotal */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['source'] !== '';
$categoryUrl = static function (string $key) use ($filters): string {
    $query = $filters;
    $query['category'] = $key;
    unset($query['page']);
    return url('/agrodatos?' . http_build_query(array_filter($query, static fn ($value): bool => $value !== '')));
};
?>
<section class="vethub-hero">
    <div class="container">
        <nav class="breadcrumb breadcrumb-light" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Datos abiertos</span>
        </nav>
        <div class="vethub-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Catálogo curado</span>
                <h1>Datos abiertos</h1>
                <p class="vethub-lead">Conjuntos de datos, APIs y recursos de aprendizaje automático para agricultura, clima, suelos y sanidad vegetal. Todos enlazan a su fuente original.</p>
                <div class="vethub-actions">
                    <a class="btn btn-sand" href="<?= e(url('/recursos')) ?>"><?= icon('book-open', 'icon icon-sm') ?> Ver directorio completo</a>
                    <a class="btn btn-ghost-light" href="<?= e(url('/recursos/glosario')) ?>"><?= icon('info', 'icon icon-sm') ?> Glosario de datos</a>
                </div>
            </div>
            <div class="vethub-stats">
                <div><strong><?= number_format($catalogTotal) ?></strong><span>datasets y APIs</span></div>
                <div><strong><?= count($categories) ?></strong><span>categorías</span></div>
                <div><strong><?= count($sources) ?></strong><span>fuentes</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="vethub-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p>El catálogo reúne recursos de instituciones como SIAP, INEGI, INIFAP, CONABIO, FAO, GBIF y centros de investigación. Cada ficha conserva su licencia y condiciones de acceso: revísalas antes de descargar o publicar resultados.</p>
        </div>

        <div class="vethub-categories">
            <a class="vethub-chip <?= $filters['category'] === '' ? 'is-active' : '' ?>" href="<?= e(url('/agrodatos')) ?>">Todo <span><?= $catalogTotal ?></span></a>
            <?php foreach ($categories as $category): ?>
                <a class="vethub-chip <?= $filters['category'] === $category['key'] ? 'is-active' : '' ?>" href="<?= e($categoryUrl((string) $category['key'])) ?>">
                    <?= e($category['label']) ?> <span><?= (int) $category['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="filter-bar" method="get" action="<?= e(url('/agrodatos')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar: clima, suelo, rendimiento, plagas…" aria-label="Buscar datasets">
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
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/agrodatos')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <div class="results-meta">
            <p>Mostrando <strong><?= $paginator->from() ?>–<?= $paginator->to() ?></strong> de <strong><?= number_format($total) ?></strong> recursos de datos</p>
        </div>

        <?php if ($datasets === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('database', 'icon') ?></span>
                <h2>No encontramos recursos con esos filtros</h2>
                <p>Prueba con otra palabra, una categoría distinta o limpia la búsqueda.</p>
                <a class="btn btn-primary" href="<?= e(url('/agrodatos')) ?>">Ver todo el catálogo</a>
            </div>
        <?php else: ?>
            <div class="vethub-grid">
                <?php foreach ($datasets as $dataset): ?>
                    <article class="vethub-card">
                        <div class="vethub-card-top">
                            <span class="badge badge-soft"><?= e($dataset['category_label'] ?? $dataset['category']) ?></span>
                            <span class="badge badge-success"><?= e(access_label((string) ($dataset['access'] ?? 'abierto'))) ?></span>
                        </div>
                        <h2><?= e($dataset['title']) ?></h2>
                        <p><?= e(excerpt((string) ($dataset['description'] ?? ''), 170)) ?></p>
                        <div class="vethub-card-foot">
                            <span><?= e($dataset['source'] ?? '') ?></span>
                            <?php if (!empty($dataset['slug'])): ?>
                                <a class="text-link" href="<?= e(url('/recursos/' . $dataset['slug'])) ?>">Ver ficha <?= icon('arrow-right', 'icon icon-xs') ?></a>
                            <?php else: ?>
                                <a class="text-link" href="<?= e($dataset['url']) ?>" target="_blank" rel="noopener noreferrer">Abrir fuente <?= icon('arrow-up-right', 'icon icon-xs') ?></a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>

        <div class="vethub-footer-note">
            <strong>¿Conoces un conjunto de datos que debería estar aquí?</strong>
            <p>Escríbenos desde la página de <a href="<?= e(url('/nosotros')) ?>">contacto</a> con el enlace, la institución y la licencia. Revisamos que sea útil, accesible y verificable antes de publicarlo.</p>
        </div>
    </div>
</section>
