<?php
/** @var string $id */
?>
<button class="password-toggle" type="button" data-password-toggle aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="<?= e($id) ?>" title="Mostrar u ocultar contraseña">
    <span class="password-toggle-show"><?= icon('eye', 'icon icon-sm') ?></span>
    <span class="password-toggle-hide"><?= icon('eye-off', 'icon icon-sm') ?></span>
</button>
