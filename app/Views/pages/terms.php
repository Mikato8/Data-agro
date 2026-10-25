<?php ?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?> <span>Términos de uso</span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow">Condiciones del servicio</span>
                <h1>Términos de uso</h1>
                <p class="page-hero-lead">Al crear una cuenta o utilizar AgroDiagnóstico aceptas estas condiciones. Léelas con atención.</p>
                <p class="page-meta">Última actualización: octubre de 2026</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-page">
        <div class="prose-page-grid">
            <div>
                <h2>1. Objeto del servicio</h2>
                <p>AgroDiagnóstico es un portal educativo y colaborativo para ingenieros agrónomos y técnicos agrícolas. Permite consultar y publicar casos de campo, usar una herramienta de orientación diagnóstica, consultar fichas de plagas y enfermedades y acceder a un directorio de recursos externos. El servicio es gratuito y puede cambiar o suspenderse.</p>

                <h2>2. Cuenta y verificación</h2>
                <p>Para publicar o comentar necesitas una cuenta. Debes proporcionar información veraz, mantener la confidencialidad de tu contraseña y notificar cualquier uso no autorizado. La verificación de la cédula profesional es un requisito para enviar casos y comentar; no implica certificación de competencias ni responsabilidad del portal sobre tus actos profesionales. Mientras la verificación esté pendiente, tu cuenta permanece en revisión.</p>

                <h2>3. Contenido que publicas</h2>
                <p>Conservas la titularidad de tus casos y comentarios. Al publicar, otorgas al portal una licencia no exclusiva, mundial y gratuita para alojar, mostrar, reproducir y difundir el contenido con atribución, conforme a la licencia que elijas. Declaras que cuentas con los derechos y el consentimiento necesarios.</p>
                <p>Eres responsable de la veracidad, originalidad y legalidad de tu contenido, así como de la anonimización de datos. No publiques información que permita identificar a personas o predios.</p>

                <h2>4. Uso aceptable y conductas prohibidas</h2>
                <ul>
                    <li>Publicar datos identificables del productor, del rancho, de la parcela o de terceros.</li>
                    <li>Presentar casos ficticios como reales o atribuir diagnósticos que no se realizaron.</li>
                    <li>Promover productos, servicios o paquetes tecnológicos como publicidad encubierta.</li>
                    <li>Difundir recomendaciones de plaguicidas sin registro sanitario vigente, procedimientos ilegales o contenido discriminatorio.</li>
                    <li>Intentar acceder a cuentas ajenas, alterar el portal o extraer datos de forma masiva.</li>
                    <li>Suplantar la identidad de una persona o institución.</li>
                </ul>

                <h2>5. Naturaleza educativa y límites</h2>
                <p>El contenido del portal es educativo y no constituye asesoría agronómica personalizada, prescripción de insumos, dictamen pericial ni sustituto de la valoración en campo. Las fichas y la herramienta de apoyo diagnóstico son orientativas. Quien toma las decisiones de manejo es responsable de ellas y del cumplimiento de la normativa aplicable.</p>
                <p>El portal no garantiza la exactitud, integridad o vigencia de los contenidos, incluidos los recursos externos. No somos responsables de daños derivados del uso de la información, de la indisponibilidad del servicio o de las prácticas de terceros.</p>

                <h2>6. Moderación</h2>
                <p>El comité editorial puede revisar, editar, rechazar, ocultar o retirar contenido que incumpla estos términos o los criterios éticos. También puede suspender cuentas. Las decisiones editoriales buscan proteger a la comunidad y no constituyen una sanción profesional.</p>

                <h2>7. Propiedad intelectual y licencias</h2>
                <p>La marca, el diseño, el código y los contenidos editoriales del portal pertenecen a sus autores o se utilizan con licencia. Los casos publicados conservan la licencia elegida por su autor; la opción predeterminada es CC BY-NC-SA 4.0, que permite compartir y adaptar el contenido con atribución, sin uso comercial y bajo la misma licencia. Puedes citar el contenido con atribución; no puedes reproducirlo masivamente ni usarlo comercialmente sin autorización.</p>

                <h2>8. Enlaces externos</h2>
                <p>El directorio incluye recursos de terceros. No controlamos su disponibilidad, licencia o tratamiento de datos. Verifica las condiciones antes de reutilizar conjuntos de datos o información.</p>

                <h2>9. Modificaciones</h2>
                <p>Podemos actualizar estos términos. La versión vigente se publicará en esta página. El uso continuado después de un cambio implica aceptación. Si no estás de acuerdo, puedes cerrar tu cuenta.</p>

                <h2>10. Legislación aplicable</h2>
                <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Cualquier controversia se someterá a los tribunales competentes de la Ciudad de México, sin perjuicio de los derechos que la ley reconozca al consumidor.</p>

                <h2>11. Contacto</h2>
                <p>Para dudas sobre estos términos: <a href="mailto:contacto@agrodiagnostico.mx">contacto@agrodiagnostico.mx</a>.</p>
            </div>
            <aside class="prose-aside">
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('file-text', 'icon icon-sm') ?> Documentos relacionados</h2>
                    <a class="text-link" href="<?= e(url('/etica')) ?>">Código de ética editorial <?= icon('arrow-right', 'icon icon-xs') ?></a>
                    <a class="text-link" href="<?= e(url('/privacidad')) ?>">Aviso de privacidad <?= icon('arrow-right', 'icon icon-xs') ?></a>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('alert-triangle', 'icon icon-sm') ?> Recuerda</h2>
                    <p>El portal no sustituye el criterio técnico ni la valoración en campo. Ante una emergencia fitosanitaria, acude a la autoridad sanitaria y a las guías de tu institución.</p>
                </div>
            </aside>
        </div>
    </div>
</section>
