<?php $errors = \App\Core\Flash::errors(); ?>
<section class="auth-section">
    <div class="container auth-grid">
        <div class="auth-panel">
            <span class="eyebrow">Bienvenido de nuevo</span>
            <h1>Accede a tu cuenta</h1>
            <p class="auth-lead">Continúa tus casos, consulta el archivo y participa en la discusión con colegas verificados.</p>

            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>No pudimos iniciar sesión</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('/acceder')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="tu@correo.mx">
                </div>
                <div class="field">
                    <div class="label-row">
                        <label for="password">Contraseña</label>
                        <a href="<?= e(url('/recuperar')) ?>">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Tu contraseña">
                        <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'password']); ?>
                    </div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Iniciar sesión <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </form>

            <p class="auth-switch">¿Aún no tienes cuenta? <a href="<?= e(url('/registro')) ?>">Regístrate como ingeniero agrónomo</a></p>
            <p class="auth-switch"><a href="<?= e(url('/panel/entrar')) ?>">Entrar al panel de campo</a></p>
        </div>

        <aside class="auth-aside">
            <div class="auth-aside-top">
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Comunidad profesional</span>
                <h2>La experiencia de campo se multiplica cuando se comparte.</h2>
                <p>Fichas de plagas y enfermedades, casos revisados por el comité editorial y un archivo que crece con cada aportación.</p>
            </div>
            <div class="auth-quote">
                <p>“Un caso bien escrito no sólo resuelve una duda: entrena el criterio de quien lo lee.”</p>
                <span>Comité editorial · AgroDiagnóstico</span>
            </div>
            <ul class="check-list check-list-light">
                <li><?= icon('check', 'icon icon-sm') ?> Publica casos con licencia y crédito.</li>
                <li><?= icon('check', 'icon icon-sm') ?> Recibe retroalimentación editorial.</li>
                <li><?= icon('check', 'icon icon-sm') ?> Accede a recursos de datos y apoyo diagnóstico.</li>
            </ul>
        </aside>
    </div>
</section>
