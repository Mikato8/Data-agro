<?php
/** @var bool $configured */
/** @var string $text */
/** @var array<string, mixed>|null $result */
/** @var string|null $error */
$examples = [
    'Maíz con hojas perforadas y larvas en el cogollo; el daño avanzó en una semana.',
    'Jitomate en invernadero con manchas oscuras que crecen y frutos con pudrición.',
    'Limón con brotes amarillos, hojas pequeñas y presencia de psílido en los brotes.',
    'Parcela sana para muestreo de fertilidad y plan de siembra del próximo ciclo.',
];
$levelOrder = ['emergencia', 'urgente', 'consulta', 'preventivo'];
?>
<section class="ai-hero">
    <div class="container">
        <nav class="breadcrumb breadcrumb-light" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/apoyo-diagnostico')) ?>">Apoyo diagnóstico</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Riesgo fitosanitario</span>
        </nav>
        <div class="ai-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Clasificador de riesgo</span>
                <h1>Orientación de emergencia con IA</h1>
                <p class="ai-lead">Escribe un relato breve del caso y el clasificador estima si conviene actuar como emergencia, emergencia, consulta programada o prevención. Es una segunda opinión orientativa, no un diagnóstico.</p>
            </div>
            <div class="ai-hero-badge">
                <?= icon('cpu', 'icon') ?>
                <strong>Clasificador local</strong>
                <span>4 niveles · español · reglas explicables</span>
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
                        <p>El portal ya tiene la interfaz y el cliente listos. Falta desplegar el servicio Clasificador de riesgo y definir <code>AI_SERVICE_URL</code>. Mientras tanto puedes usar el apoyo diagnóstico por síntomas.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="notice notice-danger" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div><strong>No se pudo clasificar.</strong><p><?= e($error) ?></p></div>
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
                            <span class="eyebrow">Resultado orientativo</span>
                            <h2><?= e($result['label_es'] ?? 'Sin clasificación') ?></h2>
                        </div>
                        <div class="ai-confidence">
                            <strong><?= number_format($confidence * 100, 0) ?>%</strong>
                            <span>confianza</span>
                        </div>
                    </div>
                    <p class="ai-result-description"><?= e($result['description'] ?? '') ?></p>
                    <?php if (!empty($result['safety_rule'])): ?>
                        <p class="ai-safety-rule"><?= icon('shield', 'icon icon-xs') ?> <?= e($result['safety_rule']) ?></p>
                    <?php endif; ?>
                    <?php if ($scores !== []): ?>
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
                    <?php endif; ?>
                    <p class="ai-disclaimer"><?= icon('alert-triangle', 'icon icon-xs') ?> <?= e($result['disclaimer'] ?? 'No es un diagnóstico ni sustituye la valoración en campo.') ?></p>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('/apoyo-diagnostico/ia')) ?>">Clasificar otro relato</a>
                </article>
            <?php endif; ?>

            <form class="ai-form" method="post" action="<?= e(url('/apoyo-diagnostico/ia')) ?>" data-ai-form>
                <?= csrf_field() ?>
                <div class="ai-form-head">
                    <div>
                        <span class="form-step">01</span>
                        <h2>Relato del caso</h2>
                    </div>
                    <span class="ai-form-limit">máx. 2,000 caracteres</span>
                </div>
                <label class="sr-only" for="ai-text">Relato de campo</label>
                <textarea id="ai-text" name="text" rows="8" maxlength="2000" required placeholder="Ej. Maíz de temporal con hojas perforadas, larvas en el cogollo y daño que avanza…" data-ai-text><?= e($text) ?></textarea>
                <div class="ai-examples">
                    <span>Ejemplos:</span>
                    <?php foreach ($examples as $example): ?>
                        <button type="button" class="ai-example" data-ai-example="<?= e($example) ?>"><?= e(excerpt($example, 62)) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="ai-form-foot">
                    <p><?= icon('lock', 'icon icon-xs') ?> No envíes nombres, direcciones ni datos identificables. El texto se envía al servicio de clasificación y no se guarda en el portal.</p>
                    <button class="btn btn-primary" type="submit" <?= !$configured ? 'disabled' : '' ?>><?= icon('cpu', 'icon icon-sm') ?> Clasificar riesgo</button>
                </div>
            </form>
        </div>

        <aside class="ai-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('layers', 'icon icon-sm') ?> Los cuatro niveles</h2>
                <div class="ai-levels">
                    <div><span class="ai-dot ai-dot-critical"></span><strong>Emergencia</strong><small>Atención inmediata</small></div>
                    <div><span class="ai-dot ai-dot-high"></span><strong>Urgente</strong><small>Mismo día o 24 h</small></div>
                    <div><span class="ai-dot ai-dot-medium"></span><strong>Consulta</strong><small>Próximos días</small></div>
                    <div><span class="ai-dot ai-dot-low"></span><strong>Preventivo</strong><small>Rutina y control</small></div>
                </div>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('cpu', 'icon icon-sm') ?> Cómo funciona</h2>
                <p>El clasificador combina reglas léxicas en español con evidencia agronómica y, opcionalmente, un modelo supervisado entrenado con ejemplos sintéticos. La inferencia corre fuera del hosting PHP, en un servicio FastAPI.</p>
            </div>
            <div class="sidebar-card sidebar-cta">
                <h2 class="sidebar-title"><?= icon('alert-triangle', 'icon icon-sm') ?> Importante</h2>
                <p>Si el cultivo tiene dificultad para respirar, convulsiones, sangrado abundante, obstrucción urinaria o está inconsciente, <strong>actúa de inmediato</strong>; no esperes la clasificación.</p>
                <a class="text-link" href="<?= e(url('/apoyo-diagnostico')) ?>">Volver al apoyo por síntomas <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
        </aside>
    </div>
</section>
