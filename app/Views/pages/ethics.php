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
                <p class="page-hero-lead">Publicar un caso es un acto de generosidad, pero también de responsabilidad. Estos criterios protegen al productor, a su cultivo y a la comunidad.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-page">
        <div class="prose-page-grid">
            <div>
                <h2>1. Consentimiento informado</h2>
                <p>El autor debe contar con el consentimiento del productor para publicar el caso con fines educativos. El consentimiento debe explicar que el contenido será público, que podrá ser citado y que se difundirá en internet. Cuando el caso provenga de un predio ejidal o de un proyecto institucional y el productor no pueda otorgar el consentimiento de forma individual, el autor documenta el contexto y la autorización de la institución o de la asamblea responsable.</p>

                <h2>2. Anonimización</h2>
                <p>Se eliminan nombres del productor, del rancho o ejido, localidades, coordenadas, direcciones, teléfonos, correos, números de expediente, placas de vehículos y cualquier combinación que permita identificar a una persona o a un predio. Las imágenes deben recortarse o difuminarse si incluyen personas, documentos, ubicaciones o datos identificables. El autor confirma este proceso al enviar el caso.</p>

                <h2>3. Revisión editorial</h2>
                <p>El comité revisa:</p>
                <ul>
                    <li>Coherencia entre el manejo previo, la exploración en campo, los muestreos y el diagnóstico.</li>
                    <li>Claridad del razonamiento técnico y de las limitaciones.</li>
                    <li>Ausencia de datos identificables y presencia de consentimiento.</li>
                    <li>Utilidad educativa y respeto por el productor y su cultivo.</li>
                    <li>Que no se presenten dosis o indicaciones como prescripción universal.</li>
                </ul>
                <p>La decisión puede ser publicar, solicitar cambios o rechazar. El autor recibe retroalimentación específica. La revisión no certifica el desempeño profesional del autor ni sustituye un dictamen pericial.</p>

                <h2>4. Veracidad y correcciones</h2>
                <p>Los casos deben describir hechos reales, aunque se permite omitir detalles para proteger la privacidad. No se aceptan casos inventados, salvo que estén etiquetados como demostrativos. Si se detecta un error, se corrige y se deja constancia cuando afecta el aprendizaje.</p>

                <h2>5. Conflictos de interés</h2>
                <p>El autor debe declarar si el caso forma parte de un estudio patrocinado, si promueve un producto, un servicio de análisis o un paquete tecnológico, o si tiene un interés comercial. El comité puede rechazar contenido que funcione como publicidad encubierta.</p>

                <h2>6. Manejo integrado y plaguicidas</h2>
                <p>No se publican recomendaciones de plaguicidas que no cuenten con registro sanitario vigente en México o que contradigan la etiqueta del producto. El contenido debe privilegiar el manejo integrado de plagas: muestreo, umbrales de acción, control biológico, prácticas culturales y uso responsable de insumos. Tampoco se aceptan procedimientos ilegales ni prácticas que pongan en riesgo a las personas, al cultivo o al ambiente.</p>

                <h2>7. Plagas cuarentenarias</h2>
                <p>Si un caso sugiere la presencia de una plaga cuarentenaria o de una enfermedad sujeta a vigilancia, el autor debe reportarlo a la autoridad sanitaria correspondiente, a través de SENASICA o del organismo auxiliar de sanidad vegetal de su estado. La publicación en el portal no sustituye ese reporte ni constituye una notificación oficial.</p>

                <h2>8. Licencias</h2>
                <p>El autor elige la licencia de su caso. La opción predeterminada es CC BY-NC-SA 4.0, que permite compartir y adaptar con atribución, sin uso comercial y bajo la misma licencia. El portal puede mostrar el caso y citarlo con crédito al autor.</p>

                <h2>9. Uso de herramientas de inteligencia artificial</h2>
                <p>Si se usan herramientas de inteligencia artificial para redactar, traducir o resumir, el autor debe revisar y asumir el contenido. No se permite generar casos ficticios ni atribuir diagnósticos que no se realizaron. La responsabilidad técnica y editorial es humana.</p>

                <h2>10. Uso educativo y límites</h2>
                <p>El contenido del portal tiene fines educativos. No sustituye la valoración en campo, el análisis de laboratorio ni la asesoría de un especialista. Las decisiones de manejo se toman con la información disponible en cada predio y bajo la responsabilidad de quien las aplica.</p>

                <div class="notice notice-info">
                    <?= icon('info', 'icon') ?>
                    <div>
                        <strong>¿Detectaste un problema?</strong>
                        <p>Escribe a <a href="mailto:etica@agrodiagnostico.mx">etica@agrodiagnostico.mx</a> con el enlace del caso. Revisaremos el reporte de forma confidencial.</p>
                    </div>
                </div>
            </div>
            <aside class="prose-aside">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('check-circle', 'icon icon-sm') ?> Lista rápida</h2>
                    <ul class="check-list">
                        <li><?= icon('check', 'icon icon-xs') ?> Consentimiento del productor.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Datos identificables eliminados.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Imágenes sin personas identificables.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Diagnóstico sustentado.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Sin dosis como prescripción.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Conflictos de interés declarados.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Plagas cuarentenarias reportadas.</li>
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
