<?php
/** @var array<int, array<string, mixed>> $downloads */
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/recursos')) ?>">Recursos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span>Kit de plantillas</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Herramientas internas</span>
                <h1>Kit de recursos para agrónomos</h1>
                <p class="page-hero-lead">Plantillas y guías descargables para documentar casos de campo, obtener consentimiento y solicitar datos a instituciones, con lenguaje claro y enfoque mexicano.</p>
            </div>
            <div class="page-hero-aside">
                <span class="badge badge-success"><?= icon('check-circle', 'icon icon-xs') ?> Descarga libre</span>
                <span>No necesitas cuenta para descargar.</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="kit-grid">
            <?php foreach ($downloads as $download): ?>
                <article class="kit-card">
                    <span class="kit-icon"><?= icon($download['icon'], 'icon') ?></span>
                    <span class="badge badge-soft"><?= e($download['type']) ?></span>
                    <h2><?= e($download['title']) ?></h2>
                    <p><?= e($download['description']) ?></p>
                    <a class="btn btn-outline btn-sm" href="<?= e(url($download['file'])) ?>" download><?= icon('download', 'icon icon-xs') ?> Descargar</a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="kit-howto">
            <div>
                <span class="eyebrow">Cómo usarlas</span>
                <h2>Descarga, completa y comparte</h2>
                <p>Las plantillas están en Word (.docx) para que puedas editarlas. Antes de compartir un caso, elimina nombres, teléfonos, coordenadas exactas y cualquier dato que identifique al productor o a su predio.</p>
            </div>
            <ol class="mini-steps">
                <li><span>1</span> Descarga la plantilla que necesites.</li>
                <li><span>2</span> Completa los campos entre corchetes.</li>
                <li><span>3</span> Revisa el checklist de anonimización antes de compartir.</li>
                <li><span>4</span> Publica el caso con el formulario del portal.</li>
            </ol>
        </div>

        <div class="notice notice-info">
            <?= icon('info', 'icon') ?>
            <div>
                <strong>¿Buscas un dataset o un manual?</strong>
                <p>El <a href="<?= e(url('/recursos')) ?>">directorio de recursos</a> explica cada repositorio en español y te dice cómo solicitar acceso. Si un término no te resulta familiar, consulta el <a href="<?= e(url('/recursos/glosario')) ?>">glosario</a>.</p>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('/recursos')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Volver a recursos</a>
            <a class="btn btn-primary" href="<?= e(url('/casos/nuevo')) ?>">Publicar un caso en el portal</a>
        </div>
    </div>
</section>
