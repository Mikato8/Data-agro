<?php
/** @var array<string, mixed> $disease */
$signGroups = [];
foreach ($disease['signs'] as $sign) {
    $signGroups[(string) $sign['category']][] = $sign;
}
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/enfermedades')) ?>">Enfermedades</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span><?= e($disease['name']) ?></span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <div class="case-kicker">
                    <span class="badge badge-soft"><?= e($disease['category']) ?></span>
                    <span class="badge badge-urgency-<?= e($disease['urgency']) ?>">Urgencia <?= e(urgency_label($disease['urgency'])) ?></span>
                    <?php if ($disease['zoonotic']): ?><span class="badge badge-warning"><?= icon('alert-triangle', 'icon icon-xs') ?> Zoonosis</span><?php endif; ?>
                </div>
                <h1><?= e($disease['name']) ?></h1>
                <?php if (!empty($disease['scientific_name'])): ?><p class="scientific scientific-lg"><?= e($disease['scientific_name']) ?></p><?php endif; ?>
                <p class="page-hero-lead"><?= e($disease['summary']) ?></p>
            </div>
            <div class="page-hero-aside">
                <a class="btn btn-primary btn-sm" href="<?= e(url('/apoyo-diagnostico?species=' . rawurlencode((string) ($disease['species'][0]['code'] ?? 'canino')))) ?>"><?= icon('stethoscope', 'icon icon-xs') ?> Buscar diferenciales</a>
                <a class="btn btn-outline btn-sm" href="<?= e(url('/casos?q=' . rawurlencode((string) $disease['name']))) ?>">Casos relacionados</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container disease-detail-layout">
        <article class="disease-detail">
            <section class="disease-block">
                <h2>Resumen clínico</h2>
                <p><?= nl2br(e($disease['summary'])) ?></p>
            </section>

            <?php foreach ([
                'Etiología y transmisión' => $disease['etiology'],
                'Diagnóstico' => $disease['diagnosis'],
                'Tratamiento y manejo' => $disease['treatment'],
                'Prevención y control' => $disease['prevention'],
                'Pronóstico' => $disease['prognosis'],
            ] as $heading => $content): ?>
                <?php if (trim((string) $content) !== ''): ?>
                    <section class="disease-block">
                        <h2><?= e($heading) ?></h2>
                        <p><?= nl2br(e($content)) ?></p>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <section class="disease-block">
                <h2>Signos clave</h2>
                <p class="disease-block-lead">Los signos con mayor peso orientan la sospecha, pero ninguno es patognomónico por sí solo.</p>
                <div class="sign-profile">
                    <?php foreach ($signGroups as $category => $signs): ?>
                        <div class="sign-profile-group">
                            <h3><?= e($category) ?></h3>
                            <div class="sign-profile-list">
                                <?php foreach ($signs as $sign): ?>
                                    <div class="sign-profile-item">
                                        <span><?= e($sign['name']) ?><?php if ($sign['is_key'] === true || $sign['is_key'] === 't'): ?><span class="key-mark">Clave</span><?php endif; ?></span>
                                        <span class="weight-bar" aria-label="Peso <?= (int) $sign['weight'] ?> de 5"><span style="width: <?= ((int) $sign['weight'] / 5) * 100 ?>%"></span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if (!empty($disease['references_text'])): ?>
                <section class="disease-block disease-references">
                    <h2>Referencias</h2>
                    <p><?= nl2br(e($disease['references_text'])) ?></p>
                </section>
            <?php endif; ?>

            <div class="notice notice-info">
                <?= icon('info', 'icon') ?>
                <div>
                    <strong>Contenido educativo.</strong>
                    <p>Esta ficha resume información general y no sustituye el juicio clínico ni las guías oficiales. Verifica protocolos, dosis y regulación local antes de tomar decisiones terapéuticas.</p>
                </div>
            </div>
        </article>

        <aside class="case-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('paw', 'icon icon-sm') ?> Especies afectadas</h2>
                <div class="species-chip-list">
                    <?php foreach ($disease['species'] as $species): ?>
                        <span class="species-pill species-<?= e($species['code']) ?>"><?= e($species['emoji']) ?> <?= e($species['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Datos de la ficha</h2>
                <dl class="patient-data">
                    <div><dt>Categoría</dt><dd><?= e($disease['category']) ?></dd></div>
                    <div><dt>Urgencia</dt><dd><?= e(urgency_label($disease['urgency'])) ?></dd></div>
                    <div><dt>Zoonosis</dt><dd><?= $disease['zoonotic'] ? 'Sí' : 'No' ?></dd></div>
                    <div><dt>Signos</dt><dd><?= count($disease['signs']) ?></dd></div>
                    <?php if (!empty($disease['icd11_code'])): ?><div><dt>ICD-11</dt><dd><?= e($disease['icd11_code']) ?></dd></div><?php endif; ?>
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
