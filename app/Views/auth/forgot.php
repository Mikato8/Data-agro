<?php $errors = \App\Core\Flash::errors(); ?>
<section class="auth-section">
    <div class="container auth-grid">
        <div class="auth-panel">
            <span class="eyebrow">Recuperar acceso</span>
            <h1>Restablece tu contraseña</h1>
            <p class="auth-lead">Escribe el correo con el que te registraste. Si existe una cuenta asociada, enviaremos un enlace válido por 60 minutos para crear una nueva contraseña. Revisa también la carpeta de correo no deseado.</p>

            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('/recuperar')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="tu@correo.mx">
                </div>
                <button class="btn btn-primary btn-block" type="submit">Enviar enlace de recuperación</button>
            </form>

            <p class="auth-switch"><a href="<?= e(url('/acceder')) ?>">Volver a iniciar sesión</a></p>
        </div>

        <aside class="auth-aside">
            <div class="auth-aside-top">
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Seguridad</span>
                <h2>Tu cuenta, protegida.</h2>
                <p>Usamos contraseñas con hash, sesiones con cookies seguras y límites de intentos para proteger tu acceso.</p>
            </div>
            <ul class="check-list check-list-light">
                <li><?= icon('check', 'icon icon-sm') ?> Enlaces de recuperación de un solo uso.</li>
                <li><?= icon('check', 'icon icon-sm') ?> Vigencia de 60 minutos.</li>
                <li><?= icon('check', 'icon icon-sm') ?> Nunca te pediremos tu contraseña por correo.</li>
            </ul>
        </aside>
    </div>
</section>
