<?php
/** @var array<string, mixed>|null $case */
/** @var array<int, array<string, mixed>> $species */
/** @var array<int, array<string, mixed>> $states */
/** @var array<string, array<int, array<string, mixed>>> $breedsBySpecies */
/** @var string $tagsValue */
/** @var array<string, mixed> $user */
$isEdit = $case !== null;
$actionUrl = $isEdit ? url('/casos/' . $case['slug']) : url('/casos');
$speciesId = (int) old('species_id', $case['species_id'] ?? 0);
$breedId = (int) old('breed_id', $case['breed_id'] ?? 0);
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
                <span class="eyebrow"><?= $isEdit ? 'Edición clínica' : 'Nueva aportación' ?></span>
                <h1><?= $isEdit ? 'Editar caso clínico' : 'Comparte un caso clínico' ?></h1>
                <p>Documenta los hechos con precisión, protege la identidad del tutor y explica lo que aprendiste. El comité editorial revisará tu envío antes de publicarlo.</p>
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
                    <input id="title" name="title" type="text" maxlength="180" required value="<?= e(old('title', $case['title'] ?? '')) ?>" placeholder="Ej. Parvovirus canino en cachorro de 4 meses con diarrea hemorrágica">
                    <div class="field-foot"><span>Entre 10 y 180 caracteres. Describe especie, problema y contexto.</span><span data-counter-for="title"></span></div>
                    <?php if (field_error('title')): ?><span class="field-error"><?= e(field_error('title')) ?></span><?php endif; ?>
                </div>

                <div class="field-grid field-grid-3">
                    <div class="field">
                        <label for="species_id">Especie <span class="req">*</span></label>
                        <select id="species_id" name="species_id" required data-species-select>
                            <option value="">Selecciona…</option>
                            <?php foreach ($species as $item): ?>
                                <option value="<?= (int) $item['id'] ?>" <?= $speciesId === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['emoji'] . ' ' . $item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (field_error('species_id')): ?><span class="field-error"><?= e(field_error('species_id')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="breed_id">Raza</label>
                        <select id="breed_id" name="breed_id" data-breed-select>
                            <option value="">No especificada</option>
                            <?php foreach ($breedsBySpecies as $sid => $breeds): ?>
                                <?php foreach ($breeds as $breed): ?>
                                    <option value="<?= (int) $breed['id'] ?>" data-species-id="<?= (int) $sid ?>" <?= $breedId === (int) $breed['id'] ? 'selected' : '' ?>><?= e($breed['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (field_error('breed_id')): ?><span class="field-error"><?= e(field_error('breed_id')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="sex">Sexo <span class="req">*</span></label>
                        <select id="sex" name="sex" required>
                            <?php foreach (['macho' => 'Macho', 'hembra' => 'Hembra', 'desconocido' => 'No determinado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('sex', $case['sex'] ?? 'desconocido') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-grid field-grid-4">
                    <div class="field">
                        <label for="age_value">Edad</label>
                        <input id="age_value" name="age_value" type="number" min="0" max="100" step="1" value="<?= e(old('age_value', $case['age_value'] ?? '')) ?>" placeholder="4">
                        <?php if (field_error('age_value')): ?><span class="field-error"><?= e(field_error('age_value')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="age_unit">Unidad</label>
                        <select id="age_unit" name="age_unit">
                            <option value="">—</option>
                            <?php foreach (['dias' => 'Días', 'semanas' => 'Semanas', 'meses' => 'Meses', 'anios' => 'Años'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('age_unit', $case['age_unit'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="weight_kg">Peso (kg)</label>
                        <input id="weight_kg" name="weight_kg" type="number" min="0.1" max="5000" step="0.1" value="<?= e(old('weight_kg', $case['weight_kg'] ?? '')) ?>" placeholder="9.2">
                        <?php if (field_error('weight_kg')): ?><span class="field-error"><?= e(field_error('weight_kg')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="setting">Entorno</label>
                        <select id="setting" name="setting">
                            <?php foreach (['clinica' => 'Clínica', 'hospital' => 'Hospital', 'campo' => 'Campo', 'laboratorio' => 'Laboratorio', 'otro' => 'Otro'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= old('setting', $case['setting'] ?? 'clinica') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="state_code">Estado de México</label>
                        <select id="state_code" name="state_code">
                            <option value="">No especificado</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= e($state['code']) ?>" <?= $selectedState === $state['code'] ? 'selected' : '' ?>><?= e($state['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <h2>Historia clínica</h2>
                        <p>Describe los hechos en orden cronológico. Evita interpretaciones en esta sección.</p>
                    </div>
                </div>
                <div class="field">
                    <label for="chief_complaint">Motivo de consulta <span class="req">*</span></label>
                    <textarea id="chief_complaint" name="chief_complaint" rows="2" maxlength="2000" required data-counter placeholder="¿Qué motivó la consulta? Incluye duración y evolución."><?= e(old('chief_complaint', $case['chief_complaint'] ?? '')) ?></textarea>
                    <?php if (field_error('chief_complaint')): ?><span class="field-error"><?= e(field_error('chief_complaint')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="anamnesis">Anamnesis <span class="req">*</span></label>
                    <textarea id="anamnesis" name="anamnesis" rows="7" maxlength="10000" required data-counter placeholder="Antecedentes, manejo, alimentación, vacunación, exposición, tratamientos previos…"><?= e(old('anamnesis', $case['anamnesis'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>Mínimo 30 caracteres. Incluye sólo información relevante y anonimizada.</span><span data-counter-for="anamnesis"></span></div>
                    <?php if (field_error('anamnesis')): ?><span class="field-error"><?= e(field_error('anamnesis')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="physical_exam">Exploración física <span class="req">*</span></label>
                    <textarea id="physical_exam" name="physical_exam" rows="7" maxlength="10000" required data-counter placeholder="Constantes, condición corporal, hallazgos por aparatos y sistemas…"><?= e(old('physical_exam', $case['physical_exam'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>Mínimo 30 caracteres. Reporta hallazgos positivos y negativos relevantes.</span><span data-counter-for="physical_exam"></span></div>
                    <?php if (field_error('physical_exam')): ?><span class="field-error"><?= e(field_error('physical_exam')) ?></span><?php endif; ?>
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
                    <label for="diagnostics">Estudios y resultados</label>
                    <textarea id="diagnostics" name="diagnostics" rows="6" maxlength="10000" data-counter placeholder="Hemograma, bioquímica, imagen, citología, cultivo, PCR…"><?= e(old('diagnostics', $case['diagnostics'] ?? '')) ?></textarea>
                    <div class="field-foot"><span data-counter-for="diagnostics"></span></div>
                </div>
                <div class="field">
                    <label for="differentials">Diagnósticos diferenciales</label>
                    <textarea id="differentials" name="differentials" rows="3" maxlength="5000" placeholder="Enumera las principales hipótesis y cómo las descartaste."><?= e(old('differentials', $case['differentials'] ?? '')) ?></textarea>
                </div>
                <div class="field-grid field-grid-2">
                    <div class="field">
                        <label for="final_diagnosis">Diagnóstico final <span class="req">*</span></label>
                        <input id="final_diagnosis" name="final_diagnosis" type="text" maxlength="300" value="<?= e(old('final_diagnosis', $case['final_diagnosis'] ?? '')) ?>" placeholder="Ej. Parvovirus canino con enteritis hemorrágica">
                        <?php if (field_error('final_diagnosis')): ?><span class="field-error"><?= e(field_error('final_diagnosis')) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="icd11_code">Código ICD-11 <span class="optional">(opcional)</span></label>
                        <input id="icd11_code" name="icd11_code" type="text" maxlength="30" value="<?= e(old('icd11_code', $case['icd11_code'] ?? '')) ?>" placeholder="Si lo tienes, agrégalo">
                    </div>
                </div>
                <div class="field">
                    <label for="treatment">Tratamiento y manejo</label>
                    <textarea id="treatment" name="treatment" rows="6" maxlength="10000" data-counter placeholder="Describe el manejo, la respuesta y los ajustes. Evita dosis que puedan interpretarse como prescripción."><?= e(old('treatment', $case['treatment'] ?? '')) ?></textarea>
                    <div class="field-foot"><span>No incluyas dosis específicas. El objetivo es documentar el razonamiento y la respuesta.</span><span data-counter-for="treatment"></span></div>
                    <?php if (field_error('treatment')): ?><span class="field-error"><?= e(field_error('treatment')) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="outcome">Desenlace <span class="req">*</span></label>
                    <select id="outcome" name="outcome" required>
                        <?php foreach (['curado' => 'Resuelto / curado', 'en_tratamiento' => 'En tratamiento', 'secuela' => 'Con secuela', 'fallecido' => 'Fallecido', 'eutanasia' => 'Eutanasia', 'desconocido' => 'Sin seguimiento'] as $value => $label): ?>
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
                    <label for="discussion">Discusión clínica</label>
                    <textarea id="discussion" name="discussion" rows="6" maxlength="15000" data-counter placeholder="Aprendizajes, limitaciones, decisiones controvertidas, recomendaciones…"><?= e(old('discussion', $case['discussion'] ?? '')) ?></textarea>
                    <div class="field-foot"><span data-counter-for="discussion"></span></div>
                </div>
                <div class="field">
                    <label for="references_text">Referencias</label>
                    <textarea id="references_text" name="references_text" rows="3" maxlength="5000" placeholder="Cita libros, guías o artículos consultados."><?= e(old('references_text', $case['references_text'] ?? '')) ?></textarea>
                </div>
                <div class="field">
                    <label for="tags">Temas del caso</label>
                    <input id="tags" name="tags" type="text" maxlength="300" value="<?= e(old('tags', $tagsValue)) ?>" placeholder="parvovirus, urgencia, cachorro">
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
                        <input type="checkbox" name="owner_consent" value="1" <?= old('owner_consent', $case['owner_consent'] ?? false) ? 'checked' : '' ?>>
                        <span>
                            <strong>Consentimiento del tutor</strong>
                            <small>Confirmo que cuento con consentimiento informado para publicar este caso con fines educativos, con datos anonimizados.</small>
                        </span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" name="anonymized" value="1" <?= old('anonymized', $case['anonymized'] ?? false) ? 'checked' : '' ?>>
                        <span>
                            <strong>Datos anonimizados</strong>
                            <small>Eliminé nombres del tutor y del paciente, direcciones, teléfonos, números de expediente y cualquier dato que permita identificarlos.</small>
                        </span>
                    </label>
                </div>
                <?php if (field_error('owner_consent')): ?><span class="field-error"><?= e(field_error('owner_consent')) ?></span><?php endif; ?>
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
                    <li><?= icon('check', 'icon icon-xs') ?> Anamnesis y exploración completas.</li>
                    <li><?= icon('check', 'icon icon-xs') ?> Diagnóstico final y tratamiento documentados.</li>
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
