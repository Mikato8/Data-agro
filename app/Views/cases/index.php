<?php
/** @var array<int, array<string, mixed>> $cases */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
/** @var array<int, array<string, mixed>> $speciesList */
/** @var array<int, array<string, mixed>> $states */
/** @var array<int, int> $years */
/** @var array<int, string> $tags */
$hasFilters = $filters['q'] !== '' || $filters['species'] !== '' || $filters['state'] !== '' || $filters['outcome'] !== '' || $filters['year'] > 0 || $filters['tag'] !== '';
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Casos clínicos</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Archivo clínico</span>
                <h1>Casos clínicos veterinarios</h1>
                <p>Experiencias reales de colegas en México, documentadas con estructura editorial y revisadas antes de publicarse.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= number_format($total) ?></span>
                <span>caso<?= $total === 1 ? '' : 's' ?> encontrado<?= $total === 1 ? '' : 's' ?></span>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/casos/nuevo')) ?>"><?= icon('plus', 'icon icon-xs') ?> Compartir caso</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('/casos')) ?>" data-filter-form>
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Diagnóstico, signo, palabra clave…" aria-label="Buscar casos">
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
                <span>Estado</span>
                <select name="state">
                    <option value="">Todos</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?= e($state['code']) ?>" <?= $filters['state'] === $state['code'] ? 'selected' : '' ?>><?= e($state['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Desenlace</span>
                <select name="outcome">
                    <option value="">Todos</option>
                    <?php foreach (['curado' => 'Resuelto', 'en_tratamiento' => 'En tratamiento', 'secuela' => 'Con secuela', 'fallecido' => 'Fallecido', 'eutanasia' => 'Eutanasia', 'desconocido' => 'Sin seguimiento'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['outcome'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Año</span>
                <select name="year">
                    <option value="">Todos</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $filters['year'] === $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Orden</span>
                <select name="sort">
                    <option value="recientes" <?= $filters['sort'] === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="relevancia" <?= $filters['sort'] === 'relevancia' ? 'selected' : '' ?>>Relevancia</option>
                    <option value="vistos" <?= $filters['sort'] === 'vistos' ? 'selected' : '' ?>>Más vistos</option>
                    <option value="antiguos" <?= $filters['sort'] === 'antiguos' ? 'selected' : '' ?>>Más antiguos</option>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Aplicar</button>
            <?php if ($hasFilters): ?>
                <a class="filter-clear" href="<?= e(url('/casos')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($tags !== []): ?>
            <div class="tag-row">
                <span class="tag-row-label">Temas:</span>
                <?php foreach ($tags as $tag): ?>
                    <a class="tag <?= $filters['tag'] === $tag ? 'is-active' : '' ?>" href="<?= e(url('/casos?tag=' . rawurlencode($tag))) ?>"><?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($cases === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('search', 'icon') ?></span>
                <h2>No encontramos casos con esos filtros</h2>
                <p>Prueba con otra especie, un término más general o limpia los filtros para ver todo el archivo.</p>
                <div class="empty-actions">
                    <a class="btn btn-primary" href="<?= e(url('/casos')) ?>">Ver todos los casos</a>
                    <a class="btn btn-outline" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso</a>
                </div>
            </div>
        <?php else: ?>
            <div class="results-meta">
                <p>Mostrando <strong><?= $paginator->from() ?>–<?= $paginator->to() ?></strong> de <strong><?= number_format($total) ?></strong> casos</p>
            </div>
            <div class="case-grid">
                <?php foreach ($cases as $case): ?>
                    <?php \App\Core\View::partial('partials/case-card', ['case' => $case]); ?>
                <?php endforeach; ?>
            </div>
            <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>
    </div>
</section>
