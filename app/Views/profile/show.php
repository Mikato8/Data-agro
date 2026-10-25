<?php
/** @var array<string, mixed> $user */
/** @var array<int, array<string, mixed>> $states */
/** @var int $caseCount */
/** @var int $publishedCount */
/** @var int $commentCount */
$errors = \App\Core\Flash::errors();
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Mi perfil</span>
        </nav>
        <div class="profile-hero">
            <?= avatar_tag((string) $user['name'], $user['avatar_url'] ?? null, 'avatar-xl') ?>
            <div class="profile-hero-main">
                <div class="case-kicker">
                    <span class="badge badge-<?= $user['status'] === 'verificado' ? 'success' : 'warning' ?>"><?= $user['status'] === 'verificado' ? 'Cuenta verificada' : 'Verificación pendiente' ?></span>
                    <span class="badge badge-soft"><?= e((string) config('app.roles.' . $user['role'], $user['role'])) ?></span>
                </div>
                <h1><?= e($user['name']) ?></h1>
                <p><?= e($user['institution'] ?? 'Ingeniero agrónomo') ?><?php if (!empty($user['state_name'])): ?> · <?= e($user['state_name']) ?><?php endif; ?></p>
            </div>
            <div class="profile-hero-stats">
                <div><strong><?= (int) $caseCount ?></strong><span>casos</span></div>
                <div><strong><?= (int) $publishedCount ?></strong><span>publicados</span></div>
                <div><strong><?= (int) $commentCount ?></strong><span>comentarios</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container profile-layout">
        <div class="profile-main">
            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>Revisa los siguientes campos</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endif; ?>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">01</span>
                    <div>
                        <h2>Datos profesionales</h2>
                        <p>Tu nombre, institución y semblanza se muestran en tu perfil y junto a los casos que publicas.</p>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/perfil')) ?>">
                    <?= csrf_field() ?>
                    <div class="field-grid field-grid-2">
                        <div class="field">
                            <label for="name">Nombre completo</label>
                            <input id="name" name="name" type="text" required maxlength="120" value="<?= e(old('name', $user['name'])) ?>">
                        </div>
                        <div class="field">
                            <label for="email">Correo electrónico</label>
                            <input id="email" type="email" value="<?= e($user['email']) ?>" disabled>
                            <span class="field-help">El correo no se puede cambiar desde aquí. Escríbenos si necesitas actualizarlo.</span>
                        </div>
                    </div>
                    <div class="field-grid field-grid-2">
                        <div class="field">
                            <label for="license_number">Cédula profesional</label>
                            <input id="license_number" name="license_number" type="text" required maxlength="40" value="<?= e(old('license_number', $user['license_number'])) ?>">
                            <span class="field-help">No se muestra públicamente; el comité editorial la usa para verificar tu identidad profesional.</span>
                        </div>
                        <div class="field">
                            <label for="state_code">Estado</label>
                            <select id="state_code" name="state_code" required>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= e($state['code']) ?>" <?= old('state_code', $user['state_code']) === $state['code'] ? 'selected' : '' ?>><?= e($state['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="institution">Institución, empresa o despacho</label>
                        <input id="institution" name="institution" type="text" required maxlength="160" value="<?= e(old('institution', $user['institution'])) ?>">
                    </div>
                    <div class="field">
                        <label for="bio">Semblanza</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="1200" placeholder="Especialidades, formación y cultivos que atiendes…"><?= e(old('bio', $user['bio'])) ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                </form>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">02</span>
                    <div>
                        <h2>Seguridad</h2>
                        <p>Cambia tu contraseña periódicamente y no la compartas.</p>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/perfil/password')) ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="current_password">Contraseña actual</label>
                        <div class="password-field">
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                            <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'current_password']); ?>
                        </div>
                    </div>
                    <div class="field-grid field-grid-2">
                        <div class="field">
                            <label for="new_password">Nueva contraseña</label>
                            <div class="password-field">
                                <input id="new_password" name="password" type="password" autocomplete="new-password" minlength="10" required>
                                <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'new_password']); ?>
                            </div>
                        </div>
                        <div class="field">
                            <label for="new_password_confirmation">Confirmar nueva contraseña</label>
                            <div class="password-field">
                                <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>
                                <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'new_password_confirmation']); ?>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-dark" type="submit">Actualizar contraseña</button>
                </form>
            </section>

            <section class="form-section form-section-danger">
                <div class="form-section-head">
                    <span class="form-step">03</span>
                    <div>
                        <h2>Eliminar mi cuenta</h2>
                        <p>Esta acción cierra tu cuenta y anonimiza tus datos personales. No se puede deshacer.</p>
                    </div>
                </div>
                <div class="danger-note">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>Qué pasa con tu contenido</strong>
                        <p>Tus casos publicados y comentarios permanecerán en el archivo, pero aparecerán como <strong>“Cuenta eliminada”</strong>. Tus borradores y datos de perfil dejarán de ser accesibles. Si necesitas conservar una copia, descarga tus casos antes de continuar.</p>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/perfil/eliminar')) ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="delete_password">Confirma tu contraseña</label>
                        <div class="password-field">
                            <input id="delete_password" name="password" type="password" autocomplete="current-password" required>
                            <?php \App\Core\View::partial('partials/password-toggle', ['id' => 'delete_password']); ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="delete_confirmation">Escribe <strong>ELIMINAR</strong> para confirmar</label>
                        <input id="delete_confirmation" name="confirmation" type="text" required maxlength="8" autocomplete="off" placeholder="ELIMINAR">
                    </div>
                    <button class="btn btn-danger" type="submit"><?= icon('trash', 'icon icon-sm') ?> Eliminar mi cuenta definitivamente</button>
                </form>
            </section>
        </div>

        <aside class="form-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('user', 'icon icon-sm') ?> Foto de perfil</h2>
                <div class="avatar-upload-preview" data-avatar-preview>
                    <?= avatar_tag((string) $user['name'], $user['avatar_url'] ?? null, 'avatar-lg') ?>
                </div>
                <form class="avatar-upload-form" method="post" action="<?= e(url('/perfil/avatar')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="btn btn-outline btn-sm" for="avatar">Elegir imagen</label>
                    <input class="sr-only" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required data-avatar-input>
                    <span class="avatar-filename" data-avatar-filename>JPG, PNG o WebP · máx. 5 MB</span>
                    <button class="btn btn-primary btn-sm" type="submit">Subir foto</button>
                </form>
                <?php if (!empty($user['avatar_url'])): ?>
                    <form method="post" action="<?= e(url('/perfil/avatar/eliminar')) ?>" class="avatar-remove">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-sm" type="submit">Quitar foto</button>
                    </form>
                <?php endif; ?>
                <p class="avatar-upload-help">Se recorta en círculo. No uses imágenes con personas identificables, documentos o datos de parcelas.</p>
            </div>

            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('shield', 'icon icon-sm') ?> Verificación profesional</h2>
                <?php if ($user['status'] === 'verificado'): ?>
                    <p>Tu cédula fue verificada por el comité editorial. Puedes enviar casos a revisión y comentar.</p>
                    <span class="badge badge-success"><?= icon('check-circle', 'icon icon-xs') ?> Verificado</span>
                <?php elseif ($user['status'] === 'pendiente'): ?>
                    <p>Tu cuenta está en revisión. El equipo editorial verificará tu cédula profesional. Mientras tanto puedes guardar borradores.</p>
                    <span class="badge badge-warning">Pendiente</span>
                <?php else: ?>
                    <p>Tu cuenta está suspendida. Contacta al comité editorial para más información.</p>
                    <span class="badge badge-danger">Suspendida</span>
                <?php endif; ?>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Tu actividad</h2>
                <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('/mis-casos')) ?>">Ir a mis casos</a>
                <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso</a>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('lock', 'icon icon-sm') ?> Privacidad</h2>
                <p>Tu correo y cédula no se muestran públicamente. Consulta el <a href="<?= e(url('/privacidad')) ?>">aviso de privacidad</a>.</p>
            </div>
        </aside>
    </div>
</section>
