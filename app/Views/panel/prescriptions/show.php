<?php
/** @var array<string, mixed> $prescription */
?>
<a class="panel-back" href="<?= e(url('/panel/recetas')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Recetas</a>
<div class="panel-page-head">
    <div><span class="panel-eyebrow">Receta · <?= e(format_date($prescription['created_at'], 'd/m/Y')) ?></span><h1><?= e($prescription['patient_name']) ?></h1><p><?= e($prescription['tutor_name']) ?> · <?= e(species_label((string) $prescription['species'])) ?></p></div>
    <div class="panel-head-actions"><button class="btn btn-outline btn-sm" type="button" data-print><?= icon('printer', 'icon icon-sm') ?> Imprimir</button><a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/' . (int) $prescription['patient_id'])) ?>">Ver paciente</a></div>
</div>

<article class="panel-card rx-sheet">
    <header class="rx-head">
        <div><strong><?= e((string) ($currentUser['institution'] ?? 'Clínica veterinaria')) ?></strong><span><?= e((string) ($currentUser['name'] ?? '')) ?></span></div>
        <div class="rx-date"><?= e(format_date($prescription['created_at'], 'd/m/Y H:i')) ?></div>
    </header>
    <div class="rx-patient">
        <div><span>Paciente</span><strong><?= e($prescription['patient_name']) ?></strong></div>
        <div><span>Especie / raza</span><strong><?= e(species_label((string) $prescription['species'])) ?> · <?= e($prescription['breed'] ?? '—') ?></strong></div>
        <div><span>Sexo</span><strong><?= e(ucfirst((string) ($prescription['sex'] ?? 'desconocido'))) ?></strong></div>
        <div><span>Peso</span><strong><?= $prescription['weight_kg'] !== null ? e((string) $prescription['weight_kg']) . ' kg' : '—' ?></strong></div>
    </div>
    <section class="rx-block"><h2>Diagnóstico</h2><p><?= nl2br(e($prescription['diagnosis'])) ?></p></section>
    <section class="rx-block"><h2>Medicamentos</h2><p class="rx-medications"><?= nl2br(e($prescription['medications'])) ?></p></section>
    <?php if (!empty($prescription['instructions'])): ?><section class="rx-block"><h2>Indicaciones</h2><p><?= nl2br(e($prescription['instructions'])) ?></p></section><?php endif; ?>
    <footer class="rx-foot"><span>Firma y sello</span><strong><?= e((string) ($currentUser['name'] ?? '')) ?></strong><small><?= e((string) ($currentUser['license_number'] ?? '')) ?></small></footer>
</article>
