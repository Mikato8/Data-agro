<?php
/** @var array<string, mixed> $problem */
$signGroups = [];
foreach ($problem['symptoms'] as $symptom) {
    $signGroups[(string) $symptom['category']][] = $symptom;
}
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/enfermedades')) ?>">Plagas y enfermedades</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span><?= e($problem['name']) ?></span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <div class="case-kicker">
                    <span class="badge badge-soft"><?= e($problem['category']) ?></span>
                    <span class="badge badge-risk-<?= e($problem['risk']) ?>">Riesgo <?= e(risk_label($problem['risk'])) ?></span>
                    <?php if ($problem['reportable']): ?><span class="badge badge-warning"><?= icon('alert-triangle', 'icon icon-xs') ?> Alerta cuarentenaria</span><?php endif; ?>
                </div>
                <h1><?= e($problem['name']) ?></h1>
                <?php if (!empty($problem['scientific_name'])): ?><p class="scientific scientific-lg"><?= e($problem['scientific_name']) ?></p><?php endif; ?>
                <p class="page-hero-lead"><?= e($problem['summary']) ?></p>
            </div>
            <div class="page-hero-aside">
                <a class="btn btn-primary btn-sm" href="<?= e(url('/apoyo-diagnostico?crops=' . rawurlencode((string) ($problem['crops'][0]['code'] ?? 'maiz')))) ?>"><?= icon('sprout', 'icon icon-xs') ?> Buscar diferenciales</a>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/casos?q=' . rawurlencode((string) $problem['name']))) ?>">Casos relacionados</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container problem-detail-layout">
        <article class="problem-detail">
            <section class="problem-block">
                <h2>Resumen técnico</h2>
                <p><?= nl2br(e($problem['summary'])) ?></p>
            </section>

            <?php foreach ([
                'Etiología y transmisión' => $problem['etiology'],
                'Diagnóstico' => $problem['diagnosis'],
                'Manejo recomendado' => $problem['management'],
                'Prevención y control' => $problem['prevention'],
                'Pronóstico' => $problem['prognosis'],
            ] as $heading => $content): ?>
                <?php if (trim((string) $content) !== ''): ?>
                    <section class="problem-block">
                        <h2><?= e($heading) ?></h2>
                        <p><?= nl2br(e($content)) ?></p>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <section class="problem-block">
                <h2>Síntomas clave</h2>
                <p class="problem-block-lead">Los síntomas con mayor peso orientan la sospecha, pero ninguno es patognomónico por sí solo.</p>
                <div class="symptom-profile">
                    <?php foreach ($signGroups as $category => $symptoms): ?>
                        <div class="symptom-profile-group">
                            <h3><?= e($category) ?></h3>
                            <div class="symptom-profile-list">
                                <?php foreach ($symptoms as $symptom): ?>
                                    <div class="symptom-profile-item">
                                        <span><?= e($symptom['name']) ?><?php if ($symptom['is_key'] === true || $symptom['is_key'] === 't'): ?><span class="key-mark">Clave</span><?php endif; ?></span>
                                        <span class="weight-bar" aria-label="Peso <?= (int) $symptom['weight'] ?> de 5"><span style="width: <?= ((int) $symptom['weight'] / 5) * 100 ?>%"></span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if (!empty($problem['references_text'])): ?>
                <section class="problem-block problem-references">
                    <h2>Referencias</h2>
                    <p><?= nl2br(e($problem['references_text'])) ?></p>
                </section>
            <?php endif; ?>

            <div class="notice notice-info">
                <?= icon('info', 'icon') ?>
                <div>
                    <strong>Contenido educativo.</strong>
                    <p>Esta ficha resume información general y no sustituye el criterio técnico ni las guías oficiales. Verifica protocolos, dosis y regulación local antes de tomar decisiones terapéuticas.</p>
                </div>
            </div>
        </article>

        <aside class="case-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('leaf', 'icon icon-sm') ?> Cultivos afectados</h2>
                <div class="crops-chip-list">
                    <?php foreach ($problem['crops'] as $crops): ?>
                        <span class="crops-pill crops-<?= e($crops['code']) ?>"><?= e($crops['emoji']) ?> <?= e($crops['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Datos de la ficha</h2>
                <dl class="plot-data">
                    <div><dt>Categoría</dt><dd><?= e($problem['category']) ?></dd></div>
                    <div><dt>Nivel de riesgo</dt><dd><?= e(risk_label($problem['risk'])) ?></dd></div>
                    <div><dt>Alerta cuarentenaria</dt><dd><?= $problem['reportable'] ? 'Sí' : 'No' ?></dd></div>
                    <div><dt>Síntomas</dt><dd><?= count($problem['symptoms']) ?></dd></div>
                    <?php if (!empty($problem['reference_code'])): ?><div><dt>Clave de referencia</dt><dd><?= e($problem['reference_code']) ?></dd></div><?php endif; ?>
                </dl>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('edit', 'icon icon-sm') ?> ¿Tienes experiencia con este cuadro?</h2>
                <p>Comparte un caso y ayuda a documentar la presentación en México.</p>
                <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/casos/nuevo')) ?>">Compartir caso</a>
            </div>
        </aside>
    </div>
</section>
