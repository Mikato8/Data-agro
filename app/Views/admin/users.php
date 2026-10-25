<?php
/** @var array<int, array<string, mixed>> $users */
/** @var int $total */
/** @var array<string, mixed> $filters */
/** @var \App\Core\Paginator $paginator */
?>
<div class="admin-page-head">
    <div>
        <span class="eyebrow">Comunidad</span>
        <h1>Usuarios</h1>
        <p><?= number_format($total) ?> cuenta<?= $total === 1 ? '' : 's' ?> registrada<?= $total === 1 ? '' : 's' ?>.</p>
    </div>
</div>

<div class="admin-card">
    <form class="admin-filter" method="get" action="<?= e(url('/admin/usuarios')) ?>">
        <div class="filter-search">
            <?= icon('search', 'icon icon-sm') ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre, correo o cédula…" aria-label="Buscar usuarios">
        </div>
        <label class="filter-select">
            <span>Estado</span>
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['pendiente' => 'Pendiente', 'verificado' => 'Verificado', 'suspendido' => 'Suspendido'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-select">
            <span>Rol</span>
            <select name="role">
                <option value="">Todos</option>
                <?php foreach (['agronomo' => 'Agrónomo', 'editor' => 'Editor', 'admin' => 'Administrador'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
    </form>

    <?php if ($users === []): ?>
        <div class="admin-empty"><?= icon('users', 'icon') ?><p>No hay usuarios con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Cédula</th>
                    <th>Estado</th>
                    <th>Casos</th>
                    <th>Verificación</th>
                    <th>Rol</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <?= avatar_tag((string) $user['name'], $user['avatar_url'] ?? null, 'avatar-xs') ?>
                                <div>
                                    <strong><?= e($user['name']) ?></strong>
                                    <small><?= e($user['email']) ?><?php if (!empty($user['state_name'])): ?> · <?= e($user['state_name']) ?><?php endif; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= e($user['license_number'] ?? '—') ?>
                            <a class="table-muted" href="https://www.cedulaprofesional.sep.gob.mx/" target="_blank" rel="noopener noreferrer">Consultar en SEP ↗</a>
                            <?php if (!empty($user['license_verified_at'])): ?>
                                <span class="badge badge-success" title="Validada con el Registro Nacional de Profesionistas">SEP</span>
                            <?php endif; ?>
                            <?php if (!empty($user['license_verified_title'])): ?>
                                <small class="table-muted"><?= e(excerpt($user['license_verified_title'], 48)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= $user['status'] === 'verificado' ? 'success' : ($user['status'] === 'suspendido' ? 'danger' : 'warning') ?>"><?= e(ucfirst($user['status'])) ?></span></td>
                        <td><?= (int) $user['cases_count'] ?></td>
                        <td>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/usuarios/' . $user['id'] . '/estado')) ?>">
                                <?= csrf_field() ?>
                                <select name="status" aria-label="Estado de <?= e($user['name']) ?>">
                                    <?php foreach (['pendiente' => 'Pendiente', 'verificado' => 'Verificado', 'suspendido' => 'Suspendido'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $user['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-ghost btn-xs" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/usuarios/' . $user['id'] . '/rol')) ?>">
                                <?= csrf_field() ?>
                                <select name="role" aria-label="Rol de <?= e($user['name']) ?>">
                                    <?php foreach (['agronomo' => 'Agrónomo', 'editor' => 'Editor', 'admin' => 'Administrador'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-ghost btn-xs" type="submit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>
