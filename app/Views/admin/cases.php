<?php
/** @var array<int, array<string, mixed>> $cases */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
?>
<div class="admin-page-head">
    <div>
        <span class="eyebrow">Moderación</span>
        <h1>Casos agronómicos</h1>
        <p><?= number_format($total) ?> caso<?= $total === 1 ? '' : 's' ?> en el sistema.</p>
    </div>
</div>

<div class="admin-card">
    <form class="admin-filter" method="get" action="<?= e(url('/admin/casos')) ?>">
        <div class="filter-search">
            <?= icon('search', 'icon icon-sm') ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar por título, diagnóstico o autor…" aria-label="Buscar casos">
        </div>
        <label class="filter-select">
            <span>Estado</span>
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['borrador', 'en_revision', 'cambios_solicitados', 'publicado', 'rechazado', 'archivado'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
        <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?><a class="filter-clear" href="<?= e(url('/admin/casos')) ?>">Limpiar</a><?php endif; ?>
    </form>

    <?php if ($cases === []): ?>
        <div class="admin-empty"><?= icon('file-text', 'icon') ?><p>No hay casos con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Caso</th>
                    <th>Autor</th>
                    <th>Cultivo</th>
                    <th>Estado</th>
                    <th>Publicación</th>
                    <th class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($cases as $case): ?>
                    <tr>
                        <td>
                            <div class="table-title">
                                <span class="table-emoji"><?= e($case['crop_emoji'] ?? '🐾') ?></span>
                                <div>
                                    <a href="<?= e(url('/admin/casos/' . $case['id'])) ?>"><?= e(excerpt($case['title'], 72)) ?></a>
                                    <small><?= e($case['folio'] ?? '') ?> · <?= e($case['final_diagnosis'] ?: 'Sin diagnóstico') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= e($case['author_name']) ?></td>
                        <td><?= e($case['crop_name']) ?></td>
                        <td><span class="badge badge-<?= e(status_tone($case['status'])) ?>"><?= e(status_label($case['status'])) ?></span></td>
                        <td>
                            <?php if (!empty($case['published_at'])): ?>
                                <?= e(format_date($case['published_at'], 'd/m/Y')) ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost btn-xs" href="<?= e(url('/admin/casos/' . $case['id'])) ?>"><?= icon('eye', 'icon icon-xs') ?> Revisar</a>
                                <?php if ($case['status'] === 'publicado'): ?>
                                    <a class="btn btn-ghost btn-xs" href="<?= e(url('/casos/' . $case['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external-link', 'icon icon-xs') ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>
