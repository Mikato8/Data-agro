<?php
/** @var array<string, mixed>|null $currentUser */
/** @var array<int, array{type: string, message: string}> $flashMessages */
$path = current_path();
$isDashboard = $path === '/panel';
$nav = [
    ['/panel', 'dashboard', 'Resumen', $isDashboard],
    ['/panel/pacientes', 'paw', 'Pacientes', str_starts_with($path, '/panel/pacientes')],
    ['/panel/citas', 'calendar', 'Citas', str_starts_with($path, '/panel/citas')],
    ['/panel/recetas', 'file-text', 'Recetas', str_starts_with($path, '/panel/recetas')],
    ['/panel/inventario', 'folder', 'Inventario', str_starts_with($path, '/panel/inventario')],
    ['/panel/ventas', 'trending-up', 'Ventas', str_starts_with($path, '/panel/ventas')],
];
?><!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Panel clínico') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0d5c4d">
    <link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/panel.css')) ?>">
</head>
<body class="panel-body <?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#panel-content">Saltar al contenido</a>
<div class="panel-shell">
    <aside class="panel-sidebar" id="panel-sidebar">
        <a class="panel-brand" href="<?= e(url('/panel')) ?>">
            <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="36" height="36">
            <span><strong>Clínica</strong><small>CRM veterinario</small></span>
        </a>
        <nav class="panel-nav" aria-label="Navegación del panel">
            <?php foreach ($nav as [$href, $iconName, $label, $active]): ?>
                <a class="<?= $active ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"><?= icon($iconName, 'icon icon-sm') ?> <?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="panel-sidebar-foot">
            <a href="<?= e(url('/')) ?>"><?= icon('globe', 'icon icon-sm') ?> Volver al portal</a>
            <a href="<?= e(url('/perfil')) ?>"><?= icon('user', 'icon icon-sm') ?> Mi perfil</a>
            <form method="post" action="<?= e(url('/panel/salir')) ?>">
                <?= csrf_field() ?>
                <button type="submit"><?= icon('logout', 'icon icon-sm') ?> Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="panel-main">
        <header class="panel-topbar">
            <button class="panel-menu-toggle" type="button" data-panel-toggle aria-controls="panel-sidebar" aria-expanded="false" aria-label="Abrir menú del panel"><?= icon('menu', 'icon') ?></button>
            <div class="panel-topbar-title">
                <span class="panel-clinic-label"><?= icon('stethoscope', 'icon icon-xs') ?> <?= e((string) ($currentUser['institution'] ?? 'Mi clínica')) ?></span>
            </div>
            <div class="panel-user">
                <?= avatar_tag((string) $currentUser['name'], $currentUser['avatar_url'] ?? null, 'avatar-sm') ?>
                <span><?= e(first_name((string) $currentUser['name'])) ?></span>
            </div>
        </header>

        <?php \App\Core\View::partial('partials/flash', ['flashMessages' => $flashMessages]); ?>
        <main class="panel-content" id="panel-content">
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
