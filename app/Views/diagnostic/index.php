<?php
/** @var array<int, array<string, mixed>> $speciesList */
/** @var array<string, mixed> $species */
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
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Herramienta de orientación clínica</span>
                <h1>Del signo al diferencial,<br>sin saltarte el criterio.</h1>
                <p>Selecciona los hallazgos clínicos del paciente. El sistema compara su perfil con las fichas de enfermedades y ordena los diferenciales por similitud, mostrando qué signos clave faltan explorar.</p>
                <div class="diagnostic-hero-actions">
                    <a class="btn btn-sand btn-sm" href="<?= e(url('/apoyo-diagnostico/ia')) ?>"><?= icon('cpu', 'icon icon-sm') ?> Probar IA de urgencia</a>
                </div>
            </div>
            <div class="diagnostic-hero-stat">
                <strong><?= count($speciesList) ?></strong>
                <span>especies</span>
                <strong><?= $totalSigns ?></strong>
                <span>signos para <?= e($species['name']) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container diagnostic-layout">
        <form class="diagnostic-form" method="post" action="<?= e(url('/apoyo-diagnostico')) ?>" data-diagnostic-form>
            <?= csrf_field() ?>
            <input type="hidden" name="species" value="<?= e($species['code']) ?>">

            <div class="diagnostic-step">
                <div class="diagnostic-step-head">
                    <span class="form-step">01</span>
                    <div>
                        <h2>Especie del paciente</h2>
                        <p>El catálogo de signos y diferenciales cambia según la especie.</p>
                    </div>
                </div>
                <div class="species-tabs" role="tablist" aria-label="Especie">
                    <?php foreach ($speciesList as $item): ?>
                        <a class="species-tab <?= $item['code'] === $species['code'] ? 'is-active' : '' ?>" href="<?= e(url('/apoyo-diagnostico?species=' . rawurlencode($item['code']))) ?>" role="tab" aria-selected="<?= $item['code'] === $species['code'] ? 'true' : 'false' ?>">
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
                        <h2>Signos y hallazgos</h2>
                        <p>Selecciona hasta 20. Prioriza los hallazgos que consideres más relevantes.</p>
                    </div>
                </div>

                <div class="sign-search">
                    <?= icon('search', 'icon icon-sm') ?>
                    <input type="search" placeholder="Filtrar signos: vómito, ictericia, cojera…" aria-label="Filtrar signos clínicos" data-sign-filter>
                </div>

                <div class="sign-groups" data-sign-groups>
                    <?php foreach ($signGroups as $category => $signs): ?>
                        <section class="sign-group" data-sign-group>
                            <h3><?= e($category) ?> <span><?= count($signs) ?></span></h3>
                            <div class="sign-options">
                                <?php foreach ($signs as $sign): ?>
                                    <label class="sign-option" data-sign-option data-sign-label="<?= e(mb_strtolower((string) $sign['name'])) ?>">
                                        <input type="checkbox" name="signs[]" value="<?= (int) $sign['id'] ?>" data-sign>
                                        <span><?= e($sign['name']) ?></span>
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
                    <span>signo<?= 's' ?> seleccionado<?= 's' ?></span>
                </div>
                <button class="btn btn-primary" type="submit">Calcular diferenciales <?= icon('arrow-right', 'icon icon-sm') ?></button>
            </div>
            <p class="diagnostic-disclaimer"><?= icon('alert-triangle', 'icon icon-xs') ?> Esta herramienta es de apoyo educativo. No emite diagnósticos ni sustituye la valoración clínica, las pruebas de laboratorio o la imagenología.</p>
        </form>

        <aside class="diagnostic-sidebar">
            <div class="sidebar-card sidebar-sticky">
                <h2 class="sidebar-title"><?= icon('stethoscope', 'icon icon-sm') ?> Cómo se calcula</h2>
                <p>Comparamos los signos que seleccionaste con el perfil de cada enfermedad. La coincidencia combina qué proporción de tus hallazgos explica el padecimiento y qué proporción de su perfil está presente.</p>
                <ol class="mini-steps">
                    <li><span>1</span> Coincidencia de tus signos.</li>
                    <li><span>2</span> Cobertura del perfil de la enfermedad.</li>
                    <li><span>3</span> Orden por puntaje y especificidad.</li>
                </ol>
                <div class="sidebar-divider"></div>
                <h3 class="sidebar-subtitle">Signos seleccionados</h3>
                <div class="selected-chips" data-selected-chips>
                    <span class="muted">Aún no has seleccionado signos.</span>
                </div>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('book-open', 'icon icon-sm') ?> Recuerda</h2>
                <p>Un diferencial no es un diagnóstico. Confirma con historia clínica, exploración, laboratorio e imagen según cada caso.</p>
                <a class="text-link" href="<?= e(url('/enfermedades')) ?>">Consultar fichas <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
        </aside>
    </div>
</section>
