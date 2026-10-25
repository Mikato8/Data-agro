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
use App\Services\UrgencyAiClient;

final class CaseController extends BaseController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'species' => $request->string('species'),
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
            'title' => 'Casos clínicos veterinarios · Diagnóstico Veterinario',
            'metaDescription' => 'Explora casos clínicos de perros, gatos, équidos, bovinos y otras especies, revisados por médicos veterinarios en México.',
            'cases' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'paginator' => new Paginator($result['total'], $perPage, $page),
            'speciesList' => $catalog->species(),
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
            'title' => $case['title'] . ' · Diagnóstico Veterinario',
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
            'title' => 'IA de urgencia · ' . $case['title'],
            'metaDescription' => 'Analiza el relato clínico de este caso con un clasificador SetFit + ONNX de urgencia veterinaria.',
            'case' => $case,
            'text' => $this->caseAiText($case),
            'result' => null,
            'error' => null,
            'configured' => (new UrgencyAiClient())->isConfigured(),
            'canonical' => url('/casos/' . $case['slug'] . '/ia-urgencia'),
            'robots' => 'noindex,nofollow',
        ]);
    }

    public function aiClassify(Request $request, string $slug): Response
    {
        $case = (new CaseRepository())->findPublishedBySlug($slug);
        if ($case === null) {
            $this->notFound('Este caso no está disponible para el análisis de IA.');
        }

        $client = new UrgencyAiClient();
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
                    \App\Core\Logger::warning('Fallo de IA en caso clínico', ['case' => $case['slug'], 'message' => $e->getMessage()]);
                }
            }
        }

        return $this->view('cases.ai', [
            'title' => 'IA de urgencia · ' . $case['title'],
            'metaDescription' => 'Analiza el relato clínico de este caso con un clasificador SetFit + ONNX de urgencia veterinaria.',
            'case' => $case,
            'text' => $text,
            'result' => $result,
            'error' => $error,
            'configured' => $client->isConfigured(),
            'canonical' => url('/casos/' . $case['slug'] . '/ia-urgencia'),
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
        $data['title'] = 'Compartir un caso clínico · Diagnóstico Veterinario';
        $data['metaDescription'] = 'Documenta un caso clínico con la estructura editorial de Diagnóstico Veterinario.';
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
        $data['consent_at'] = $data['owner_consent'] ? date('c') : null;

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
        $data['title'] = 'Editar caso · Diagnóstico Veterinario';
        $data['metaDescription'] = 'Edita tu caso clínico.';
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
        if ($data['owner_consent'] && !$case['owner_consent']) {
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
            'anamnesis' => 'anamnesis',
            'physical_exam' => 'exploración física',
            'final_diagnosis' => 'diagnóstico final',
            'treatment' => 'tratamiento',
        ] as $field => $label) {
            if (trim((string) $case[$field]) === '') {
                $missing[] = $label;
            }
        }
        if (!$case['owner_consent'] || !$case['anonymized']) {
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
            'title' => 'Mis casos · Diagnóstico Veterinario',
            'metaDescription' => 'Gestiona tus casos clínicos, borradores y envíos a revisión.',
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
        $speciesIds = array_map('intval', array_column($catalog->species(), 'id'));
        $stateCodes = array_column($catalog->states(), 'code');

        $speciesId = $request->int('species_id');
        $breedId = $request->int('breed_id');
        $data = [
            'title' => $request->string('title'),
            'species_id' => $speciesId,
            'breed_id' => $breedId > 0 ? $breedId : null,
            'sex' => $request->string('sex', 'desconocido'),
            'age_value' => $request->string('age_value') !== '' ? $request->int('age_value') : null,
            'age_unit' => $request->string('age_unit') !== '' ? $request->string('age_unit') : null,
            'weight_kg' => $request->string('weight_kg') !== '' ? $request->string('weight_kg') : null,
            'state_code' => $request->string('state_code') !== '' ? $request->string('state_code') : null,
            'setting' => $request->string('setting', 'clinica'),
            'chief_complaint' => $request->string('chief_complaint'),
            'anamnesis' => $request->string('anamnesis'),
            'physical_exam' => $request->string('physical_exam'),
            'diagnostics' => $request->string('diagnostics'),
            'differentials' => $request->string('differentials'),
            'final_diagnosis' => $request->string('final_diagnosis'),
            'icd11_code' => $request->string('icd11_code') !== '' ? $request->string('icd11_code') : null,
            'treatment' => $request->string('treatment'),
            'outcome' => $request->string('outcome', 'desconocido'),
            'discussion' => $request->string('discussion'),
            'references_text' => $request->string('references_text'),
            'owner_consent' => $request->bool('owner_consent'),
            'anonymized' => $request->bool('anonymized'),
            'license' => $request->string('license', 'CC BY-NC-SA 4.0'),
        ];

        $rules = [
            'title' => 'required|min:10|max:180',
            'species_id' => 'required|integer|in:' . implode(',', $speciesIds),
            'sex' => 'required|in:macho,hembra,desconocido',
            'age_value' => 'nullable|integer|between:0,100',
            'age_unit' => 'nullable|in:dias,semanas,meses,anios',
            'weight_kg' => 'nullable|numeric|between:0.1,5000',
            'state_code' => 'nullable|in:' . implode(',', $stateCodes),
            'setting' => 'required|in:clinica,hospital,campo,laboratorio,otro',
            'chief_complaint' => 'required|min:10|max:2000',
            'anamnesis' => 'required|min:30|max:10000',
            'physical_exam' => 'required|min:30|max:10000',
            'diagnostics' => 'nullable|max:10000',
            'differentials' => 'nullable|max:5000',
            'final_diagnosis' => 'nullable|max:300',
            'icd11_code' => 'nullable|max:30',
            'treatment' => 'nullable|max:10000',
            'outcome' => 'required|in:curado,en_tratamiento,secuela,fallecido,eutanasia,desconocido',
            'discussion' => 'nullable|max:15000',
            'references_text' => 'nullable|max:5000',
            'license' => 'required|in:CC BY-NC-SA 4.0,CC BY 4.0,CC BY-ND 4.0,Todos los derechos reservados',
        ];
        $labels = [
            'title' => 'título del caso',
            'species_id' => 'especie',
            'sex' => 'sexo',
            'age_value' => 'edad',
            'weight_kg' => 'peso',
            'state_code' => 'estado',
            'setting' => 'entorno clínico',
            'chief_complaint' => 'motivo de consulta',
            'anamnesis' => 'anamnesis',
            'physical_exam' => 'exploración física',
            'final_diagnosis' => 'diagnóstico final',
            'treatment' => 'tratamiento',
            'outcome' => 'desenlace',
            'license' => 'licencia',
        ];
        $validator = $this->validator($data, $labels);
        $validator->validate($rules);

        if ($action === 'submit') {
            if (mb_strlen($data['final_diagnosis']) < 3) {
                $validator->add('final_diagnosis', 'Para enviar a revisión necesitas escribir el diagnóstico final.');
            }
            if (mb_strlen($data['treatment']) < 20) {
                $validator->add('treatment', 'Describe el tratamiento o manejo con al menos 20 caracteres.');
            }
            if (!$data['owner_consent']) {
                $validator->add('owner_consent', 'Confirma que cuentas con consentimiento informado del tutor.');
            }
            if (!$data['anonymized']) {
                $validator->add('anonymized', 'Confirma que eliminaste datos identificables del tutor y del paciente.');
            }
            if ($user['status'] !== 'verificado') {
                $validator->add('general', 'Tu cuenta debe estar verificada para enviar casos a revisión.');
            }
        }

        if ($validator->fails()) {
            return [$data, $validator->errors()];
        }

        if ($data['breed_id'] !== null) {
            $validBreed = Database::value(
                'select 1 from breeds where id = $1 and species_id = $2',
                [$data['breed_id'], $data['species_id']]
            );
            if ($validBreed === null) {
                return [$data, ['breed_id' => 'La raza seleccionada no corresponde a la especie.']];
            }
        }

        return [$data, []];
    }

    /** @param array<string, mixed> $case */
    private function caseAiText(array $case): string
    {
        $parts = [
            'Especie: ' . $case['species_name'],
            'Sexo: ' . sex_label((string) $case['sex']),
            'Edad: ' . age_label($case['age_value'] !== null ? (int) $case['age_value'] : null, $case['age_unit']),
            'Motivo de consulta: ' . $case['chief_complaint'],
            'Anamnesis: ' . $case['anamnesis'],
            'Exploración física: ' . $case['physical_exam'],
        ];
        return mb_substr(implode("\n", array_filter($parts, static fn (string $value): bool => trim($value) !== '')), 0, 2000);
    }
}
