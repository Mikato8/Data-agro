<?php
/** @var array<string, mixed> $case */
/** @var string $text */
/** @var array<string, mixed>|null $result */
/** @var string|null $error */
/** @var bool $configured */
$levelOrder = ['emergencia', 'urgente', 'consulta', 'preventivo'];
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/casos')) ?>">Casos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/casos/' . $case['slug'])) ?>"><?= e(excerpt($case['title'], 48)) ?></a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>IA de urgencia</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <div class="case-kicker">
                    <span class="badge badge-soft"><?= icon('cpu', 'icon icon-xs') ?> SetFit + ONNX</span>
                    <span class="badge badge-success"><?= e($case['species_name']) ?></span>
                </div>
                <h1>Urgencia del caso con IA</h1>
                <p class="page-hero-lead">El clasificador lee el relato clínico de <strong><?= e($case['title']) ?></strong> y estima un nivel de urgencia. Revisa y edita el texto antes de analizarlo: el diagnóstico final no se envía.</p>
            </div>
            <div class="page-hero-aside">
                <a class="btn btn-outline btn-sm" href="<?= e(url('/casos/' . $case['slug'])) ?>"><?= icon('arrow-left', 'icon icon-xs') ?> Volver al caso</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container ai-layout">
        <div>
            <?php if (!$configured): ?>
                <div class="notice notice-warning">
                    <?= icon('info', 'icon') ?>
                    <div>
                        <strong>La IA aún no está conectada en este entorno.</strong>
                        <p>La interfaz y el cliente están listos; falta desplegar el servicio SetFit + ONNX y definir <code>AI_SERVICE_URL</code>.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="notice notice-danger" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div><strong>No se pudo analizar.</strong><p><?= e($error) ?></p></div>
                </div>
            <?php endif; ?>

            <?php if (is_array($result)): ?>
                <?php
                $confidence = (float) ($result['confidence'] ?? 0);
                $scores = is_array($result['scores'] ?? null) ? $result['scores'] : [];
                $color = (string) ($result['color'] ?? 'medium');
                ?>
                <article class="ai-result ai-result-<?= e($color) ?>">
                    <div class="ai-result-head">
                        <span class="ai-result-icon"><?= icon($color === 'critical' ? 'alert-triangle' : ($color === 'low' ? 'shield' : 'activity'), 'icon') ?></span>
                        <div>
                            <span class="eyebrow">Resultado orientativo para este caso</span>
                            <h2><?= e($result['label_es'] ?? 'Sin clasificación') ?></h2>
                        </div>
                        <div class="ai-confidence"><strong><?= number_format($confidence * 100, 0) ?>%</strong><span>confianza</span></div>
                    </div>
                    <p class="ai-result-description"><?= e($result['description'] ?? '') ?></p>
                    <?php if (!empty($result['safety_rule'])): ?><p class="ai-safety-rule"><?= icon('shield', 'icon icon-xs') ?> <?= e($result['safety_rule']) ?></p><?php endif; ?>
                    <div class="ai-scores">
                        <?php foreach ($levelOrder as $level): ?>
                            <?php if (!isset($scores[$level])) continue; ?>
                            <div class="ai-score-row">
                                <span><?= e(ucfirst($level)) ?></span>
                                <div class="ai-score-bar"><span style="width: <?= max(1, min(100, round((float) $scores[$level] * 100))) ?>%"></span></div>
                                <strong><?= number_format((float) $scores[$level] * 100, 0) ?>%</strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="ai-disclaimer"><?= icon('alert-triangle', 'icon icon-xs') ?> <?= e($result['disclaimer'] ?? 'No es un diagnóstico ni sustituye la valoración clínica.') ?></p>
                </article>
            <?php endif; ?>

            <form class="ai-form" method="post" action="<?= e(url('/casos/' . $case['slug'] . '/ia-urgencia')) ?>" data-ai-form>
                <?= csrf_field() ?>
                <div class="ai-form-head">
                    <div><span class="form-step">01</span><h2>Relato para analizar</h2></div>
                    <span class="ai-form-limit">máx. 2,000 caracteres</span>
                </div>
                <textarea id="case-ai-text" name="text" rows="10" maxlength="2000" required aria-label="Relato clínico" data-ai-text><?= e($text) ?></textarea>
                <div class="ai-form-foot">
                    <p><?= icon('lock', 'icon icon-xs') ?> Edita o recorta el texto. No incluyas nombres, direcciones, teléfonos ni datos del tutor. El texto se envía al servicio de IA y no se guarda en el portal.</p>
                    <button class="btn btn-primary" type="submit" <?= !$configured ? 'disabled' : '' ?>><?= icon('cpu', 'icon icon-sm') ?> Analizar urgencia</button>
                </div>
            </form>
        </div>

        <aside class="ai-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('paw', 'icon icon-sm') ?> Datos del caso</h2>
                <dl class="patient-data">
                    <div><dt>Especie</dt><dd><?= e($case['species_name']) ?></dd></div>
                    <div><dt>Sexo</dt><dd><?= e(sex_label($case['sex'])) ?></dd></div>
                    <div><dt>Edad</dt><dd><?= e(age_label($case['age_value'] !== null ? (int) $case['age_value'] : null, $case['age_unit'])) ?></dd></div>
                    <div><dt>Entorno</dt><dd><?= e(setting_label($case['setting'])) ?></dd></div>
                </dl>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('cpu', 'icon icon-sm') ?> Qué analiza</h2>
                <p>Especie, sexo, edad, motivo, anamnesis y exploración física. No se envía el diagnóstico final ni el tratamiento para evitar sesgos.</p>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('alert-triangle', 'icon icon-sm') ?> Límite</h2>
                <p>El modelo es un prototipo con datos sintéticos. Si hay un signo de alarma, actúa de inmediato; no esperes la clasificación.</p>
                <a class="text-link" href="<?= e(url('/casos/' . $case['slug'])) ?>">Volver a la ficha del caso <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
        </aside>
    </div>
</section>
