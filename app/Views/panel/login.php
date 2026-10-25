<?php
$errors = \App\Core\Flash::errors();
?>
<section class="crm-auth">
    <div class="crm-auth-shell">
        <aside class="crm-auth-aside">
            <a class="crm-auth-brand" href="<?= e(url('/')) ?>">
                <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="42" height="42">
                <span><strong>AgroDiagnóstico</strong><small>Panel de campo</small></span>
            </a>

            <div class="crm-auth-pitch">
                <span class="crm-auth-eyebrow">CRM agronómico</span>
                <h2>El campo,<br>en orden.</h2>
                <p>Parcelas, productores, visitas, recomendaciones, insumos y ventas en un solo lugar.</p>
                <ul class="crm-auth-list">
                    <li><?= icon('map-pin', 'icon icon-sm') ?> Parcelas y productores</li>
                    <li><?= icon('calendar', 'icon icon-sm') ?> Agenda de visitas</li>
                    <li><?= icon('file-text', 'icon icon-sm') ?> Recomendaciones imprimibles</li>
                    <li><?= icon('trending-up', 'icon icon-sm') ?> Inventario y ventas</li>
                </ul>
            </div>

            <p class="crm-auth-aside-foot">Hecho en México · para ingenieros agrónomos</p>
        </aside>

        <div class="crm-auth-card">
            <span class="crm-eyebrow"><span></span> Acceso profesional</span>
            <h1>Entra a tu panel</h1>
            <p class="crm-lead">Usa la misma cuenta del portal para administrar tu operación de campo.</p>

            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>No pudimos iniciar sesión</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endif; ?>

            <form class="crm-auth-form" method="post" action="<?= e(url('/panel/entrar')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="tu@campo.mx">
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Tu contraseña">
                        <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'password']); ?>
                    </div>
                </div>
                <label class="crm-remember">
                    <input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
                    <span>Mantener la sesión abierta</span>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Entrar al panel <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </form>

            <p class="crm-auth-register">¿Aún no tienes cuenta? <a href="<?= e(url('/panel/registro')) ?>">Regístrate como agrónomo</a></p>
            <div class="crm-auth-links">
                <a href="<?= e(url('/recuperar')) ?>">¿Olvidaste tu contraseña?</a>
                <a href="<?= e(url('/')) ?>">Volver al portal</a>
            </div>
            <p class="crm-auth-foot">AgroDiagnóstico · Panel de campo</p>
        </div>
    </div>
</section>
