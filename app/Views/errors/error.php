<?php $status = (int) ($status ?? 500); ?>
<section class="error-page">
    <div class="container error-inner">
        <span class="error-code"><?= $status ?></span>
        <span class="error-icon"><?= icon($status === 404 ? 'search' : ($status === 403 ? 'lock' : 'alert-triangle'), 'icon') ?></span>
        <h1><?= $status === 404 ? 'No encontramos esta página' : ($status === 403 ? 'No tienes acceso a esta sección' : ($status === 419 ? 'Tu sesión expiró' : 'Algo no salió como esperábamos')) ?></h1>
        <p><?= e($message ?? 'Intenta de nuevo o vuelve al inicio.') ?></p>
        <div class="error-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Volver al inicio</a>
            <a class="btn btn-outline" href="<?= e(url('/casos')) ?>">Explorar casos</a>
        </div>
        <?php if ($status === 419): ?>
            <p class="error-hint">Recarga la página e inténtalo de nuevo. Si el problema persiste, cierra sesión y vuelve a entrar.</p>
        <?php endif; ?>
    </div>
</section>
