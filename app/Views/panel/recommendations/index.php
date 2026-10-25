<?php
/** @var array<int, array<string, mixed>> $recommendations */
/** @var array<int, array<string, mixed>> $plots */
/** @var int $plotId */
?>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Historial de campo</span><h1>Recomendaciones</h1><p>Insumos y dosis e indicaciones emitidas por la parcela.</p></div>
    <div class="panel-head-actions"><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/recomendaciones/nueva' . ($plotId > 0 ? '?plot_id=' . $plotId : ''))) ?>"><?= icon('plus', 'icon icon-sm') ?> Nueva recomendación</a></div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/recomendaciones')) ?>">
        <select name="plot_id" aria-label="Cultivo">
            <option value="">Todos los cultivos</option>
            <?php foreach ($plots as $plot): ?><option value="<?= (int) $plot['id'] ?>" <?= $plotId === (int) $plot['id'] ? 'selected' : '' ?>><?= e($plot['name']) ?> · <?= e($plot['producer_name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-dark btn-sm" type="submit">Filtrar</button>
    </form>
</div>

<?php if ($recommendations === []): ?>
    <div class="panel-card"><div class="panel-empty"><span class="panel-empty-icon"><?= icon('file-text', 'icon') ?></span><p>No hay recomendaciones registradas.</p><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/recomendaciones/nueva')) ?>">Crear recomendación</a></div></div>
<?php else: ?>
    <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>Fecha</th><th>Cultivo</th><th>Productor</th><th>Diagnóstico</th><th></th></tr></thead><tbody>
    <?php foreach ($recommendations as $recommendation): ?>
        <tr><td><?= e(format_date($recommendation['created_at'], 'd/m/Y H:i')) ?></td><td><a href="<?= e(url('/panel/parcelas/' . (int) $recommendation['plot_id'])) ?>"><?= e($recommendation['plot_name']) ?></a><small><?= e(crops_label((string) $recommendation['crop'])) ?></small></td><td><?= e($recommendation['producer_name']) ?></td><td><?= e(excerpt($recommendation['diagnosis'], 80)) ?></td><td><a class="panel-text-link" href="<?= e(url('/panel/recomendaciones/' . (int) $recommendation['id'])) ?>">Abrir <?= icon('arrow-right', 'icon icon-xs') ?></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
