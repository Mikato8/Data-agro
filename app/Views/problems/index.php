<?php
/** @var array<int, array<string, mixed>> $problems */
/** @var array<string, mixed> $filters */
/** @var array<int, array<string, mixed>> $cropsList */
/** @var array<int, string> $categories */
$hasFilters = $filters['q'] !== '' || $filters['crops'] !== '' || $filters['category'] !== '';
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Plagas y enfermedades</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Sanidad vegetal</span>
                <h1>Plagas y enfermedades</h1>
                <p>Resúmenes prácticos en español para orientar el diagnóstico, ordenar diferenciales y recordar medidas de prevención.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($problems) ?></span>
                <span>fichas disponibles</span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/apoyo-diagnostico')) ?>"><?= icon('sprout', 'icon icon-xs') ?> Apoyo diagnóstico</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('/enfermedades')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre de la plaga, enfermedad o deficiencia…" aria-label="Buscar problema fitosanitario">
            </div>
            <label class="filter-select">
                <span>Cultivo</span>
                <select name="crops">
                    <option value="">Todos</option>
                    <?php foreach ($cropsList as $crops): ?>
                        <option value="<?= e($crops['code']) ?>" <?= $filters['crops'] === $crops['code'] ? 'selected' : '' ?>><?= e($crops['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Categoría</span>
                <select name="category">
                    <option value="">Todos</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/enfermedades')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <?php if ($problems === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('book-open', 'icon') ?></span>
                <h2>No encontramos fichas con esos filtros</h2>
                <p>Prueba con otro término o limpia los filtros para explorar toda la biblioteca.</p>
                <a class="btn btn-primary" href="<?= e(url('/enfermedades')) ?>">Ver todas las fichas</a>
            </div>
        <?php else: ?>
            <div class="problem-grid">
                <?php foreach ($problems as $problem): ?>
                    <a class="problem-card" href="<?= e(url('/enfermedades/' . $problem['slug'])) ?>">
                        <div class="problem-card-top">
                            <span class="risk-dot risk-<?= e($problem['risk']) ?>"></span>
                            <span class="badge badge-soft"><?= e($problem['category']) ?></span>
                            <?php if ($problem['reportable'] === true || $problem['reportable'] === 't'): ?><span class="badge badge-warning">Alerta cuarentenaria</span><?php endif; ?>
                        </div>
                        <h2><?= e($problem['name']) ?></h2>
                        <?php if (!empty($problem['scientific_name'])): ?><p class="scientific"><?= e($problem['scientific_name']) ?></p><?php endif; ?>
                        <p><?= e(excerpt($problem['summary'], 145)) ?></p>
                        <div class="problem-card-foot">
                            <span><?= icon('leaf', 'icon icon-xs') ?> <?= e($problem['crop_names'] ?? 'Varios cultivos') ?></span>
                            <span><?= (int) $problem['signs_count'] ?> síntomas</span>
                            <?= icon('arrow-right', 'icon icon-sm') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
