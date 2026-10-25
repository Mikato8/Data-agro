<?php ?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Nosotros</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Acerca del portal</span>
                <h1>Una comunidad que documenta lo que aprende en el campo.</h1>
                <p class="page-hero-lead">AgroDiagnóstico nace de una idea sencilla: la experiencia de los ingenieros agrónomos mexicanos merece un archivo propio, accesible y riguroso.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-page">
        <div class="prose-page-grid">
            <div>
                <h2>Nuestra misión</h2>
                <p>Documentar casos de campo para que el conocimiento técnico de quienes trabajan la tierra no se pierda. Queremos mejorar las decisiones fitosanitarias con evidencia, criterio y contexto, y ofrecer datos abiertos y reutilizables a ingenieros agrónomos, técnicos agrícolas, estudiantes e investigadores.</p>
                <p>En México, gran parte de ese conocimiento se transmite en la visita a la parcela, en la bodega de insumos o en grupos informales. Es valioso, pero se dispersa. El portal ofrece una estructura editorial para documentarlo, revisarlo y ponerlo al alcance de otros colegas.</p>
                <p>No pretendemos sustituir libros, guías o la formación especializada. Queremos complementarlos con la realidad de la práctica: casos que no siempre aparecen en los manuales, decisiones tomadas con recursos limitados y aprendizajes que sólo se obtienen recorriendo la parcela.</p>

                <h2>Qué encontrarás en el portal</h2>
                <ul>
                    <li><strong>Casos agronómicos</strong> documentados por colegas y revisados por el comité editorial.</li>
                    <li><strong>Apoyo diagnóstico por síntomas</strong> para ordenar hipótesis y detectar información faltante antes de confirmar en campo.</li>
                    <li><strong>Fichas de plagas y enfermedades</strong> con información práctica, referencias y alertas cuarentenarias.</li>
                    <li><strong>Directorio de recursos</strong> para investigación, docencia y vigilancia fitosanitaria.</li>
                    <li><strong>Espacios de discusión</strong> donde las dudas se contrastan con la experiencia de otros profesionales.</li>
                </ul>

                <h2>Cómo funciona la revisión editorial</h2>
                <p>El comité está formado por ingenieros agrónomos con experiencia en fitopatología, entomología, fertilidad de suelos, riego, horticultura protegida y fruticultura. Cada caso se revisa para comprobar que el razonamiento técnico sea claro, que las limitaciones estén declaradas y que la información identificable se haya eliminado.</p>
                <p>La revisión es editorial: no avala un diagnóstico ni certifica el desempeño profesional del autor. Cada caso representa la experiencia de quien lo documenta, con sus aciertos y sus dudas.</p>

                <h2>Compromiso con el productor</h2>
                <p>Escribimos para quien toma decisiones en el campo y asume el costo de una cosecha perdida. Por eso pedimos que los casos distingan entre lo observado, lo supuesto y lo confirmado; que expliquen el contexto de producción; y que no conviertan una experiencia particular en una receta universal. La privacidad del productor, de su rancho y de su parcela es parte de ese respeto.</p>

                <h2>Transparencia</h2>
                <p>Los casos demostrativos están marcados como tales. Las fichas de plagas y enfermedades citan referencias generales y se actualizan cuando hay cambios relevantes. Si detectas un error, escríbenos: la corrección es parte del método científico y del compromiso con quienes leen.</p>
            </div>
            <aside class="prose-aside">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('award', 'icon icon-sm') ?> Principios</h2>
                    <ul class="check-list">
                        <li><?= icon('check', 'icon icon-xs') ?> Rigor sin solemnidad.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Respeto por el productor y su cultivo.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Transparencia sobre limitaciones.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Colaboración entre colegas.</li>
                        <li><?= icon('check', 'icon icon-xs') ?> Acceso abierto al conocimiento.</li>
                    </ul>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('globe', 'icon icon-sm') ?> Sobre la plataforma</h2>
                    <p>El portal funciona con PHP 8 y PostgreSQL. Su diseño se inspira en archivos abiertos de sanidad vegetal y en plataformas de datos agrícolas adaptadas al contexto mexicano; la prioridad es que el archivo sea fácil de consultar, citar y reutilizar.</p>
                    <a class="text-link" href="<?= e(url('/recursos')) ?>">Ver directorio de recursos <?= icon('arrow-right', 'icon icon-xs') ?></a>
                </div>
            </aside>
        </div>
    </div>
</section>
