<?php
/** @var array<int, array<string, mixed>> $resources */
?>
<div class="admin-page-head">
    <div>
        <span class="eyebrow">Directorio</span>
        <h1>Recursos externos</h1>
        <p>Datasets, APIs y guías que se muestran en el portal.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/recursos/nuevo')) ?>"><?= icon('plus', 'icon icon-xs') ?> Nuevo recurso</a>
    </div>
</div>

<div class="admin-card">
    <?php if ($resources === []): ?>
        <div class="admin-empty"><?= icon('database', 'icon') ?><p>Aún no hay recursos. Crea el primero.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Recurso</th>
                    <th>Tipo</th>
                    <th>Acceso</th>
                    <th>Organización</th>
                    <th class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td>
                            <div class="table-title">
                                <span class="table-emoji"><?= icon($resource['category'] === 'dataset' ? 'database' : ($resource['category'] === 'api' ? 'activity' : 'folder'), 'icon icon-sm') ?></span>
                                <div>
                                    <a href="<?= e(url('/recursos/' . $resource['slug'])) ?>" target="_blank" rel="noopener noreferrer"><strong><?= e($resource['title']) ?></strong></a>
                                    <small><a href="<?= e($resource['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e(excerpt($resource['url'], 55)) ?></a></small>
                                </div>
                            </div>
                        </td>
                        <td><?= e(category_label($resource['category'])) ?></td>
                        <td><span class="badge badge-<?= $resource['access'] === 'abierto' ? 'success' : 'warning' ?>"><?= e(access_label($resource['access'])) ?></span></td>
                        <td><?= e($resource['organization'] ?? '—') ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost btn-xs" href="<?= e(url('/recursos/' . $resource['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon icon-xs') ?> Ficha</a>
                                <a class="btn btn-ghost btn-xs" href="<?= e(url('/admin/recursos/' . $resource['id'] . '/editar')) ?>"><?= icon('edit', 'icon icon-xs') ?> Editar</a>
                                <form method="post" action="<?= e(url('/admin/recursos/' . $resource['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar este recurso del directorio?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs link-danger" type="submit"><?= icon('trash', 'icon icon-xs') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
