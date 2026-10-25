<?php
/** @var \App\Core\Paginator $paginator */
?>
<?php if ($paginator->hasPages()): ?>
    <nav class="pagination" aria-label="Paginación">
        <?php if ($paginator->page() > 1): ?>
            <a class="page-link page-prev" href="<?= e($paginator->url($paginator->page() - 1)) ?>" rel="prev"><?= icon('chevron-left', 'icon icon-sm') ?> Anterior</a>
        <?php else: ?>
            <span class="page-link page-prev is-disabled"><?= icon('chevron-left', 'icon icon-sm') ?> Anterior</span>
        <?php endif; ?>

        <div class="page-numbers">
            <?php foreach ($paginator->pages() as $page): ?>
                <?php if ($page === '…'): ?>
                    <span class="page-ellipsis">…</span>
                <?php elseif ((int) $page === $paginator->page()): ?>
                    <span class="page-number is-current" aria-current="page"><?= (int) $page ?></span>
                <?php else: ?>
                    <a class="page-number" href="<?= e($paginator->url((int) $page)) ?>"><?= (int) $page ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($paginator->page() < $paginator->lastPage()): ?>
            <a class="page-link page-next" href="<?= e($paginator->url($paginator->page() + 1)) ?>" rel="next">Siguiente <?= icon('chevron-right', 'icon icon-sm') ?></a>
        <?php else: ?>
            <span class="page-link page-next is-disabled">Siguiente <?= icon('chevron-right', 'icon icon-sm') ?></span>
        <?php endif; ?>
    </nav>
<?php endif; ?>
