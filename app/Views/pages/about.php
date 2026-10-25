<?php ?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Nosotros</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Acerca del portal</span>
                <h1>Una comunidad clínica que documenta lo que aprende.</h1>
                <p class="page-hero-lead">Diagnóstico Veterinario nace de una idea sencilla: la experiencia clínica de los médicos veterinarios mexicanos merece un archivo propio, accesible y riguroso.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-page">
        <div class="prose-page-grid">
            <div>
                <h2>Por qué existe</h2>
                <p>En México, gran parte del conocimiento clínico se transmite en la consulta, en los pasillos del hospital o en grupos informales. Ese conocimiento es valioso, pero se dispersa. El portal ofrece una estructura editorial para documentarlo, revisarlo y ponerlo al alcance de otros colegas.</p>
                <p>No pretendemos sustituir libros, guías o la formación especializada. Queremos complementarlos con la realidad de la práctica: casos que no siempre aparecen en los manuales, decisiones tomadas con recursos limitados y aprendizajes que sólo se obtienen viendo pacientes.</p>

                <h2>Qué puedes hacer aquí</h2>
                <ul>
                    <li><strong>Consultar casos clínicos</strong> documentados por colegas y revisados por el comité editorial.</li>
                    <li><strong>Compartir tus propios casos</strong> con una estructura que facilita el aprendizaje y la citación.</li>
                    <li><strong>Usar el apoyo diagnóstico</strong> para ordenar diferenciales a partir de signos clínicos.</li>
                    <li><strong>Explorar fichas de enfermedades</strong> con información práctica y alertas de zoonosis.</li>
                    <li><strong>Descubrir recursos de datos</strong> para investigación, docencia y vigilancia.</li>
                </ul>

                <h2>Comité editorial</h2>
                <p>El comité está formado por médicos veterinarios con experiencia en medicina interna, urgencias, reproducción, producción animal y docencia. Revisa que cada caso cumpla los criterios de consentimiento, anonimización, coherencia clínica y utilidad educativa.</p>
                <p>La revisión es editorial, no un aval diagnóstico ni una certificación de la conducta clínica del autor. Cada caso representa la experiencia de quien lo documenta.</p>

                <h2>Transparencia</h2>
                <p>Los casos demostrativos están marcados como tales. Las fichas de enfermedades citan referencias generales y se actualizan cuando hay cambios relevantes. Si detectas un error, escríbenos: la corrección es parte del método científico.</p>
            </div>
            <aside class="prose-aside">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('award', 'icon icon-sm') ?> Principios</h2>
                    <ul class="check-list">
                        <li><?= icon('check', 'icon icon-xs') ?> Rigor sin solemnidad.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Respeto por el paciente y el tutor.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Transparencia sobre limitaciones.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Colaboración entre colegas.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Acceso abierto al conocimiento.</li>
                    </ul>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('globe', 'icon icon-sm') ?> Referencias de arquitectura</h2>
                    <p>El diseño se inspira en sistemas como VetAPI, PetCloud, VetCompass y SAVSNET, adaptados al contexto mexicano y a una operación ligera en PHP + PostgreSQL.</p>
                    <a class="text-link" href="<?= e(url('/recursos')) ?>">Ver directorio de recursos <?= icon('arrow-right', 'icon icon-xs') ?></a>
                </div>
            </aside>
        </div>
    </div>
</section>
