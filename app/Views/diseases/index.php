<?php
/** @var array<int, array<string, mixed>> $diseases */
/** @var array<string, mixed> $filters */
/** @var array<int, array<string, mixed>> $speciesList */
/** @var array<int, string> $categories */
$hasFilters = $filters['q'] !== '' || $filters['species'] !== '' || $filters['category'] !== '';
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Enfermedades</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Biblioteca clínica</span>
                <h1>Fichas de enfermedades</h1>
                <p>Resúmenes prácticos en español para orientar el diagnóstico, ordenar diferenciales y recordar medidas de prevención.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($diseases) ?></span>
                <span>fichas disponibles</span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/apoyo-diagnostico')) ?>"><?= icon('stethoscope', 'icon icon-xs') ?> Apoyo diagnóstico</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('/enfermedades')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre de la enfermedad…" aria-label="Buscar enfermedad">
            </div>
            <label class="filter-select">
                <span>Especie</span>
                <select name="species">
                    <option value="">Todas</option>
                    <?php foreach ($speciesList as $species): ?>
                        <option value="<?= e($species['code']) ?>" <?= $filters['species'] === $species['code'] ? 'selected' : '' ?>><?= e($species['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Categoría</span>
                <select name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/enfermedades')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <?php if ($diseases === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('book-open', 'icon') ?></span>
                <h2>No encontramos fichas con esos filtros</h2>
                <p>Prueba con otro término o limpia los filtros para explorar toda la biblioteca.</p>
                <a class="btn btn-primary" href="<?= e(url('/enfermedades')) ?>">Ver todas las fichas</a>
            </div>
        <?php else: ?>
            <div class="disease-grid">
                <?php foreach ($diseases as $disease): ?>
                    <a class="disease-card" href="<?= e(url('/enfermedades/' . $disease['slug'])) ?>">
                        <div class="disease-card-top">
                            <span class="urgency-dot urgency-<?= e($disease['urgency']) ?>"></span>
                            <span class="badge badge-soft"><?= e($disease['category']) ?></span>
                            <?php if ($disease['zoonotic'] === true || $disease['zoonotic'] === 't'): ?><span class="badge badge-warning">Zoonosis</span><?php endif; ?>
                        </div>
                        <h2><?= e($disease['name']) ?></h2>
                        <?php if (!empty($disease['scientific_name'])): ?><p class="scientific"><?= e($disease['scientific_name']) ?></p><?php endif; ?>
                        <p><?= e(excerpt($disease['summary'], 145)) ?></p>
                        <div class="disease-card-foot">
                            <span><?= icon('paw', 'icon icon-xs') ?> <?= e($disease['species_names'] ?? 'Especies varias') ?></span>
                            <span><?= (int) $disease['signs_count'] ?> signos</span>
                            <?= icon('arrow-right', 'icon icon-sm') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
