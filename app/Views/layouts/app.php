<?php
/** @var array<string, mixed>|null $currentUser */
/** @var array<int, array{type: string, message: string}> $flashMessages */
$headerQuery = is_string($_GET['q'] ?? null) ? (string) $_GET['q'] : '';
?><!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AgroDiagnóstico') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <link rel="canonical" href="<?= e($canonical ?? url(current_path())) ?>">
    <meta name="robots" content="<?= e($robots ?? 'index,follow') ?>">
    <meta name="theme-color" content="#2f5a3d">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AgroDiagnóstico">
    <meta property="og:title" content="<?= e($title ?? 'AgroDiagnóstico') ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:url" content="<?= e($canonical ?? url(current_path())) ?>">
    <meta property="og:image" content="<?= e(url('/assets/img/og-cover.png')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="AgroDiagnóstico, inicio">
            <img class="brand-mark" src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="44" height="44">
            <span class="brand-text">
                <span class="brand-name">Agro</span>
                <span class="brand-sub">Diagnóstico</span>
            </span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
            <a class="<?= e(is_active('/casos')) ?>" href="<?= e(url('/casos')) ?>">Casos agronómicos</a>
            <a class="<?= e(is_active('/apoyo-diagnostico')) ?>" href="<?= e(url('/apoyo-diagnostico')) ?>">Apoyo diagnóstico</a>
            <a class="<?= e(is_active('/enfermedades')) ?>" href="<?= e(url('/enfermedades')) ?>">Plagas y enfermedades</a>
            <a class="<?= e(is_active('/panel')) ?>" href="<?= e(url('/panel')) ?>" title="CRM de parcelas y productores">CRM</a>
            <a class="<?= e(is_active('/recursos')) ?>" href="<?= e(url('/recursos')) ?>">Recursos</a>
            <a class="<?= e(is_active('/nosotros')) ?>" href="<?= e(url('/nosotros')) ?>">Nosotros</a>
            <div class="mobile-account">
                <?php if ($currentUser): ?>
                    <a class="btn btn-ghost btn-block" href="<?= e(url('/perfil')) ?>">Mi perfil</a>
                    <a class="btn btn-ghost btn-block" href="<?= e(url('/mis-casos')) ?>">Mis casos</a>
                    <?php if (can('moderar')): ?>
                        <a class="btn btn-dark btn-block" href="<?= e(url('/admin')) ?>">Panel editorial</a>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/salir')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-block" type="submit">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost btn-block" href="<?= e(url('/acceder')) ?>">Acceder</a>
                    <a class="btn btn-primary btn-block" href="<?= e(url('/registro')) ?>">Crear cuenta</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="header-actions">
            <form class="header-search" action="<?= e(url('/casos')) ?>" method="get" role="search">
                <?= icon('search', 'icon icon-sm') ?>
                <input type="search" name="q" value="<?= e($currentPath === '/casos' ? $headerQuery : '') ?>" placeholder="Buscar caso o síntoma…" aria-label="Buscar casos agronómicos">
            </form>

            <?php if ($currentUser): ?>
                <a class="header-link" href="<?= e(url('/mis-casos')) ?>">Mis casos</a>
                <details class="user-menu">
                    <summary>
                        <?= avatar_tag((string) $currentUser['name'], $currentUser['avatar_url'] ?? null, 'avatar-sm') ?>
                        <span class="user-menu-name"><?= e(first_name((string) $currentUser['name'])) ?></span>
                        <?= icon('chevron-down', 'icon icon-xs') ?>
                    </summary>
                    <div class="user-menu-panel">
                        <div class="user-menu-head">
                            <strong><?= e($currentUser['name']) ?></strong>
                            <span><?= e($currentUser['email']) ?></span>
                            <span class="badge badge-<?= $currentUser['status'] === 'verificado' ? 'success' : 'warning' ?>"><?= $currentUser['status'] === 'verificado' ? 'Verificado' : 'Por verificar' ?></span>
                        </div>
                        <a href="<?= e(url('/perfil')) ?>"><?= icon('user') ?> Mi perfil</a>
                        <a href="<?= e(url('/panel')) ?>"><?= icon('dashboard') ?> Panel de campo</a>
                        <a href="<?= e(url('/mis-casos')) ?>"><?= icon('folder') ?> Mis casos</a>
                        <a href="<?= e(url('/recursos/favoritos')) ?>"><?= icon('bookmark') ?> Recursos guardados</a>
                        <?php if (can('moderar')): ?>
                            <a href="<?= e(url('/admin')) ?>"><?= icon('dashboard') ?> Panel editorial</a>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/salir')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('logout') ?> Cerrar sesión</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a class="header-link" href="<?= e(url('/acceder')) ?>">Acceder</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/registro')) ?>">Crear cuenta</a>
            <?php endif; ?>
        </div>

        <button class="menu-toggle" type="button" data-menu-toggle aria-controls="main-nav" aria-expanded="false" aria-label="Abrir menú">
            <span class="menu-toggle-open"><?= icon('menu') ?></span>
            <span class="menu-toggle-close"><?= icon('close') ?></span>
        </button>
    </div>
</header>

<?php \App\Core\View::partial('partials/flash', ['flashMessages' => $flashMessages]); ?>

<main id="contenido" class="site-main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= e(url('/')) ?>">
                <img class="brand-mark" src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="44" height="44">
                <span class="brand-text">
                    <span class="brand-name">Agro</span>
                    <span class="brand-sub">Diagnóstico</span>
                </span>
            </a>
            <p>Una biblioteca colaborativa de casos agronómicos para ingenieros agrónomos de México. Aprende de la experiencia de colegas y comparte la tuya.</p>
            <span class="footer-stamp">Hecho en México · Español</span>
        </div>

        <div class="footer-col">
            <h2>Portal</h2>
            <a href="<?= e(url('/casos')) ?>">Casos agronómicos</a>
            <a href="<?= e(url('/apoyo-diagnostico')) ?>">Apoyo diagnóstico</a>
            <a href="<?= e(url('/apoyo-diagnostico/ia')) ?>">Riesgo fitosanitario</a>
            <a href="<?= e(url('/enfermedades')) ?>">Plagas y enfermedades</a>
            <a href="<?= e(url('/recursos')) ?>">Bibliotecas y recursos</a>
            <a href="<?= e(url('/agrodatos')) ?>">Datos abiertos</a>
        </div>

        <div class="footer-col">
            <h2>Comunidad</h2>
            <a href="<?= e(url('/registro')) ?>">Crear cuenta</a>
            <a href="<?= e(url('/acceder')) ?>">Acceder</a>
            <a href="<?= e(url('/panel')) ?>">Panel de campo</a>
            <a href="<?= e(url('/mis-casos')) ?>">Compartir un caso</a>
            <a href="<?= e(url('/nosotros')) ?>">Comité editorial</a>
        </div>

        <div class="footer-col">
            <h2>Legal</h2>
            <a href="<?= e(url('/etica')) ?>">Ética de publicación</a>
            <a href="<?= e(url('/privacidad')) ?>">Aviso de privacidad</a>
            <a href="<?= e(url('/terminos')) ?>">Términos de uso</a>
            <a href="<?= e(url('/salud')) ?>">Estado del servicio</a>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> AgroDiagnóstico. El contenido es educativo y no sustituye la valoración en campo ni las recomendaciones oficiales.</p>
        <p>Casos publicados bajo licencia del autor · Código del portal bajo licencia MIT.</p>
    </div>
</footer>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
