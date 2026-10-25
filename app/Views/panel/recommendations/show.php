<?php
/** @var array<string, mixed> $recommendation */
?>
<a class="panel-back" href="<?= e(url('/panel/recomendaciones')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Recomendaciones</a>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Recomendación · <?= e(format_date($recommendation['created_at'], 'd/m/Y')) ?></span><h1><?= e($recommendation['plot_name']) ?></h1><p><?= e($recommendation['producer_name']) ?> · <?= e(crops_label((string) $recommendation['crop'])) ?></p></div>
    <div class="panel-head-actions"><button class="btn btn-outline btn-sm" type="button" data-print><?= icon('printer', 'icon icon-sm') ?> Imprimir</button><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/parcelas/' . (int) $recommendation['plot_id'])) ?>">Ver parcela</a></div>
</div>

<article class="panel-card rx-sheet">
    <header class="rx-head">
        <div><strong><?= e((string) ($currentUser['institution'] ?? 'Parcela agronómica')) ?></strong><span><?= e((string) ($currentUser['name'] ?? '')) ?></span></div>
        <div class="rx-date"><?= e(format_date($recommendation['created_at'], 'd/m/Y H:i')) ?></div>
    </header>
    <div class="rx-plot">
        <div><span>Parcela</span><strong><?= e($recommendation['plot_name']) ?></strong></div>
        <div><span>Cultivo / variedad</span><strong><?= e(crops_label((string) $recommendation['crop'])) ?> · <?= e($recommendation['variety'] ?? '—') ?></strong></div>
        <div><span>Superficie</span><strong><?= $recommendation['area_ha'] !== null ? e((string) $recommendation['area_ha']) . ' ha' : '—' ?></strong></div>
        <div><span>Municipio</span><strong><?= e($recommendation['municipality'] ?? '—') ?></strong></div>
    </div>
    <section class="rx-block"><h2>Diagnóstico</h2><p><?= nl2br(e($recommendation['diagnosis'])) ?></p></section>
    <section class="rx-block"><h2>Insumos y dosis</h2><p class="rx-inputs"><?= nl2br(e($recommendation['inputs_prescribed'])) ?></p></section>
    <?php if (!empty($recommendation['instructions'])): ?><section class="rx-block"><h2>Indicaciones</h2><p><?= nl2br(e($recommendation['instructions'])) ?></p></section><?php endif; ?>
    <footer class="rx-foot"><span>Firma y sello</span><strong><?= e((string) ($currentUser['name'] ?? '')) ?></strong><small><?= e((string) ($currentUser['license_number'] ?? '')) ?></small></footer>
</article>
