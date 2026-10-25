<?php
/** @var array<int, array{type: string, message: string}> $flashMessages */
?><!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Panel clínico') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <meta name="robots" content="<?= e($robots ?? 'noindex,nofollow') ?>">
    <meta name="theme-color" content="#0d5c4d">
    <link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/panel.css')) ?>">
</head>
<body class="panel-auth-body">
<?php \App\Core\View::partial('partials/flash', ['flashMessages' => $flashMessages]); ?>
<?= $content ?>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
