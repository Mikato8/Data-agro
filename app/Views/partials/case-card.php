<?php
/** @var array<string, mixed> $case */
$href = url('/casos/' . $case['slug']);
$hasCover = !empty($case['cover_image']);
$isDemo = !empty($case['is_demo']);
?>
<article class="case-card">
    <a class="case-card-media crops-<?= e($case['crop_code'] ?? 'maiz') ?>" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($hasCover): ?>
            <img src="<?= e(url('/uploads/cases/' . $case['cover_image'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="case-card-emoji"><?= e($case['crop_emoji'] ?? crop_emoji($case['crop_code'] ?? null)) ?></span>
            <span class="case-card-pattern"></span>
        <?php endif; ?>
        <span class="case-card-crop"><?= e($case['crop_name'] ?? 'Caso agronómico') ?></span>
        <?php if ($isDemo): ?><span class="badge badge-demo">Demostrativo</span><?php endif; ?>
    </a>
    <div class="case-card-body">
        <div class="case-card-meta">
            <span class="badge badge-soft"><?= e(outcome_label($case['outcome'] ?? 'desconocido')) ?></span>
            <?php if (!empty($case['state_name'])): ?>
                <span class="meta-dot"><?= icon('map-pin', 'icon icon-xs') ?> <?= e($case['state_name']) ?></span>
            <?php endif; ?>
        </div>
        <h3><a href="<?= e($href) ?>"><?= e($case['title']) ?></a></h3>
        <p class="case-card-diagnosis"><?= e(excerpt($case['final_diagnosis'] ?: ($case['chief_complaint'] ?? ''), 118)) ?></p>
        <div class="case-card-footer">
            <span class="case-author">
                <span class="avatar avatar-xs"><?= e(initials((string) ($case['author_name'] ?? 'AG'))) ?></span>
                <span><?= e($case['author_name'] ?? 'Autor') ?></span>
            </span>
            <span class="case-card-data">
                <?= icon('calendar', 'icon icon-xs') ?> <?= e(format_date($case['published_at'] ?? null, 'd/m/Y')) ?>
                <?php if (isset($case['comments_count'])): ?>
                    <span class="case-stat"><?= icon('message-circle', 'icon icon-xs') ?> <?= (int) $case['comments_count'] ?></span>
                <?php endif; ?>
            </span>
        </div>
    </div>
</article>
