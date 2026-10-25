<?php
/** @var array<string, mixed>|null $case */
/** @var array<int, array<string, mixed>> $crops */
/** @var array<int, array<string, mixed>> $states */
/** @var array<string, array<int, array<string, mixed>>> $varietiesByCrops */
/** @var string $tagsValue */
/** @var array<string, mixed> $user */
$isEdit = $case !== null;
$actionUrl = $isEdit ? url('/casos/' . $case['slug']) : url('/casos');
$cropId = (int) old('crop_id', $case['crop_id'] ?? 0);
$varietyId = (int) old('variety_id', $case['variety_id'] ?? 0);
$errors = \App\Core\Flash::errors();
$selectedState = (string) old('state_code', $case['state_code'] ?? '');
?>
<section class="page-hero page-hero-form">
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="<?= e(url('/')) ?>">Inicio</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <a href="<?= e(url('/mis-casos')) ?>">Mis casos</a> <?= icon('chevron-right', 'icon icon-xs') ?>
            <span><?= $isEdit ? 'Editar caso' : 'Nuevo caso' ?></span>
        </nav>
        <div class="page-hero-grid">
            <div>
                <span class="eyebrow"><?= $isEdit ? 'Edición parcela' : 'Nueva aportación' ?></span>
                <h1><?= $isEdit ? 'Editar caso agronómico' : 'Comparte un caso agronómico' ?></h1>
                <p>Documenta los hechos con precisión, protege la identidad del productor y explica lo que aprendiste. El comité editorial revisará tu envío antes de publicarlo.</p>
            </div>
            <div class="form-progress">
                <span class="form-progress-label">Estado</span>
                <strong><?= e($isEdit ? status_label($case['status']) : 'Nuevo borrador') ?></strong>
                <span class="badge badge-<?= e($isEdit ? status_tone($case['status']) : 'neutral') ?>"><?= $isEdit ? e(status_label($case['status'])) : 'Sin guardar' ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container form-layout">
        <form class="case-form" method="post" action="<?= e($actionUrl) ?>" enctype="multipart/form-data" data-case-form>
            <?= csrf_field() ?>

            <?php if ($errors !== []): ?>
                <div class="form-error-summary" role="alert">
                    <?= icon('alert-triangle', 'icon') ?>
                    <div>
                        <strong>Revisa los siguientes campos</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">01</span>
                    <div>
                        <h2>Identificación del caso</h2>
                        <p>Un título claro ayuda a que otros colegas encuentren el caso.</p>
                    </div>
                </div>
                <div class="field">
                    <label for="title">Título del caso <span class="req">*</span></label>
                    <input id="title" name="title" type="text" maxlength="180" required value="<?= e(old('title', $case['title'] ?? '')) ?>" placeholder="Ej. Tizón tardío en jitomate bajo invernadero tras lluvias">
                    <div class="field-foot"><span>Entre 10 y 180 caracteres. Describe cultivo, problema y contexto.</span><span data-counter-for="title"></span></div>
                    <?php if (field_error('title')): ?><span class="field-error"><?= e(field_error('title')) ?></span><?php endif; ?>
                </div>

                <div class="field-grid field-grid-3">
                    <div class="field">
                        <label for="crop_id">Cultivo <span class="req">*</span></label>
                        <select id="crop_id" name="crop_id" required data-crops-select>
                            <option value="">Selecciona…</option>
                            <?php foreach ($crops as $item): ?>
                                <option value="<?= (int) $item['id'] ?>" <?= $cropId === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['emoji'] . ' ' . $item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (field_error('crop_id')): ?><span class="field-error"><?= e(field_error('crop_id')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="variety_id">Variedad</label>
                        <select id="variety_id" name="variety_id" data-variety-select>
                            <option value="">No especificada</option>
                            <?php foreach ($varietiesByCrops as $sid => $varieties): ?>
                                <?php foreach ($varieties as $variety): ?>
                                    <option value="<?= (int) $variety['id'] ?>" data-crops-id="<?= (int) $sid ?>" <?= $varietyId === (int) $variety['id'] ? 'selected' : '' ?>><?= e($variety['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (field_error('variety_id')): ?><span class="field-error"><?= e(field_error('variety_id')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="cycle">Ciclo</label>
                        <select id="cycle" name="cycle">
                            <?php foreach (['primavera-verano' => 'Primavera-verano', 'otono-invierno' => 'Otoño-invierno', 'perenne' => 'Perenne', 'continuo' => 'Continuo', 'no_aplica' => 'No especificado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('cycle', $case['cycle'] ?? 'no_aplica') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-grid field-grid-4">
                    <div class="field">
                        <label for="crop_stage">Etapa fenológica</label>
                        <select id="crop_stage" name="crop_stage">
                            <?php foreach (['siembra' => 'Siembra', 'emergencia' => 'Emergencia', 'vegetativo' => 'Vegetativo', 'floracion' => 'Floración', 'fructificacion' => 'Fructificación', 'llenado' => 'Llenado', 'madurez' => 'Madurez', 'cosecha' => 'Cosecha', 'postcosecha' => 'Postcosecha', 'no_aplica' => 'No especificada'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('crop_stage', $case['crop_stage'] ?? 'no_aplica') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="area_ha">Superficie (ha)</label>
                        <input id="area_ha" name="area_ha" type="number" min="0.01" max="100000" step="0.01" value="<?= e(old('area_ha', $case['area_ha'] ?? '')) ?>" placeholder="1.5">
                        <?php if (field_error('area_ha')): ?><span class="field-error"><?= e(field_error('area_ha')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="incidence_pct">Incidencia (%)</label>
                        <input id="incidence_pct" name="incidence_pct" type="number" min="0" max="100" step="1" value="<?= e(old('incidence_pct', $case['incidence_pct'] ?? '')) ?>" placeholder="35">
                        <?php if (field_error('incidence_pct')): ?><span class="field-error"><?= e(field_error('incidence_pct')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="system">Sistema de producción</label>
                        <select id="system" name="system">
                            <?php foreach (['campo_abierto' => 'Campo abierto', 'invernadero' => 'Invernadero', 'macrotunel' => 'Macrotúnel', 'hidroponia' => 'Hidroponía', 'vivero' => 'Vivero', 'otro' => 'Otro'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('system', $case['system'] ?? 'campo_abierto') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-grid field-grid-3">
                    <div class="field">
                        <label for="state_code">Estado</label>
                        <select id="state_code" name="state_code">
                            <option value="">No especificado</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= e($state['code']) ?>" <?= $selectedState === $state['code'] ? 'selected' : '' ?>><?= e($state['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="municipality">Municipio</label>
                        <input id="municipality" name="municipality" type="text" maxlength="120" value="<?= e(old('municipality', $case['municipality'] ?? '')) ?>" placeholder="Ej. Culiacán">
                        <?php if (field_error('municipality')): ?><span class="field-error"><?= e(field_error('municipality')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="license">Licencia de publicación</label>
                        <select id="license" name="license">
                            <?php foreach (['CC BY-NC-SA 4.0' => 'CC BY-NC-SA 4.0 · Uso educativo con atribución', 'CC BY 4.0' => 'CC BY 4.0 · Uso amplio con atribución', 'CC BY-ND 4.0' => 'CC BY-ND 4.0 · Sin obras derivadas', 'Todos los derechos reservados' => 'Todos los derechos reservados'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('license', $case['license'] ?? 'CC BY-NC-SA 4.0') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">02</span>
                    <div>
                        <h2>Historial del cultivo</h2>
                        <p>Describe los hechos en orden cronológico. Evita interpretaciones en esta sección.</p>
                    </div>
                </div>
                <div class="field">
                    <label for="chief_complaint">Motivo de consulta <span class="req">*</span></label>
                    <textarea id="chief_complaint" name="chief_complaint" rows="2" maxlength="2000" required data-counter placeholder="¿Qué motivó la consulta? Incluye duración y evolución."><?= e(old('chief_complaint', $case['chief_complaint'] ?? '')) ?></textarea>
                    <?php if (field_error('chief_complaint')): ?><span class="field-error"><?= e(field_error('chief_complaint')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="crop_history">Historial de manejo <span class="req">*</span></label>
                    <textarea id="crop_history" name="crop_history" rows="7" maxlength="10000" required data-counter placeholder="Ciclo anterior, variedad, fertilización, riego, aplicaciones fitosanitarias, labores, clima y eventos previos…"><?= e(old('crop_history', $case['crop_history'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>Mínimo 30 caracteres. Incluye sólo información relevante y anonimizada.</span><span data-counter-for="crop_history"></span></div>
                    <?php if (field_error('crop_history')): ?><span class="field-error"><?= e(field_error('crop_history')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="field_inspection">Inspección de campo <span class="req">*</span></label>
                    <textarea id="field_inspection" name="field_inspection" rows="7" maxlength="10000" required data-counter placeholder="Distribución en la parcela, patrón de daño, órganos afectados, presencia de insectos, síntomas en hojas, tallos, raíz y fruto, condiciones de humedad y temperatura…"><?= e(old('field_inspection', $case['field_inspection'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>Mínimo 30 caracteres. Reporta hallazgos positivos y negativos relevantes.</span><span data-counter-for="field_inspection"></span></div>
                    <?php if (field_error('field_inspection')): ?><span class="field-error"><?= e(field_error('field_inspection')) ?></span><?php endif; ?>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">03</span>
                    <div>
                        <h2>Diagnóstico y manejo</h2>
                        <p>Distingue entre sospechas, pruebas realizadas y diagnóstico final.</p>
                    </div>
                </div>
                <div class="field">
                    <label for="lab_tests">Estudios y resultados</label>
                    <textarea id="lab_tests" name="lab_tests" rows="6" maxlength="10000" data-counter placeholder="Análisis de suelo, foliar, cultivo microbiológico, PCR, trampas, conteos de insectos, fotografías y muestras enviadas al laboratorio…"><?= e(old('lab_tests', $case['lab_tests'] ?? '')) ?></textarea>
                    <div class="field-foot"><span data-counter-for="lab_tests"></span></div>
                </div>
                <div class="field">
                    <label for="differentials">Diagnósticos diferenciales</label>
                    <textarea id="differentials" name="differentials" rows="3" maxlength="5000" placeholder="Enumera las principales hipótesis y cómo las descartaste."><?= e(old('differentials', $case['differentials'] ?? '')) ?></textarea>
                </div>
                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="final_diagnosis">Diagnóstico final <span class="req">*</span></label>
                        <input id="final_diagnosis" name="final_diagnosis" type="text" maxlength="300" value="<?= e(old('final_diagnosis', $case['final_diagnosis'] ?? '')) ?>" placeholder="Ej. Tizón tardío por Phytophthora infestans">
                        <?php if (field_error('final_diagnosis')): ?><span class="field-error"><?= e(field_error('final_diagnosis')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="reference_code">Clave de referencia <span class="optional">(opcional)</span></label>
                        <input id="reference_code" name="reference_code" type="text" maxlength="30" value="<?= e(old('reference_code', $case['reference_code'] ?? '')) ?>" placeholder="Ej. clave SENASICA o código interno">
                    </div>
                </div>
                <div class="field">
                    <label for="management">Manejo recomendado</label>
                    <textarea id="management" name="management" rows="6" maxlength="10000" data-counter placeholder="Describe el manejo, la respuesta y los ajustes. Evita dosis que puedan interpretarse como prescripción."><?= e(old('management', $case['management'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>No incluyas dosis específicas. El objetivo es documentar el razonamiento y la respuesta.</span><span data-counter-for="management"></span></div>
                    <?php if (field_error('management')): ?><span class="field-error"><?= e(field_error('management')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="outcome">Resultado <span class="req">*</span></label>
                    <select id="outcome" name="outcome" required>
                        <?php foreach (['recuperado' => 'Recuperado', 'en_manejo' => 'En manejo', 'perdida_parcial' => 'Pérdida parcial', 'perdida_total' => 'Pérdida total', 'en_observacion' => 'En observación', 'desconocido' => 'Sin seguimiento'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= old('outcome', $case['outcome'] ?? 'desconocido') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">04</span>
                    <div>
                        <h2>Discusión y aprendizaje</h2>
                        <p>Explica qué harías diferente, qué limitaciones tuvo el caso y qué mensaje deja para la práctica.</p>
                    </div>
                </div>
                <div class="field">
                    <label for="discussion">Discusión y aprendizaje</label>
                    <textarea id="discussion" name="discussion" rows="6" maxlength="15000" data-counter placeholder="Aprendizajes, limitaciones, decisiones controvertidas, recomendaciones…"><?= e(old('discussion', $case['discussion'] ?? '')) ?></textarea>
                    <div class="field-foot"><span data-counter-for="discussion"></span></div>
                </div>
                <div class="field">
                    <label for="references_text">Referencias</label>
                    <textarea id="references_text" name="references_text" rows="3" maxlength="5000" placeholder="Cita libros, guías o artículos consultados."><?= e(old('references_text', $case['references_text'] ?? '')) ?></textarea>
                </div>
                <div class="field">
                    <label for="tags">Temas del caso</label>
                    <input id="tags" name="tags" type="text" maxlength="300" value="<?= e(old('tags', $tagsValue)) ?>" placeholder="cogollero, maíz, emergencia">
                    <div class="field-foot"><span>Separa hasta 10 temas con comas. Ayudan a encontrar el caso.</span></div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="form-step">05</span>
                    <div>
                        <h2>Imágenes y consentimiento</h2>
                        <p>Las imágenes son opcionales. No subas datos identificables.</p>
                    </div>
                </div>

                <?php if ($isEdit && !empty($case['images'])): ?>
                    <div class="current-images">
                        <?php foreach ($case['images'] as $image): ?>
                            <figure>
                                <img src="<?= e(url('/uploads/cases/' . $image['path'])) ?>" alt="<?= e($image['alt_text'] ?: 'Imagen del caso') ?>" loading="lazy">
                                <figcaption><?= e($image['caption'] ?: 'Imagen del caso') ?></figcaption>
                                <button class="link-button link-danger" type="submit" form="delete-image-<?= (int) $image['id'] ?>">Eliminar</button>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="images">Añadir imágenes <span class="optional">(máx. 6 · JPG, PNG o WebP · 5 MB cada una)</span></label>
                    <label class="file-drop" for="images" data-file-drop>
                        <?= icon('upload', 'icon') ?>
                        <span><strong>Selecciona archivos</strong> o arrástralos aquí</span>
                        <small>Se recomienda eliminar metadatos y recortar cualquier dato identificable.</small>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-image-input>
                    </label>
                    <div class="image-previews" data-image-previews></div>
                </div>

                <div class="consent-grid">
                    <label class="check-card">
                        <input type="checkbox" name="producer_consent" value="1" <?= old('producer_consent', $case['producer_consent'] ?? false) ? 'checked' : '' ?>>
                        <span>
                            <strong>Consentimiento del productor</strong>
                            <small>Confirmo que cuento con consentimiento informado para publicar este caso con fines educativos, con datos anonimizados.</small>
                        </span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" name="anonymized" value="1" <?= old('anonymized', $case['anonymized'] ?? false) ? 'checked' : '' ?>>
                        <span>
                            <strong>Datos anonimizados</strong>
                            <small>Eliminé nombres del productor y del cultivo, direcciones, teléfonos, números de expediente y cualquier dato que permita identificarlos.</small>
                        </span>
                    </label>
                </div>
                <?php if (field_error('producer_consent')): ?><span class="field-error"><?= e(field_error('producer_consent')) ?></span><?php endif; ?>
                <?php if (field_error('anonymized')): ?><span class="field-error"><?= e(field_error('anonymized')) ?></span><?php endif; ?>
                <p class="form-legal">Al enviar, aceptas los <a href="<?= e(url('/terminos')) ?>">términos de uso</a> y el <a href="<?= e(url('/etica')) ?>">código de ética editorial</a>. Puedes guardar un borrador sin completar el consentimiento, pero no se publicará hasta cumplir todos los requisitos.</p>
            </section>

            <div class="form-actions">
                <a class="btn btn-ghost" href="<?= e(url('/mis-casos')) ?>">Cancelar</a>
                <div class="form-actions-right">
                    <button class="btn btn-outline" type="submit" name="action" value="draft">Guardar borrador</button>
                    <button class="btn btn-primary" type="submit" name="action" value="submit">Enviar a revisión <?= icon('arrow-right', 'icon icon-sm') ?></button>
                </div>
            </div>
        </form>

        <aside class="form-sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('shield', 'icon icon-sm') ?> Antes de enviar</h2>
                <ul class="check-list">
                    <li><?= icon('check', 'icon icon-xs') ?> Título claro y específico.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Historial de manejo e inspección de campo completos.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Diagnóstico final y manejo documentados.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Consentimiento y anonimización confirmados.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Sin dosis que puedan interpretarse como prescripción.</li>
                </ul>
            </div>
            <div class="sidebar-card sidebar-tip">
                <h2 class="sidebar-title"><?= icon('sparkles', 'icon icon-sm') ?> Un buen caso</h2>
                <p>No es el que tiene el diagnóstico más raro, sino el que explica con honestidad el razonamiento, las dudas y lo que se aprendió.</p>
                <a class="text-link" href="<?= e(url('/etica')) ?>">Leer guía editorial <?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
            <div class="sidebar-card">
                <h2 class="sidebar-title"><?= icon('lock', 'icon icon-sm') ?> Privacidad</h2>
                <p>Los borradores sólo son visibles para ti y el comité editorial. Puedes eliminar imágenes antes de publicar.</p>
            </div>
            <?php if ($isEdit && in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true)): ?>
                <div class="sidebar-card">
                    <h2 class="sidebar-title"><?= icon('activity', 'icon icon-sm') ?> Envío directo</h2>
                    <p>Si el caso ya está completo, puedes enviarlo a revisión sin volver a guardar.</p>
                    <form method="post" action="<?= e(url('/casos/' . $case['slug'] . '/enviar')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-dark btn-block btn-sm" type="submit">Enviar a revisión</button>
                    </form>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($isEdit && !empty($case['images'])): ?>
        <?php foreach ($case['images'] as $image): ?>
            <form id="delete-image-<?= (int) $image['id'] ?>" method="post" action="<?= e(url('/casos/' . $case['slug'] . '/imagenes/' . $image['id'] . '/eliminar')) ?>" class="visually-hidden" data-confirm="¿Eliminar esta imagen?">
                <?= csrf_field() ?>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
