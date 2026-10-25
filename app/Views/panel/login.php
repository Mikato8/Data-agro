<?php
$errors = \App\Core\Flash::errors();
?>
<section class="crm-auth">
    <div class="crm-auth-card">
        <span class="crm-eyebrow"><span></span> Clínica veterinaria</span>
        <h1>CRM de la Clínica Veterinaria</h1>
        <p class="crm-lead">Entra para administrar pacientes, citas, recetas, inventario y ventas.</p>

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
                <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="tu@clinica.mx">
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
            <button class="btn btn-primary btn-block" type="submit">Entrar</button>
        </form>

        <p class="crm-auth-register">¿Aún no tienes cuenta? <a href="<?= e(url('/panel/registro')) ?>">Regístrate como veterinario</a></p>
        <div class="crm-auth-links">
            <a href="<?= e(url('/recuperar')) ?>">¿Olvidaste tu contraseña?</a>
            <a href="<?= e(url('/')) ?>">Volver al portal</a>
        </div>
        <p class="crm-auth-foot">Clínica Veterinaria · CRM</p>
    </div>
</section>
