<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Str;
use App\Core\Response;
use App\Repositories\CaseRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\UserRepository;

final class AdminController extends BaseController
{
    public function dashboard(Request $request): Response
    {
        Auth::requireModerator();
        $cases = new CaseRepository();
        $users = new UserRepository();
        $pending = $cases->search(['status' => 'en_revision'], 1, 5, true);
        $recentComments = Database::select(
            "select cc.*, u.name as author_name, c.slug as case_slug, c.title as case_title
             from case_comments cc
             join users u on u.id = cc.user_id
             join cases c on c.id = cc.case_id
             where cc.status = 'visible'
             order by cc.created_at desc limit 6"
        );

        return $this->view('admin.dashboard', [
            'title' => 'Panel editorial · AgroDiagnóstico',
            'metaDescription' => 'Panel de administración y moderación de casos.',
            'stats' => $cases->adminStats(),
            'pendingCases' => $pending['items'],
            'pendingUsers' => array_slice($users->paginate('', 1, 10, 'pendiente')['items'], 0, 6),
            'recentComments' => $recentComments,
        ], 'admin');
    }

    public function cases(Request $request): Response
    {
        Auth::requireModerator();
        $filters = [
            'q' => $request->string('q'),
            'status' => $request->string('status'),
        ];
        $page = max(1, $request->int('page', 1));
        $perPage = (int) config('app.pagination.admin', 20);
        $result = (new CaseRepository())->search($filters, $page, $perPage, true);

        return $this->view('admin.cases', [
            'title' => 'Casos · Panel editorial',
            'metaDescription' => 'Administra los casos agronómicos del portal.',
            'cases' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'paginator' => new Paginator($result['total'], $perPage, $page),
        ], 'admin');
    }

    public function reviewCase(Request $request, string $id): Response
    {
        Auth::requireModerator();
        $repo = new CaseRepository();
        $case = $repo->findById((int) $id);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }

        return $this->view('admin.case', [
            'title' => 'Revisar caso · Panel editorial',
            'metaDescription' => 'Revisión editorial del caso agronómico.',
            'case' => $case,
            'reviews' => $repo->reviews((int) $case['id']),
            'comments' => $repo->comments((int) $case['id'], true),
        ], 'admin');
    }

    public function review(Request $request, string $id): Response
    {
        $user = Auth::requireModerator();
        $repo = new CaseRepository();
        $case = $repo->findById((int) $id);
        if ($case === null) {
            $this->notFound('No encontramos este caso.');
        }

        $decision = $request->string('decision');
        $comments = trim($request->string('comments'));
        if (!in_array($decision, ['aprobado', 'rechazado', 'cambios'], true)) {
            Flash::error('Selecciona una decisión editorial válida.');
            return $this->redirect('/admin/casos/' . $case['id']);
        }
        if ($decision !== 'aprobado' && mb_strlen($comments) < 10) {
            Flash::error('Explica al autor el motivo de la decisión (mínimo 10 caracteres).');
            return $this->redirect('/admin/casos/' . $case['id']);
        }

        if ($decision === 'aprobado') {
            $missing = [];
            if (mb_strlen((string) $case['final_diagnosis']) < 3) {
                $missing[] = 'diagnóstico final';
            }
            if (mb_strlen((string) $case['management']) < 20) {
                $missing[] = 'manejo';
            }
            if (!$case['producer_consent'] || !$case['anonymized']) {
                $missing[] = 'consentimiento y anonimización';
            }
            if ($missing !== []) {
                Flash::error('No se puede publicar: falta ' . implode(', ', $missing) . '.');
                return $this->redirect('/admin/casos/' . $case['id']);
            }
            $repo->publish((int) $case['id']);
            $message = 'Caso publicado en el portal.';
        } elseif ($decision === 'rechazado') {
            $repo->reject((int) $case['id']);
            $message = 'Caso rechazado. El autor podrá corregirlo y reenviarlo.';
        } else {
            $repo->requestChanges((int) $case['id']);
            $message = 'Se solicitaron cambios al autor.';
        }

        $repo->addReview((int) $case['id'], (int) $user['id'], $decision, $comments);
        Cache::flush();
        $this->audit('caso.revision', 'case', (string) $case['id'], ['decision' => $decision]);
        Flash::success($message);
        return $this->redirect('/admin/casos/' . $case['id']);
    }

    public function users(Request $request): Response
    {
        Auth::requireAdmin();
        $q = $request->string('q');
        $status = $request->string('status');
        $role = $request->string('role');
        $page = max(1, $request->int('page', 1));
        $perPage = (int) config('app.pagination.admin', 20);
        $result = (new UserRepository())->paginate($q, $page, $perPage, $status, $role);

        return $this->view('admin.users', [
            'title' => 'Usuarios · Panel editorial',
            'metaDescription' => 'Gestiona cuentas, verificaciones y roles.',
            'users' => $result['items'],
            'total' => $result['total'],
            'filters' => ['q' => $q, 'status' => $status, 'role' => $role],
            'paginator' => new Paginator($result['total'], $perPage, $page),
        ], 'admin');
    }

    public function updateUserStatus(Request $request, string $id): Response
    {
        $admin = Auth::requireAdmin();
        $status = $request->string('status');
        if (!in_array($status, ['pendiente', 'verificado', 'suspendido'], true)) {
            Flash::error('Estado de cuenta no válido.');
            return $this->back('/admin/usuarios');
        }
        if ((int) $id === (int) $admin['id'] && $status !== 'verificado') {
            Flash::error('No puedes cambiar tu propio estado de verificación.');
            return $this->back('/admin/usuarios');
        }
        (new UserRepository())->setStatus((int) $id, $status);
        $this->audit('usuario.estado', 'user', $id, ['status' => $status]);
        Flash::success('Estado de la cuenta actualizado.');
        return $this->back('/admin/usuarios');
    }

    public function updateUserRole(Request $request, string $id): Response
    {
        $admin = Auth::requireAdmin();
        $role = $request->string('role');
        if (!in_array($role, ['agronomo', 'editor', 'admin'], true)) {
            Flash::error('Rol no válido.');
            return $this->back('/admin/usuarios');
        }
        if ((int) $id === (int) $admin['id'] && $role !== 'admin') {
            Flash::error('No puedes quitarte a ti mismo el rol de administrador.');
            return $this->back('/admin/usuarios');
        }
        if ($role !== 'admin') {
            $admins = (int) Database::value("select count(*) from users where role = 'admin' and status = 'verificado' and deleted_at is null");
            $target = Database::first('select role from users where id = $1', [(int) $id]);
            if ($target !== null && $target['role'] === 'admin' && $admins <= 1) {
                Flash::error('Debe existir al menos un administrador verificado.');
                return $this->back('/admin/usuarios');
            }
        }
        (new UserRepository())->setRole((int) $id, $role);
        $this->audit('usuario.rol', 'user', $id, ['role' => $role]);
        Flash::success('Rol actualizado.');
        return $this->back('/admin/usuarios');
    }

    public function resources(Request $request): Response
    {
        Auth::requireAdmin();
        $repo = new ResourceRepository();
        return $this->view('admin.resources', [
            'title' => 'Recursos · Panel editorial',
            'metaDescription' => 'Administra el directorio de recursos externos.',
            'resources' => $repo->all(),
        ], 'admin');
    }

    public function resourceForm(Request $request, ?string $id = null): Response
    {
        Auth::requireAdmin();
        $resource = $id !== null ? (new ResourceRepository())->find((int) $id) : null;
        if ($id !== null && $resource === null) {
            $this->notFound('No encontramos este recurso.');
        }
        return $this->view('admin.resource-form', [
            'title' => ($resource === null ? 'Nuevo recurso' : 'Editar recurso') . ' · Panel editorial',
            'metaDescription' => 'Formulario de recurso externo.',
            'resource' => $resource,
            'tagsValue' => $resource !== null ? implode(', ', $resource['tags'] ?? []) : '',
        ], 'admin');
    }

    public function resourceStore(Request $request): Response
    {
        Auth::requireAdmin();
        [$data, $errors] = $this->validateResource($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($request->all());
            return $this->redirect('/admin/recursos/nuevo');
        }
        try {
            $id = (new ResourceRepository())->create($data);
        } catch (\Throwable $e) {
            $this->withErrors(['url' => 'Ya existe un recurso con esta URL o los datos no son válidos.']);
            $this->flashOld($request->all());
            return $this->redirect('/admin/recursos/nuevo');
        }
        Cache::flush();
        $this->audit('recurso.creado', 'resource', (string) $id);
        Flash::success('Recurso creado.');
        return $this->redirect('/admin/recursos');
    }

    public function resourceUpdate(Request $request, string $id): Response
    {
        Auth::requireAdmin();
        $repo = new ResourceRepository();
        $resource = $repo->find((int) $id);
        if ($resource === null) {
            $this->notFound('No encontramos este recurso.');
        }
        [$data, $errors] = $this->validateResource($request, (int) $id);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($request->all());
            return $this->redirect('/admin/recursos/' . $id . '/editar');
        }
        try {
            $repo->update((int) $id, $data);
        } catch (\Throwable $e) {
            $this->withErrors(['url' => 'Ya existe un recurso con esta URL o los datos no son válidos.']);
            $this->flashOld($request->all());
            return $this->redirect('/admin/recursos/' . $id . '/editar');
        }
        Cache::flush();
        $this->audit('recurso.actualizado', 'resource', $id);
        Flash::success('Recurso actualizado.');
        return $this->redirect('/admin/recursos');
    }

    public function resourceDelete(Request $request, string $id): Response
    {
        Auth::requireAdmin();
        (new ResourceRepository())->delete((int) $id);
        Cache::flush();
        $this->audit('recurso.eliminado', 'resource', $id);
        Flash::success('Recurso eliminado.');
        return $this->redirect('/admin/recursos');
    }

    public function hideComment(Request $request, string $id): Response
    {
        Auth::requireModerator();
        $comment = Database::first('select * from case_comments where id = $1', [(int) $id]);
        if ($comment === null) {
            $this->notFound('No encontramos este comentario.');
        }
        (new CaseRepository())->hideComment((int) $id);
        $this->audit('comentario.oculto', 'comment', $id);
        Flash::success('Comentario oculto.');
        return $this->back('/admin');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateResource(Request $request, ?int $id = null): array
    {
        $tags = $this->tagsFromInput($request->string('tags'));
        $title = $request->string('title');
        $slug = Str::slug($request->string('slug') !== '' ? $request->string('slug') : $title);
        $baseSlug = $slug;
        $suffix = 2;
        while (Database::value('select id from resources where slug = $1 and id <> $2', [$slug, $id ?? 0]) !== null) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
        $steps = preg_split('/\r\n|\r|\n/', $request->string('how_to')) ?: [];
        $steps = array_values(array_filter(array_map('trim', $steps), static fn (string $step): bool => $step !== ''));
        $data = [
            'title' => $title,
            'description' => $request->string('description'),
            'url' => $request->string('url'),
            'category' => $request->string('category'),
            'organization' => $request->string('organization'),
            'country' => $request->string('country'),
            'access' => $request->string('access'),
            'license' => $request->string('license'),
            'language' => $request->string('language', 'es'),
            'tags' => '{' . implode(',', array_map(static fn (string $tag): string => '"' . str_replace('"', '\\"', $tag) . '"', $tags)) . '}',
            'featured' => $request->bool('featured'),
            'sort_order' => $request->int('sort_order', 100),
            'slug' => $slug,
            'what_for' => $request->string('what_for'),
            'how_to' => $steps,
            'requirements' => $request->string('requirements'),
            'guide' => $request->string('guide'),
            'action_label' => $request->string('action_label'),
            'reviewed_at' => $request->string('reviewed_at') !== '' ? $request->string('reviewed_at') : null,
        ];
        $validator = $this->validator($data, [
            'title' => 'título', 'description' => 'descripción', 'url' => 'URL',
            'category' => 'categoría', 'access' => 'acceso', 'what_for' => 'para qué sirve',
            'how_to' => 'cómo usarlo', 'requirements' => 'requisitos', 'guide' => 'guía',
        ]);
        $validator->validate([
            'title' => 'required|min:4|max:160',
            'description' => 'required|min:20|max:1200',
            'url' => 'required|url|max:500',
            'category' => 'required|in:dataset,api,repositorio,vigilancia,guia,ml,institucion,biblioteca,directorio,revista,plataforma',
            'access' => 'required|in:abierto,solicitud,pago,mixto',
            'organization' => 'nullable|max:160',
            'country' => 'nullable|max:80',
            'license' => 'nullable|max:160',
            'language' => 'required|max:10',
            'sort_order' => 'required|integer|between:0,999',
            'slug' => 'required|min:3|max:100',
            'what_for' => 'required|min:20|max:2000',
            'requirements' => 'nullable|max:2000',
            'guide' => 'nullable|max:4000',
            'action_label' => 'nullable|max:80',
            'reviewed_at' => 'nullable|date',
        ]);
        if (count($steps) < 2) {
            $validator->add('how_to', 'Escribe al menos dos pasos, uno por línea.');
        }
        return [$data, $validator->errors()];
    }
}
