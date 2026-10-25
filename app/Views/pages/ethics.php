<?php ?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Ética editorial</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Publicación responsable</span>
                <h1>Ética y publicación de casos</h1>
                <p class="page-hero-lead">Publicar un caso es un acto de generosidad, pero también de responsabilidad. Estos criterios protegen a los tutores, a los pacientes y a la comunidad.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-page">
        <div class="prose-page-grid">
            <div>
                <h2>1. Consentimiento informado</h2>
                <p>El autor debe contar con consentimiento del tutor para publicar el caso con fines educativos. El consentimiento debe explicar que el contenido será público, que podrá ser citado y que se difundirá en internet. En animales sin tutor identificable, el autor asume la responsabilidad de documentar el contexto y la autorización institucional cuando corresponda.</p>

                <h2>2. Anonimización</h2>
                <p>Se eliminan nombres del tutor y del paciente, direcciones, teléfonos, correos, números de expediente, placas, microchips, marcas particulares y cualquier combinación que permita identificar a la persona o al animal. Las imágenes deben recortarse o difuminarse si incluyen personas, documentos o datos identificables. El autor confirma este proceso al enviar.</p>

                <h2>3. Revisión editorial</h2>
                <p>El comité revisa:</p>
                <ul>
                    <li>Coherencia entre anamnesis, exploración, pruebas y diagnóstico.</li>
                    <li>Claridad del razonamiento clínico y de las limitaciones.</li>
                    <li>Ausencia de datos identificables y presencia de consentimiento.</li>
                    <li>Utilidad educativa y respeto por el bienestar animal.</li>
                    <li>Que no se presenten dosis o indicaciones como prescripción universal.</li>
                </ul>
                <p>La decisión puede ser publicar, solicitar cambios o rechazar. El autor recibe retroalimentación específica. La revisión no certifica la conducta clínica ni sustituye un dictamen pericial.</p>

                <h2>4. Veracidad y correcciones</h2>
                <p>Los casos deben describir hechos reales, aunque se permite omitir detalles para proteger la privacidad. No se aceptan casos inventados salvo que estén etiquetados como demostrativos. Si se detecta un error, se corrige y se deja constancia cuando afecta el aprendizaje.</p>

                <h2>5. Conflictos de interés</h2>
                <p>El autor debe declarar si el caso forma parte de un estudio patrocinado, si promueve un producto o si tiene un interés comercial. El comité puede rechazar contenido que funcione como publicidad encubierta.</p>

                <h2>6. Bienestar animal</h2>
                <p>No se publican imágenes de sufrimiento innecesario, procedimientos ilegales o maltrato. El contenido debe tener valor educativo y respetar la dignidad del animal.</p>

                <h2>7. Licencias</h2>
                <p>El autor elige la licencia de su caso. La opción predeterminada es CC BY-NC-SA 4.0, que permite compartir y adaptar con atribución, sin uso comercial y bajo la misma licencia. El portal puede mostrar el caso y citarlo con crédito al autor.</p>

                <h2>8. Uso de herramientas de IA</h2>
                <p>Si se usan herramientas de inteligencia artificial para redactar o traducir, el autor debe revisar y asumir el contenido. No se permite generar casos ficticios ni atribuir diagnósticos que no se realizaron. La responsabilidad clínica y editorial es humana.</p>

                <div class="notice notice-info">
                    <?= icon('info', 'icon') ?>
                    <div>
                        <strong>¿Detectaste un problema?</strong>
                        <p>Escribe a <a href="mailto:etica@diagnosticoveterinario.mx">etica@diagnosticoveterinario.mx</a> con el enlace del caso. Revisaremos el reporte de forma confidencial.</p>
                    </div>
                </div>
            </div>
            <aside class="prose-aside">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('check-circle', 'icon icon-sm') ?> Lista rápida</h2>
                    <ul class="check-list">
                        <li><?= icon('check', 'icon icon-xs') ?> Consentimiento del tutor.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Datos identificables eliminados.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Imágenes sin personas identificables.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Diagnóstico sustentado.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Sin dosis como prescripción.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Conflictos de interés declarados.</li>
                    </ul>
                </div>
                <div class="sidebar-card sidebar-cta">
                    <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> ¿Listo para publicar?</h2>
                    <p>La guía se aplica a todos los casos, incluidos los demostrativos.</p>
                    <a class="btn btn-primary btn-block btn-sm" href="<?= e(url('/casos/nuevo')) ?>">Compartir un caso</a>
                </div>
            </aside>
        </div>
    </div>
</section>
