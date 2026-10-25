<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Support\CaseDocx;
use App\Support\CasePdf;
use App\Repositories\CaseRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\UserRepository;
use App\Services\RiskAiClient;

final class CaseController extends BaseController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'crops' => $request->string('crops'),
            'state' => $request->string('state'),
            'outcome' => $request->string('outcome'),
            'year' => $request->int('year'),
            'tag' => $request->string('tag'),
            'sort' => $request->string('sort', 'recientes'),
        ];
        $page = max(1, $request->int('page', 1));
        $perPage = (int) config('app.pagination.cases', 9);
        $repo = new CaseRepository();
        $result = $repo->search($filters, $page, $perPage);
        $catalog = new CatalogRepository();

        return $this->view('cases.index', [
            'title' => 'Casos de campo · AgroDiagnóstico',
            'metaDescription' => 'Explora casos de campo en maíz, café, agave, hortalizas, frutales y otros cultivos de México, documentados por ingenieros agrónomos.',
            'cases' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'paginator' => new Paginator($result['total'], $perPage, $page),
            'cropsList' => $catalog->crops(),
            'states' => $catalog->states(),
            'years' => $repo->years(),
            'tags' => $repo->popularTags(12),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $repo = new CaseRepository();
        $case = $repo->findPublishedBySlug($slug);
        $isPreview = false;

        if ($case === null) {
            $user = Auth::user();
            if ($user !== null && (in_array($user['role'], (array) config('app.moderators', []), true) || (int) $user['id'] > 0)) {
                $case = $repo->findBySlug($slug);
                if ($case !== null && ((int) $case['author_id'] === (int) $user['id'] || in_array($user['role'], (array) config('app.moderators', []), true))) {
                    $isPreview = true;
                } else {
                    $case = null;
                }
            }
        }

        if ($case === null) {
            $this->notFound('Este caso no existe o aún no ha sido publicado.');
        }

        if (!$isPreview) {
            $repo->incrementViews((int) $case['id']);
            $case['views'] = (int) $case['views'] + 1;
        }

        $user = Auth::user();
        $comments = $repo->comments((int) $case['id'], $user !== null && in_array($user['role'], (array) config('app.moderators', []), true));
        $similar = $case['final_diagnosis'] !== '' ? $repo->similar($case, 3) : [];
        $reacted = $user !== null && $repo->hasReacted((int) $case['id'], (int) $user['id']);

        return $this->view('cases.show', [
            'title' => $case['title'] . ' · AgroDiagnóstico',
            'metaDescription' => excerpt($case['chief_complaint'], 160),
            'case' => $case,
            'comments' => $comments,
            'similar' => $similar,
            'reacted' => $reacted,
            'isPreview' => $isPreview,
            'canEdit' => $user !== null && $this->canEditCase($case, $user),
            'canModerate' => $user !== null && in_array($user['role'], (array) config('app.moderators', []), true),
            'canonical' => url('/casos/' . $case['slug']),
            'robots' => $isPreview ? 'noindex,nofollow' : 'index,follow',
        ]);
    }

    public function aiForm(Request $request, string $slug): Response
    {
        $case = (new CaseRepository())->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para el análisis de IA.');
        }

        return $this->view('cases.ai', [
            'title' => 'Riesgo fitosanitario · ' . $case['title'],
            'metaDescription' => 'Analiza el relato de campo de este caso con un clasificador orientativo de riesgo fitosanitario.',
            'case' => $case,
            'text' => $this->caseAiText($case),
            'result' => null,
            'error' => null,
            'configured' => (new RiskAiClient())->isConfigured(),
            'canonical' => url('/casos/' . $case['slug'] . '/ia-emergencia'),
            'robots' => 'noindex,nofollow',
        ]);
    }

    public function aiClassify(Request $request, string $slug): Response
    {
        $case = (new CaseRepository())->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para el análisis de IA.');
        }

        $client = new RiskAiClient();
        $text = trim($request->string('text'));
        $error = null;
        $result = null;

        if (!$client->isConfigured()) {
            $error = 'La IA todavía no está configurada en este entorno.';
        } elseif (mb_strlen($text) < 10) {
            $error = 'El relato es demasiado corto para analizarlo.';
        } elseif (mb_strlen($text) > 2000) {
            $error = 'El relato supera los 2,000 caracteres.';
        } else {
            $last = (int) ($_SESSION['case_ai_last'] ?? 0);
            if (time() - $last < 2) {
                $error = 'Espera un par de segundos antes de volver a analizar.';
            } else {
                $_SESSION['case_ai_last'] = time();
                try {
                    $result = $client->classify($text);
                } catch (\RuntimeException $e) {
                    $error = $e->getMessage();
                    \App\Core\Logger::warning('Fallo de IA en caso agronómico', ['case' => $case['slug'], 'message' => $e->getMessage()]);
                }
            }
        }

        return $this->view('cases.ai', [
            'title' => 'Riesgo fitosanitario · ' . $case['title'],
            'metaDescription' => 'Analiza el relato de campo de este caso con un clasificador orientativo de riesgo fitosanitario.',
            'case' => $case,
            'text' => $text,
            'result' => $result,
            'error' => $error,
            'configured' => $client->isConfigured(),
            'canonical' => url('/casos/' . $case['slug'] . '/ia-emergencia'),
            'robots' => 'noindex,nofollow',
        ]);
    }

    public function pdf(Request $request, string $slug): Response
    {
        $case = (new CaseRepository())->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para descarga.');
        }

        $content = CasePdf::render($case);
        $filename = 'caso-' . $case['slug'] . '.pdf';
        return (new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]));
    }

    public function docx(Request $request, string $slug): Response
    {
        $case = (new CaseRepository())->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para descarga.');
        }

        $content = CaseDocx::render($case);
        $filename = 'caso-' . $case['slug'] . '.docx';
        return (new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]));
    }

    public function create(Request $request): Response
    {
        $user = Auth::requireLogin();
        $data = $this->caseFormData();
        $data['title'] = 'Compartir un caso agronómico · AgroDiagnóstico';
        $data['metaDescription'] = 'Documenta un caso agronómico con la estructura editorial de AgroDiagnóstico.';
        $data['case'] = null;
        $data['user'] = $user;
        $data['tagsValue'] = '';
        return $this->view('cases.form', $data);
    }

    public function store(Request $request): Response
    {
        $user = Auth::requireLogin();
        $action = $request->string('action', 'draft');
        [$data, $errors] = $this->validateCase($request, $action, $user);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($request->all());
            return $this->redirect('/casos/nuevo');
        }

        $data['folio'] = $this->caseFolio();
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['author_id'] = (int) $user['id'];
        $data['status'] = $action === 'submit' ? 'en_revision' : 'borrador';
        $data['consent_at'] = $data['producer_consent'] ? date('c') : null;

       $repo = new CaseRepository();
        try {
            $caseId = $repo->create($data);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Error al crear caso', ['message' => $e->getMessage()]);
            $this->withErrors(['general' => 'No fue posible guardar el caso. Revisa los datos e inténtalo de nuevo.']);
            $this->flashOld($request->all());
            return $this->redirect('/casos/nuevo');
        }

        $repo->replaceTags($caseId, $this->tagsFromInput($request->string('tags')));
        $this->saveImages($caseId, $request);
        Cache::flush();
        $this->audit('caso.creado', 'case', (string) $caseId, ['status' => $data['status']]);

        Flash::success($action === 'submit'
            ? 'Tu caso se envió a revisión editorial. Te avisaremos cuando haya una decisión.'
            : 'Tu borrador se guardó. Puedes completarlo y enviarlo cuando estés listo.');

        return $this->redirect('/mis-casos');
    }

    public function edit(Request $request, string $slug): Response
    {
        $user = Auth::requireLogin();
        $repo = new CaseRepository();
        $case = $repo->findBySlug($slug);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }
        if (!$this->canEditCase($case, $user)) {
            throw new \App\Core\HttpException(403, 'No puedes editar este caso en su estado actual.');
        }

        $data = $this->caseFormData();
        $data['title'] = 'Editar caso · AgroDiagnóstico';
        $data['metaDescription'] = 'Edita tu caso agronómico.';
        $data['case'] = $case;
        $data['user'] = $user;
        $data['tagsValue'] = implode(', ', $case['tags']);
        return $this->view('cases.form', $data);
    }

    public function update(Request $request, string $slug): Response
    {
        $user = Auth::requireLogin();
        $repo = new CaseRepository();
        $case = $repo->findBySlug($slug);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }
        if (!$this->canEditCase($case, $user)) {
            throw new \App\Core\HttpException(403, 'No puedes editar este caso en su estado actual.');
        }

        $action = $request->string('action', 'draft');
        [$data, $errors] = $this->validateCase($request, $action, $user);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($request->all());
            return $this->redirect('/casos/' . $case['slug'] . '/editar');
        }

        $status = $action === 'submit' ? 'en_revision' : (string) $case['status'];
        if (!in_array($user['role'], (array) config('app.moderators', []), true) && $action !== 'submit') {
            if (in_array($status, ['publicado', 'archivado', 'en_revision', 'rechazado', 'cambios_solicitados'], true)) {
                $status = 'borrador';
            }
        }
        $data['status'] = $status;
        if ($data['producer_consent'] && !$case['producer_consent']) {
            $data['consent_at'] = date('c');
        } else {
            $data['consent_at'] = $case['consent_at'];
        }

        $repo->update((int) $case['id'], $data);
        $repo->replaceTags((int) $case['id'], $this->tagsFromInput($request->string('tags')));
        $this->saveImages((int) $case['id'], $request);
        Cache::flush();
        $this->audit('caso.actualizado', 'case', (string) $case['id'], ['status' => $status]);

        Flash::success($action === 'submit'
            ? 'Tu caso se envió a revisión editorial.'
            : 'Los cambios se guardaron.');
        return $this->redirect('/mis-casos');
    }

    public function submit(Request $request, string $slug): Response
    {
        $user = Auth::requireVerified();
        $repo = new CaseRepository();
        $case = $repo->findBySlug($slug);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }
        if ((int) $case['author_id'] !== (int) $user['id'] && !in_array($user['role'], (array) config('app.moderators', []), true)) {
            throw new \App\Core\HttpException(403);
        }
        if (!in_array($case['status'], ['borrador', 'cambios_solicitados', 'rechazado'], true)) {
            Flash::error('Este caso no se puede enviar a revisión en su estado actual.');
            return $this->redirect('/mis-casos');
        }

        $missing = [];
        foreach ([
            'title' => 'título',
            'chief_complaint' => 'motivo de consulta',
            'crop_history' => 'crop_history',
            'field_inspection' => 'inspección de campo',
            'final_diagnosis' => 'diagnóstico final',
            'management' => 'manejo',
        ] as $field => $label) {
            if (trim((string) $case[$field]) === '') {
                $missing[] = $label;
            }
        }
        if (!$case['producer_consent'] || !$case['anonymized']) {
            $missing[] = 'consentimiento y anonimización';
        }
        if ($missing !== []) {
            Flash::error('Antes de enviar completa: ' . implode(', ', $missing) . '.');
            return $this->redirect('/casos/' . $case['slug'] . '/editar');
        }

        $repo->submitForReview((int) $case['id']);
        $this->audit('caso.enviado_revision', 'case', (string) $case['id']);
        Flash::success('Caso enviado a revisión. Un editor clínico lo revisará y recibirás una notificación.');
        return $this->redirect('/mis-casos');
    }

    public function mine(Request $request): Response
    {
        $user = Auth::requireLogin();
        $cases = (new CaseRepository())->byAuthor((int) $user['id']);
        $counts = ['total' => count($cases), 'publicado' => 0, 'en_revision' => 0, 'borrador' => 0, 'cambios_solicitados' => 0, 'rechazado' => 0];
        foreach ($cases as $case) {
            if (isset($counts[$case['status']])) {
                $counts[$case['status']]++;
            }
        }

        return $this->view('cases.mine', [
            'title' => 'Mis casos · AgroDiagnóstico',
            'metaDescription' => 'Gestiona tus casos agronómicos, borradores y envíos a revisión.',
            'cases' => $cases,
            'counts' => $counts,
            'user' => $user,
        ]);
    }

    public function comment(Request $request, string $slug): Response
    {
        $user = Auth::requireVerified();
        $repo = new CaseRepository();
        $case = $repo->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para comentarios.');
        }
        $body = trim($request->string('body'));
        if (mb_strlen($body) < 3 || mb_strlen($body) > 3000) {
            Flash::error('El comentario debe tener entre 3 y 3000 caracteres.');
            return $this->redirect('/casos/' . $case['slug'] . '#comentarios');
        }

        $repo->addComment((int) $case['id'], (int) $user['id'], $body);
        $this->audit('caso.comentado', 'case', (string) $case['id']);
        Flash::success('Tu comentario se publicó.');
        return $this->redirect('/casos/' . $case['slug'] . '#comentarios');
    }

    public function react(Request $request, string $slug): Response
    {
        $user = Auth::requireLogin();
        $repo = new CaseRepository();
        $case = $repo->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible.');
        }
        $active = $repo->toggleReaction((int) $case['id'], (int) $user['id']);
        $count = $repo->reactionCount((int) $case['id']);

        if ($request->wantsJson()) {
            return Response::json(['active' => $active, 'count' => $count]);
        }
        return $this->redirect('/casos/' . $case['slug'] . '#ficha');
    }

    public function deleteImage(Request $request, string $slug, string $imageId): Response
    {
        $user = Auth::requireLogin();
        $repo = new CaseRepository();
        $case = $repo->findBySlug($slug);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }
        if (!$this->canEditCase($case, $user)) {
            throw new \App\Core\HttpException(403);
        }
        $imageId = (int) $imageId;
        $image = null;
        foreach ($case['images'] as $candidate) {
            if ((int) $candidate['id'] === $imageId) {
                $image = $candidate;
                break;
            }
        }
        if ($image !== null) {
            $repo->deleteImage($imageId, (int) $case['id']);
            $path = BASE_PATH . '/public/uploads/cases/' . ltrim((string) $image['path'], '/');
            if (is_file($path)) {
                @unlink($path);
            }
            $this->audit('caso.imagen_eliminada', 'case', (string) $case['id'], ['image_id' => $imageId]);
        }
        Flash::info('Imagen eliminada.');
        return $this->redirect('/casos/' . $case['slug'] . '/editar');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateCase(Request $request, string $action, array $user): array
    {
        $catalog = new CatalogRepository();
        $cropIds = array_map('intval', array_column($catalog->crops(), 'id'));
        $stateCodes = array_column($catalog->states(), 'code');

        $cropsId = $request->int('crop_id');
        $varietyId = $request->int('variety_id');
        $data = [
            'title' => $request->string('title'),
            'crop_id' => $cropsId,
            'variety_id' => $varietyId > 0 ? $varietyId : null,
            'cycle' => $request->string('cycle', 'no_aplica'),
            'crop_stage' => $request->string('crop_stage', 'no_aplica'),
            'area_ha' => $request->string('area_ha') !== '' ? $request->string('area_ha') : null,
            'incidence_pct' => $request->string('incidence_pct') !== '' ? $request->int('incidence_pct') : null,
            'state_code' => $request->string('state_code') !== '' ? $request->string('state_code') : null,
            'municipality' => $request->string('municipality') !== '' ? $request->string('municipality') : null,
            'system' => $request->string('system', 'campo_abierto'),
            'chief_complaint' => $request->string('chief_complaint'),
            'crop_history' => $request->string('crop_history'),
            'field_inspection' => $request->string('field_inspection'),
            'lab_tests' => $request->string('lab_tests'),
            'differentials' => $request->string('differentials'),
            'final_diagnosis' => $request->string('final_diagnosis'),
            'reference_code' => $request->string('reference_code') !== '' ? $request->string('reference_code') : null,
            'management' => $request->string('management'),
            'outcome' => $request->string('outcome', 'desconocido'),
            'discussion' => $request->string('discussion'),
            'references_text' => $request->string('references_text'),
            'producer_consent' => $request->bool('producer_consent'),
            'anonymized' => $request->bool('anonymized'),
            'license' => $request->string('license', 'CC BY-NC-SA 4.0'),
        ];

        $rules = [
            'title' => 'required|min:10|max:180',
            'crop_id' => 'required|integer|in:' . implode(',', $cropIds),
            'cycle' => 'required|in:primavera-verano,otono-invierno,perenne,continuo,no_aplica',
            'crop_stage' => 'required|in:siembra,emergencia,vegetativo,floracion,fructificacion,llenado,madurez,cosecha,postcosecha,no_aplica',
            'area_ha' => 'nullable|numeric|between:0.01,100000',
            'incidence_pct' => 'nullable|integer|between:0,100',
            'state_code' => 'nullable|in:' . implode(',', $stateCodes),
            'municipality' => 'nullable|max:120',
            'system' => 'required|in:campo_abierto,invernadero,macrotunel,hidroponia,vivero,otro',
            'chief_complaint' => 'required|min:10|max:2000',
            'crop_history' => 'required|min:30|max:10000',
            'field_inspection' => 'required|min:30|max:10000',
            'lab_tests' => 'nullable|max:10000',
            'differentials' => 'nullable|max:5000',
            'final_diagnosis' => 'nullable|max:300',
            'reference_code' => 'nullable|max:30',
            'management' => 'nullable|max:10000',
            'outcome' => 'required|in:recuperado,en_manejo,perdida_parcial,perdida_total,en_observacion,desconocido',
            'discussion' => 'nullable|max:15000',
            'references_text' => 'nullable|max:5000',
            'license' => 'required|in:CC BY-NC-SA 4.0,CC BY 4.0,CC BY-ND 4.0,Todos los derechos reservados',
        ];
        $labels = [
            'title' => 'título del caso',
            'crop_id' => 'cultivo',
            'cycle' => 'ciclo',
            'crop_stage' => 'etapa fenológica',
            'area_ha' => 'superficie',
            'incidence_pct' => 'incidencia',
            'state_code' => 'estado',
            'municipality' => 'municipio',
            'system' => 'sistema de producción',
            'chief_complaint' => 'motivo de consulta',
            'crop_history' => 'historial de manejo',
            'field_inspection' => 'inspección de campo',
            'final_diagnosis' => 'diagnóstico final',
            'management' => 'manejo recomendado',
            'outcome' => 'resultado',
            'license' => 'licencia',
        ];
        $validator = $this->validator($data, $labels);
        $validator->validate($rules);

        if ($action === 'submit') {
            if (mb_strlen($data['final_diagnosis']) < 3) {
                $validator->add('final_diagnosis', 'Para enviar a revisión necesitas escribir el diagnóstico final.');
            }
            if (mb_strlen($data['management']) < 20) {
                $validator->add('management', 'Describe el manejo recomendado con al menos 20 caracteres.');
            }
            if (!$data['producer_consent']) {
                $validator->add('producer_consent', 'Confirma que cuentas con el consentimiento informado del productor.');
            }
            if (!$data['anonymized']) {
                $validator->add('anonymized', 'Confirma que eliminaste datos identificables del productor y de la parcela.');
            }
            if ($user['status'] !== 'verificado') {
                $validator->add('general', 'Tu cuenta debe estar verificada para enviar casos a revisión.');
            }
        }

        if ($validator->fails()) {
            return [$data, $validator->errors()];
        }

        if ($data['variety_id'] !== null) {
            $validVariety = Database::value(
                'select 1 from varieties where id = $1 and crop_id = $2',
                [$data['variety_id'], $data['crop_id']]
            );
            if ($validVariety === null) {
                return [$data, ['variety_id' => 'La variedad seleccionada no corresponde al cultivo.']];
            }
        }

        return [$data, []];
    }

    /** @param array<string, mixed> $case */
    private function caseAiText(array $case): string
    {
        $parts = [
            'Cultivo: ' . ($case['crop_name'] ?? ''),
            'Variedad: ' . ($case['variety_name'] ?? ''),
            'Ciclo: ' . cycle_label((string) ($case['cycle'] ?? 'no_aplica')),
            'Etapa fenológica: ' . stage_label((string) ($case['crop_stage'] ?? 'no_aplica')),
            'Superficie: ' . ($case['area_ha'] !== null ? $case['area_ha'] . ' ha' : ''),
            'Sistema de producción: ' . system_label((string) ($case['system'] ?? 'campo_abierto')),
            'Motivo de consulta: ' . $case['chief_complaint'],
            'Historial de manejo: ' . $case['crop_history'],
            'Inspección de campo: ' . $case['field_inspection'],
        ];
        return mb_substr(implode("\n", array_filter($parts, static fn (string $value): bool => trim($value) !== '')), 0, 2000);
    }
}
