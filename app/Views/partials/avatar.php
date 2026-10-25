<?php
/** @var string $name */
/** @var string|null $class */
?>
<span class="avatar <?= e($class ?? '') ?>" title="<?= e($name) ?>" aria-hidden="true"><?= e(initials($name)) ?></span>
