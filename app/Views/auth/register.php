<?php
/** @var array<int, array<string, mixed>> $states */
/** @var string $next */
$errors = \App\Core\Flash::errors();
?>
<section class="auth-section auth-section-register">
    <div class="container auth-grid auth-grid-wide">
        <div class="auth-panel">
            <span class="eyebrow">Nueva cuenta</span>
            <h1>Crea tu cuenta profesional</h1>
            <p class="auth-lead"><?= config('app.cedula.enabled') ? 'El registro es gratuito y está dirigido a ingenieros agrónomos y profesionales del sector agrícola con cédula profesional. Validamos tu cédula y tu nombre con el Registro Nacional de Profesionistas; tu cuenta queda en revisión hasta completar la verificación.' : 'El registro es gratuito y está dirigido a ingenieros agrónomos y profesionales del sector agrícola con cédula profesional. Revisamos tu cédula para cuidar la calidad de la comunidad; tu cuenta queda en revisión mientras la confirmamos.' ?></p>

            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>Revisa los siguientes campos</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('/registro')) ?>">
                <?= csrf_field() ?>
                <?php if (($next ?? '') === 'panel'): ?><input type="hidden" name="next" value="panel"><?php endif; ?>
                <?php if (config('app.cedula.enabled')): ?>
                    <div class="notice notice-info">
                        <?= icon('shield', 'icon') ?>
                        <div>
                            <strong>Verificación con la SEP</strong>
                            <p>Tu cédula y nombre se consultan en el Registro Nacional de Profesionistas de la SEP. Deben coincidir y corresponder a tu formación en ingeniería agronómica o en un área afín.</p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="name">Nombre completo <span class="req">*</span></label>
                        <input id="name" name="name" type="text" autocomplete="name" required value="<?= e(old('name')) ?>" placeholder="Ing. Ana Sofía Ramírez">
                    </div>
                    <div class="field">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="tu@correo.mx">
                    </div>
                </div>

                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="license_number">Cédula profesional <span class="req">*</span></label>
                        <input id="license_number" name="license_number" type="text" required value="<?= e(old('license_number')) ?>" placeholder="Ej. 12345678">
                        <span class="field-help">Se usa para verificar tu identidad profesional y no se muestra públicamente. <?= config('app.cedula.enabled') ? 'Debe coincidir con tu nombre en el Registro Nacional de Profesionistas.' : '' ?></span>
                    </div>
                    <div class="field">
                        <label for="state_code">Estado <span class="req">*</span></label>
                        <select id="state_code" name="state_code" required>
                            <option value="">Selecciona…</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= e($state['code']) ?>" <?= old('state_code') === $state['code'] ? 'selected' : '' ?>><?= e($state['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="institution">Institución, empresa o despacho <span class="req">*</span></label>
                    <input id="institution" name="institution" type="text" required value="<?= e(old('institution')) ?>" placeholder="Universidad, empresa o despacho">
                </div>

                <div class="field">
                    <label for="bio">Semblanza breve <span class="optional">(opcional)</span></label>
                    <textarea id="bio" name="bio" rows="3" maxlength="1200" placeholder="Especialidades, formación y cultivos que atiendes…"><?= e(old('bio')) ?></textarea>
                </div>

                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="password">Contraseña <span class="req">*</span></label>
                        <div class="password-field">
                            <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required placeholder="Mínimo 10 caracteres">
                            <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'password']); ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirmar contraseña <span class="req">*</span></label>
                        <div class="password-field">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required placeholder="Repite la contraseña">
                            <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'password_confirmation']); ?>
                        </div>
                    </div>
                </div>

                <label class="check-line">
                    <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>>
                    <span>Acepto los <a href="<?= e(url('/terminos')) ?>" target="_blank" rel="noopener">términos de uso</a>, el <a href="<?= e(url('/etica')) ?>" target="_blank" rel="noopener">código de ética</a> y el <a href="<?= e(url('/privacidad')) ?>" target="_blank" rel="noopener">aviso de privacidad</a>.</span>
                </label>

                <button class="btn btn-primary btn-block" type="submit">Crear mi cuenta <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </form>

            <p class="auth-switch">¿Ya tienes cuenta? <a href="<?= e(url('/acceder')) ?>">Inicia sesión</a> · <a href="<?= e(url('/panel/entrar')) ?>">Entrar al panel de campo</a></p>
        </div>

        <aside class="auth-aside auth-aside-register">
            <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Qué obtienes</span>
            <h2>Un espacio para compartir criterio con colegas.</h2>
            <ol class="onboarding-steps">
                <li><span>01</span><div><strong>Crea tu perfil</strong><p>Comparte tu institución, estado y especialidades.</p></div></li>
                <li><span>02</span><div><strong>Verifica tu cédula</strong><p><?= config('app.cedula.enabled') ? 'Contrastamos cédula y nombre con el Registro Nacional de Profesionistas. La cuenta queda en revisión mientras se confirma.' : 'Un administrador revisa tu registro para mantener la calidad. La cuenta queda en revisión mientras se confirma.' ?></p></div></li>
                <li><span>03</span><div><strong>Publica y conversa</strong><p>Documenta casos, recibe comentarios y aprende de otros.</p></div></li>
            </ol>
            <div class="auth-note">
                <?= icon('shield', 'icon icon-sm') ?>
                <p>Tus datos personales se tratan conforme a la LFPDPPP. Nunca publicamos tu cédula completa ni tu correo.</p>
            </div>
        </aside>
    </div>
</section>
