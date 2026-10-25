<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Repositories\CatalogRepository;
use App\Repositories\UserRepository;

final class ProfileController extends BaseController
{
    public function show(Request $request): Response
    {
        $user = Auth::requireLogin();
        $caseCount = (int) Database::value('select count(*) from cases where author_id = $1 and deleted_at is null', [(int) $user['id']]);
        $published = (int) Database::value("select count(*) from cases where author_id = $1 and status = 'publicado' and deleted_at is null", [(int) $user['id']]);
        $comments = (int) Database::value('select count(*) from case_comments where user_id = $1 and status = \'visible\'', [(int) $user['id']]);
        $catalog = new CatalogRepository();

        return $this->view('profile.show', [
            'title' => 'Mi perfil · AgroDiagnóstico',
            'metaDescription' => 'Administra tu perfil profesional y tu actividad en AgroDiagnóstico.',
            'user' => $user,
            'caseCount' => $caseCount,
            'publishedCount' => $published,
            'commentCount' => $comments,
            'states' => $catalog->states(),
        ]);
    }

    public function update(Request $request): Response
    {
        $user = Auth::requireLogin();
        $catalog = new CatalogRepository();
        $states = array_column($catalog->states(), 'code');

        $data = [
            'name' => $request->string('name'),
            'license_number' => $request->string('license_number'),
            'institution' => $request->string('institution'),
            'state_code' => $request->string('state_code'),
            'bio' => $request->string('bio'),
        ];
        $validator = $this->validator($data, [
            'name' => 'nombre completo',
            'license_number' => 'cédula profesional',
            'institution' => 'institución u hospital',
            'state_code' => 'estado',
            'bio' => 'semblanza',
        ]);
        $validator->validate([
            'name' => 'required|min:2|max:120',
            'license_number' => 'required|min:4|max:40',
            'institution' => 'required|min:3|max:160',
            'state_code' => 'required|in:' . implode(',', $states),
            'bio' => 'nullable|max:1200',
        ]);

        $duplicate = Database::value(
            'select id from users where license_number = $1 and id <> $2',
            [$data['license_number'], (int) $user['id']]
        );
        if ($duplicate !== null) {
            $validator->add('license_number', 'Esta cédula profesional ya está registrada por otra cuenta.');
        }

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld($data);
            return $this->redirect('/perfil');
        }

        (new UserRepository())->updateProfile((int) $user['id'], $data);
        $this->audit('usuario.perfil_actualizado', 'user', (string) $user['id']);
        Flash::success('Tu perfil se actualizó correctamente.');
        return $this->redirect('/perfil');
    }

    public function deleteAccount(Request $request): Response
    {
        $user = Auth::requireLogin();
        $password = (string) $request->post('password', '');
        $confirmation = mb_strtoupper(trim($request->string('confirmation')));

        $errors = [];
        if (!password_verify($password, (string) $user['password_hash'])) {
            $errors['password'] = 'La contraseña no es correcta.';
        }
        if ($confirmation !== 'ELIMINAR') {
            $errors['confirmation'] = 'Escribe ELIMINAR para confirmar.';
        }
        if ($user['role'] === 'admin') {
            $admins = (int) Database::value(
                "select count(*) from users where role = 'admin' and status = 'verificado' and deleted_at is null"
            );
            if ($admins <= 1) {
                $errors['general'] = 'No puedes eliminar la última cuenta administradora del portal.';
            }
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            return $this->redirect('/perfil');
        }

        $this->deleteAvatarFile($user['avatar_url'] ?? null);
        (new UserRepository())->anonymizeAndDelete((int) $user['id']);
        $this->audit('usuario.eliminado', 'user', (string) $user['id']);
        Auth::logout();
        Flash::success('Tu cuenta fue eliminada. Tus datos personales se anonimizaron; los casos publicados permanecen en el archivo sin tu nombre.');
        return $this->redirect('/');
    }

    public function avatar(Request $request): Response
    {
        $user = Auth::requireLogin();
        $file = $request->file('avatar');
        if ($file === null) {
            Flash::error('Selecciona una imagen para tu foto de perfil.');
            return $this->redirect('/perfil');
        }

        try {
            $image = Upload::image(
                $file,
                'user-' . (int) $user['id'],
                BASE_PATH . '/public/uploads/avatars',
                '/uploads/avatars',
                600
            );
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            return $this->redirect('/perfil');
        }

        $this->deleteAvatarFile($user['avatar_url'] ?? null);
        (new UserRepository())->updateAvatar((int) $user['id'], $image['url']);
        $this->audit('usuario.avatar_actualizado', 'user', (string) $user['id']);
        Flash::success('Tu foto de perfil se actualizó.');
        return $this->redirect('/perfil');
    }

    public function removeAvatar(Request $request): Response
    {
        $user = Auth::requireLogin();
        $this->deleteAvatarFile($user['avatar_url'] ?? null);
        (new UserRepository())->updateAvatar((int) $user['id'], null);
        $this->audit('usuario.avatar_eliminado', 'user', (string) $user['id']);
        Flash::success('Tu foto de perfil se eliminó.');
        return $this->redirect('/perfil');
    }

    private function deleteAvatarFile(?string $url): void
    {
        if ($url === null || !str_starts_with($url, '/uploads/avatars/')) {
            return;
        }
        $relative = substr($url, strlen('/uploads/avatars/'));
        if ($relative === '' || str_contains($relative, '..')) {
            return;
        }
        $path = BASE_PATH . '/public/uploads/avatars/' . $relative;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function password(Request $request): Response
    {
        $user = Auth::requireLogin();
        $current = (string) $request->post('current_password', '');
        $new = (string) $request->post('password', '');
        $confirmation = (string) $request->post('password_confirmation', '');

        $errors = [];
        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'La contraseña actual no es correcta.';
        }
        if (mb_strlen($new) < 10) {
            $errors['password'] = 'La nueva contraseña debe tener al menos 10 caracteres.';
        } elseif ($new !== $confirmation) {
            $errors['password_confirmation'] = 'La confirmación no coincide.';
        }
        if ($errors !== []) {
            $this->withErrors($errors);
            return $this->redirect('/perfil');
        }

        (new UserRepository())->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        Auth::logout();
        Flash::success('Tu contraseña se actualizó. Inicia sesión nuevamente.');
        return $this->redirect('/acceder');
    }
}
