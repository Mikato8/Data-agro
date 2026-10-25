<?php
/** @var array<int, array<string, mixed>> $resources */
/** @var array<string, mixed> $filters */
/** @var array<int, string> $categories */
/** @var array<string, array{title: string, description: string, icon: string}> $goals */
/** @var string $goal */
/** @var array<int, array<string, mixed>> $recommendations */
/** @var array<int, array<string, mixed>> $cropsList */
/** @var string $cropCode */
/** @var string $topic */
/** @var array<int, array<string, mixed>> $problemMatches */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['access'] !== '';
$cropsName = '';
foreach ($cropsList as $cropsItem) {
    if ($cropsItem['code'] === $cropCode) {
        $cropsName = (string) $cropsItem['name'];
        break;
    }
}
$goalUrl = static function (string $key) use ($cropCode, $topic): string {
    $query = ['goal' => $key];
    if ($cropCode !== '') { $query['crops'] = $cropCode; }
    if ($topic !== '') { $query['topic'] = $topic; }
    return url('/recursos?' . http_build_query($query));
};
$resourceLink = static fn (array $resource): string => match ($resource['slug'] ?? '') {
    'revistas-acceso-abierto' => url('/recursos/revistas'),
    default => url('/recursos/' . $resource['slug']),
};
$resourceActionLabel = static fn (array $resource): string => match ($resource['slug'] ?? '') {
    'revistas-acceso-abierto' => 'Abrir directorio',
    default => 'Leer guía',
};
$resourceIcon = static fn (string $category): string => match ($category) {
    'dataset' => 'database',
    'api' => 'activity',
    'vigilancia' => 'globe',
    'institucion' => 'shield',
    'guia' => 'book-open',
    'biblioteca' => 'book-open',
    'directorio' => 'search',
    'revista' => 'file-text',
    'plataforma' => 'layers',
    default => 'folder',
};
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Recursos</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Bibliotecas y evidencia</span>
                <h1>Bibliotecas y recursos de acceso abierto</h1>
                <p>Bibliotecas universitarias, repositorios, revistas y libros de acceso abierto para estudiar, investigar y actualizarte. Cada ficha te dice para qué sirve, cómo entrar y qué necesitas.</p>
            </div>
            <div class="page-hero-aside">
                <span class="big-number"><?= count($resources) ?></span>
                <span>recursos con guía en español</span>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/recursos/kit')) ?>"><?= icon('download', 'icon icon-xs') ?> Kit descargable</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="resource-intro">
            <div>
                <span class="eyebrow">Empieza por tu objetivo</span>
                <h2>¿Qué necesitas hacer hoy?</h2>
                <p>Elige una opción y te sugerimos por dónde empezar. No tienes que conocer los nombres técnicos.</p>
            </div>
            <div class="resource-intro-links">
                <a class="text-link" href="<?= e(url('/recursos/glosario')) ?>"><?= icon('book-open', 'icon icon-sm') ?> Glosario para no especialistas</a>
                <a class="text-link" href="<?= e(url('/recursos/kit')) ?>"><?= icon('download', 'icon icon-sm') ?> Plantillas listas para usar</a>
                <?php if ($currentUser !== null): ?><a class="text-link" href="<?= e(url('/recursos/favoritos')) ?>"><?= icon('bookmark', 'icon icon-sm') ?> Mis recursos guardados</a><?php endif; ?>
            </div>
        </div>

        <div class="goal-grid">
            <?php foreach ($goals as $key => $item): ?>
                <a class="goal-card <?= $goal === $key ? 'is-active' : '' ?>" href="<?= e($goalUrl((string) $key)) ?>">
                    <span class="goal-icon"><?= icon($item['icon'], 'icon') ?></span>
                    <strong><?= e($item['title']) ?></strong>
                    <span><?= e($item['description']) ?></span>
                    <span class="goal-action"><?= $goal === $key ? 'Seleccionado' : 'Ver recomendaciones' ?> <?= icon('arrow-right', 'icon icon-xs') ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="recommend-form" method="get" action="<?= e(url('/recursos')) ?>">
            <div class="recommend-form-head">
                <span class="eyebrow">Afina la recomendación</span>
                <p>Agrega el cultivo y, si quieres, una plaga, enfermedad o tema para acercar la sugerencia a tu caso.</p>
            </div>
            <input type="hidden" name="goal" value="<?= e($goal !== '' ? $goal : 'datos') ?>">
            <label class="filter-select">
                <span>Cultivo</span>
                <select name="crops">
                    <option value="">Todas</option>
                    <?php foreach ($cropsList as $cropsItem): ?>
                        <option value="<?= e($cropsItem['code']) ?>" <?= $cropCode === $cropsItem['code'] ? 'selected' : '' ?>><?= e($cropsItem['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="recommend-topic">
                <span>Tema o problema <span class="optional">(opcional)</span></span>
                <input type="search" name="topic" value="<?= e($topic) ?>" placeholder="Ej. cogollero, roya, salinidad…">
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Recomendar</button>
            <?php if ($goal !== '' || $cropCode !== '' || $topic !== ''): ?>
                <a class="filter-clear" href="<?= e(url('/recursos')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($problemMatches !== []): ?>
            <section class="topic-matches">
                <div class="topic-matches-head">
                    <div>
                        <span class="eyebrow">También en el portal</span>
                        <h2>Fichas relacionadas con «<?= e($topic) ?>»</h2>
                        <p>Antes de buscar datos externos, revisa el resumen técnico y los casos del archivo.</p>
                    </div>
                </div>
                <div class="topic-match-grid">
                    <?php foreach ($problemMatches as $problem): ?>
                        <article class="topic-match-card">
                            <div class="problem-card-top">
                                <span class="badge badge-soft"><?= e(risk_label($problem['risk'])) ?></span>
                                <?php if ($problem['reportable'] === true || $problem['reportable'] === 't'): ?><span class="badge badge-warning">Alerta cuarentenaria</span><?php endif; ?>
                            </div>
                            <h3><a href="<?= e(url('/enfermedades/' . $problem['slug'])) ?>"><?= e($problem['name']) ?></a></h3>
                            <p><?= e(excerpt($problem['summary'], 130)) ?></p>
                            <div class="topic-match-actions">
                                <a class="text-link" href="<?= e(url('/enfermedades/' . $problem['slug'])) ?>">Ver ficha <?= icon('arrow-right', 'icon icon-xs') ?></a>
                                <a class="text-link" href="<?= e(url('/casos?q=' . rawurlencode((string) $problem['name']) . ($cropCode !== '' ? '&crops=' . rawurlencode($cropCode) : ''))) ?>">Buscar casos</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($recommendations !== []): ?>
            <section class="recommendation-panel">
                <div class="recommendation-head">
                    <div>
                        <span class="eyebrow">Recomendación para ti</span>
                        <h2><?= e($goals[$goal]['title'] ?? 'Recursos sugeridos') ?></h2>
                        <p>Estos recursos encajan con el objetivo<?= $cropsName !== '' ? ' y el cultivo (' . e($cropsName) . ')' : '' ?><?= $topic !== '' ? ' y el tema «' . e($topic) . '»' : '' ?>. Abre la ficha interna para ver pasos, requisitos y advertencias.</p>
                    </div>
                    <a class="text-link" href="<?= e(url('/recursos')) ?>">Quitar selección</a>
                </div>
                <div class="resource-grid">
                    <?php foreach ($recommendations as $resource): ?>
                        <a class="resource-card resource-card-recommended" href="<?= e($resourceLink($resource)) ?>">
                            <div class="resource-card-top">
                                <span class="resource-icon"><?= icon($resourceIcon($resource['category']), 'icon') ?></span>
                                <span class="badge badge-success">Recomendado</span>
                            </div>
                            <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                            <h3><?= e($resource['title']) ?></h3>
                            <p><?= e(excerpt($resource['what_for'] ?: $resource['description'], 120)) ?></p>
                            <span class="resource-foot"><?= e($resourceActionLabel($resource)) ?> <?= icon('arrow-right', 'icon icon-xs') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="kit-teaser">
            <div class="kit-teaser-icon"><?= icon('folder', 'icon') ?></div>
            <div>
                <span class="eyebrow">Herramientas internas</span>
                <h2>Plantillas que puedes usar sin registrarte</h2>
                <p>Descarga la plantilla de caso agronómico, el checklist de consentimiento, la hoja de recomendación, el consentimiento imprimible, la carta de solicitud de datos y la guía de recursos.</p>
            </div>
            <a class="btn btn-primary" href="<?= e(url('/recursos/kit')) ?>">Abrir kit <?= icon('arrow-right', 'icon icon-sm') ?></a>
        </div>

        <form class="filter-bar" method="get" action="<?= e(url('/recursos')) ?>">
            <div class="filter-search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar recurso, institución…" aria-label="Buscar recurso">
            </div>
            <label class="filter-select">
                <span>Tipo</span>
                <select name="category">
                    <option value="">Todos</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e(category_label($category)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-select">
                <span>Acceso</span>
                <select name="access">
                    <option value="">Todos</option>
                    <?php foreach (['abierto' => 'Abierto', 'solicitud' => 'Por solicitud', 'pago' => 'De pago', 'mixto' => 'Mixto'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['access'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
            <?php if ($hasFilters): ?><a class="filter-clear" href="<?= e(url('/recursos')) ?>">Limpiar</a><?php endif; ?>
        </form>

        <div class="resource-note">
            <?= icon('info', 'icon icon-sm') ?>
            <p>El botón de cada ficha abre el recurso externo en una pestaña nueva. Antes de reutilizar datos, revisa la licencia y las condiciones de acceso. Este directorio es curado y no sustituye la revisión ética de tu proyecto.</p>
        </div>

        <?php if ($resources === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= icon('database', 'icon') ?></span>
                <h2>No encontramos recursos con esos filtros</h2>
                <p>Prueba con otro término o limpia los filtros para ver el directorio completo.</p>
                <a class="btn btn-primary" href="<?= e(url('/recursos')) ?>">Ver todo el directorio</a>
            </div>
        <?php else: ?>
            <div class="resource-grid">
                <?php foreach ($resources as $resource): ?>
                    <a class="resource-card resource-card-lg" href="<?= e($resourceLink($resource)) ?>">
                        <div class="resource-card-top">
                            <span class="resource-icon"><?= icon($resourceIcon($resource['category']), 'icon') ?></span>
                            <span class="badge badge-<?= $resource['access'] === 'abierto' ? 'success' : 'warning' ?>"><?= e(access_label($resource['access'])) ?></span>
                        </div>
                        <span class="resource-category"><?= e(category_label($resource['category'])) ?></span>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e(excerpt($resource['what_for'] ?: $resource['description'], 150)) ?></p>
                        <div class="resource-tags">
                            <?php foreach (array_slice($resource['tags'], 0, 4) as $tag): ?><span class="tag tag-sm"><?= e($tag) ?></span><?php endforeach; ?>
                        </div>
                        <div class="resource-foot">
                            <span><?= e($resource['organization'] ?? '') ?><?php if (!empty($resource['country'])): ?> · <?= e($resource['country']) ?><?php endif; ?></span>
                            <span class="resource-guide-link"><?= e($resourceActionLabel($resource)) ?> <?= icon('arrow-right', 'icon icon-sm') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
