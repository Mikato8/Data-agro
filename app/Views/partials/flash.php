<?php
/** @var array<int, array{type: string, message: string}> $flashMessages */
$icons = ['success' => 'check-circle', 'error' => 'alert-triangle', 'info' => 'info'];
?>
<?php if ($flashMessages !== []): ?>
    <div class="flash-stack container" aria-live="polite">
        <?php foreach ($flashMessages as $flash): ?>
            <?php $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info'; ?>
            <div class="flash flash-<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
                <?= icon($icons[$type] ?? 'info', 'icon') ?>
                <p><?= e($flash['message']) ?></p>
                <button type="button" class="flash-close" data-dismiss aria-label="Cerrar mensaje"><?= icon('close', 'icon icon-sm') ?></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
