<?php
/** @var array<int, array<string, mixed>> $cropsList */
/** @var array<string, mixed> $crops */
/** @var array<string, array<int, array<string, mixed>>> $signGroups */
$totalSigns = 0;
foreach ($signGroups as $group) {
    $totalSigns += count($group);
}
?>
<section class="diagnostic-hero">
    <div class="container">
        <nav class="breadcrumb breadcrumb-light" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Apoyo diagnóstico</span>
        </nav>
        <div class="diagnostic-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Herramienta de orientación en campo</span>
                <h1>Del síntoma al diferencial,<br>sin saltarte el criterio.</h1>
                <p>Selecciona los hallazgos observados en el cultivo. El sistema compara el perfil con las fichas de plagas, enfermedades y deficiencias, y ordena los diferenciales por similitud.</p>
                <div class="diagnostic-hero-actions">
                    <a class="btn btn-sand btn-sm" href="<?= e(url('/apoyo-diagnostico/ia')) ?>"><?= icon('cpu', 'icon icon-sm') ?> Probar clasificador de riesgo</a>
                </div>
            </div>
            <div class="diagnostic-hero-stat">
                <strong><?= count($cropsList) ?></strong>
                <span>cultivos</span>
                <strong><?= $totalSigns ?></strong>
                <span>síntomas para <?= e($crops['name']) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container diagnostic-layout">
        <form class="diagnostic-form" method="post" action="<?= e(url('/apoyo-diagnostico')) ?>" data-diagnostic-form>
            <?= csrf_field() ?>
            <input type="hidden" name="crops" value="<?= e($crops['code']) ?>">

            <div class="diagnostic-step">
                <div class="diagnostic-step-head">
                    <span class="form-step">01</span>
                    <div>
                        <h2>Cultivo</h2>
                        <p>El catálogo de síntomas y diferenciales cambia según el cultivo.</p>
                    </div>
                </div>
                <div class="crops-tabs" role="tablist" aria-label="Cultivo">
                    <?php foreach ($cropsList as $item): ?>
                        <a class="crops-tab <?= $item['code'] === $crops['code'] ? 'is-active' : '' ?>" href="<?= e(url('/apoyo-diagnostico?crops=' . rawurlencode($item['code']))) ?>" role="tab" aria-selected="<?= $item['code'] === $crops['code'] ? 'true' : 'false' ?>">
                            <span><?= e($item['emoji']) ?></span>
                            <?= e($item['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="diagnostic-step">
                <div class="diagnostic-step-head">
                    <span class="form-step">02</span>
                    <div>
                        <h2>Síntomas y hallazgos</h2>
                        <p>Selecciona hasta 20. Prioriza los hallazgos que consideres más relevantes.</p>
                    </div>
                </div>

                <div class="symptom-search">
                    <?= icon('search', 'icon icon-sm') ?>
                    <input type="search" placeholder="Filtrar síntomas: clorosis, marchitez, manchas…" aria-label="Filtrar síntomas" data-symptom-filter>
                </div>

                <div class="symptom-groups" data-symptom-groups>
                    <?php foreach ($signGroups as $category => $symptoms): ?>
                        <section class="symptom-group" data-symptom-group>
                            <h3><?= e($category) ?> <span><?= count($symptoms) ?></span></h3>
                            <div class="symptom-options">
                                <?php foreach ($symptoms as $symptom): ?>
                                    <label class="symptom-option" data-symptom-option data-symptom-label="<?= e(mb_strtolower((string) $symptom['name'])) ?>">
                                        <input type="checkbox" name="symptoms[]" value="<?= (int) $symptom['id'] ?>" data-symptom>
                                        <span><?= e($symptom['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="diagnostic-actions">
                <div class="selected-counter">
                    <strong data-selected-count>0</strong>
                    <span>síntoma<?= 's' ?> seleccionado<?= 's' ?></span>
                </div>
                <button class="btn btn-primary" type="submit">Calcular diferenciales <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </div>
            <p class="diagnostic-disclaimer"><?= icon('alert-triangle', 'icon icon-xs') ?> Esta herramienta es de apoyo educativo. No emite diagnósticos ni sustituye la inspección de campo, el análisis de laboratorio o la revisión de un especialista.</p>
        </form>

        <aside class="diagnostic-sidebar">
            <div class="sidebar-card sidebar-sticky">
                <h2 class="sidebar-title"><?= icon('sprout', 'icon icon-sm') ?> Cómo se calcula</h2>
                <p>Comparamos los síntomas que seleccionaste con el perfil de cada problema fitosanitario. La coincidencia combina qué proporción de tus hallazgos explica el padecimiento y qué proporción de su perfil está presente.</p>
                <ol class="mini-steps">
                    <li><span>1</span> Coincidencia de tus síntomas.</li>
                    <li><span>2</span> Cobertura del perfil del problema.</li>
                    <li><span>3</span> Orden por puntaje y especificidad.</li>
                </ol>
                <div class="sidebar-divider"></div>
                <h3 class="sidebar-subtitle">Síntomas seleccionados</h3>
                <div class="selected-chips" data-selected-chips>
                    <span class="muted">Aún no has seleccionado síntomas.</span>
                </div>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('book-open', 'icon icon-sm') ?> Recuerda</h2>
                <p>Un diferencial no es un diagnóstico. Confirma con el historial de manejo, la inspección de campo, el laboratorio y la revisión de un especialista.</p>
                <a class="text-link" href="<?= e(url('/enfermedades')) ?>">Consultar fichas <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
        </aside>
    </div>
</section>
