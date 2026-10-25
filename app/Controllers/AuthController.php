<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Str;
use App\Repositories\CatalogRepository;
use App\Repositories\UserRepository;
use App\Services\CedulaVerifier;

final class AuthController extends BaseController
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        return $this->view('auth.login', [
            'title' => 'Acceder · Diagnóstico Veterinario',
            'metaDescription' => 'Inicia sesión en tu cuenta de Diagnóstico Veterinario.',
        ]);
    }

    public function login(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        $email = Str::normalizeEmail($request->string('email'));
        $password = (string) $request->post('password', '');
        $data = ['email' => $email];

        $validator = $this->validator($data, ['email' => 'correo electrónico']);
        if (!$validator->validate(['email' => 'required|email'])) {
            $this->withErrors($validator->errors());
            $this->flashOld($data);
            return $this->redirect('/acceder');
        }
        if ($password === '') {
            $this->withErrors(['password' => 'Escribe tu contraseña.']);
            $this->flashOld($data);
            return $this->redirect('/acceder');
        }

        $result = Auth::attempt($email, $password);
        if (!$result['ok']) {
            $this->withErrors(['email' => $result['message']]);
            $this->flashOld($data);
            return $this->redirect('/acceder');
        }

        Flash::success('Bienvenido de nuevo. Tu sesión está activa.');
        return $this->redirect(Auth::intendedUrl());
    }

    public function showRegister(Request $request): Response
    {
        $next = $request->string('next') === 'panel' ? 'panel' : '';
        if (Auth::check()) {
            return $this->redirect($next === 'panel' ? '/panel' : '/');
        }
        $catalog = new CatalogRepository();
        return $this->view('auth.register', [
            'title' => 'Crear cuenta · Diagnóstico Veterinario',
            'metaDescription' => 'Regístrate como médico veterinario para compartir casos clínicos y participar en la comunidad.',
            'states' => $catalog->states(),
            'next' => $next,
        ]);
    }

    public function register(Request $request): Response
    {
        $next = $request->string('next') === 'panel' ? 'panel' : '';
        $registerUrl = $next === 'panel' ? '/registro?next=panel' : '/registro';
        if (Auth::check()) {
            return $this->redirect($next === 'panel' ? '/panel' : '/');
        }

        $catalog = new CatalogRepository();
        $states = array_column($catalog->states(), 'code');
        $data = [
            'name' => $request->string('name'),
            'email' => Str::normalizeEmail($request->string('email')),
            'license_number' => $request->string('license_number'),
            'institution' => $request->string('institution'),
            'state_code' => $request->string('state_code'),
            'bio' => $request->string('bio'),
        ];

        $rules = [
            'name' => 'required|min:2|max:120',
            'email' => 'required|email|max:254',
            'license_number' => 'required|min:4|max:40',
            'institution' => 'required|min:3|max:160',
            'state_code' => 'required|in:' . implode(',', $states),
            'bio' => 'nullable|max:1200',
        ];
        $validator = $this->validator($data, [
            'name' => 'nombre completo',
            'email' => 'correo electrónico',
            'license_number' => 'cédula profesional',
            'institution' => 'institución u hospital',
            'state_code' => 'estado',
            'bio' => 'semblanza',
        ]);
        $validator->validate($rules);

        $password = (string) $request->post('password', '');
        $confirmation = (string) $request->post('password_confirmation', '');
        if (mb_strlen($password) < 10) {
            $validator->add('password', 'La contraseña debe tener al menos 10 caracteres.');
        } elseif ($password !== $confirmation) {
            $validator->add('password_confirmation', 'La confirmación de la contraseña no coincide.');
        }
        if (!$request->bool('terms')) {
            $validator->add('terms', 'Debes aceptar los términos y el aviso de privacidad.');
        }

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
            $this->flashOld($data);
            return $this->redirect($registerUrl);
        }

        $users = new UserRepository();
        if ($users->findByEmail($data['email']) !== null) {
            $this->withErrors(['email' => 'Ya existe una cuenta con este correo electrónico.']);
            $this->flashOld($data);
            return $this->redirect($registerUrl);
        }
        if (Database::value('select id from users where license_number = $1', [$data['license_number']]) !== null) {
            $this->withErrors(['license_number' => 'Esta cédula profesional ya está registrada. Si es un error, contáctanos.']);
            $this->flashOld($data);
            return $this->redirect($registerUrl);
        }

        $verification = null;
        $verifier = new CedulaVerifier();
        if ($verifier->isEnabled()) {
            if (!$verifier->isConfigured()) {
                \App\Core\Logger::error('Verificación de cédula activada sin credenciales');
                $this->withErrors(['license_number' => 'La verificación automática de cédula no está disponible en este momento. Intenta más tarde o contáctanos.']);
                $this->flashOld($data);
                return $this->redirect($registerUrl);
            }
            try {
                $verification = $verifier->verify($data['license_number'], $data['name']);
            } catch (\RuntimeException $e) {
                \App\Core\Logger::warning('Fallo de verificación de cédula', ['message' => $e->getMessage()]);
                $this->withErrors(['license_number' => 'No pudimos verificar tu cédula en este momento. Intenta de nuevo en unos minutos o contáctanos.']);
                $this->flashOld($data);
                return $this->redirect($registerUrl);
            }

            if (!$verification['found']) {
                $this->withErrors(['license_number' => 'No encontramos esa cédula en el Registro Nacional de Profesionistas. Revisa el número o contáctanos.']);
                $this->flashOld($data);
                return $this->redirect($registerUrl);
            }
            if (!$verification['name_match']) {
                $this->withErrors(['name' => 'La cédula existe, pero el nombre no coincide con el registrado en la SEP. Revisa ambos datos.']);
                $this->flashOld($data);
                return $this->redirect($registerUrl);
            }
            if (!$verification['veterinary']) {
                $this->withErrors(['license_number' => 'La cédula existe, pero no corresponde a medicina veterinaria. Este portal es exclusivo para médicos veterinarios.']);
                $this->flashOld($data);
                return $this->redirect($registerUrl);
            }
        }

        $verified = (bool) config('app.auto_verify_users', false) || $verification !== null;
        try {
            $id = $users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'veterinario',
                'status' => $verified ? 'verificado' : 'pendiente',
                'license_number' => $data['license_number'],
                'institution' => $data['institution'],
                'state_code' => $data['state_code'],
                'bio' => $data['bio'] !== '' ? $data['bio'] : null,
                'license_verified_at' => $verification !== null ? date('c') : null,
                'license_verified_name' => $verification['name'] ?? null,
                'license_verified_title' => $verification['title'] ?? null,
                'license_verified_institution' => $verification['institution'] ?? null,
                'license_verification_source' => $verification['source'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Error al registrar usuario', ['message' => $e->getMessage()]);
            $this->withErrors(['email' => 'No fue posible crear la cuenta. Revisa los datos e inténtalo de nuevo.']);
            $this->flashOld($data);
            return $this->redirect($registerUrl);
        }

        $user = $users->findById($id);
        if ($user !== null) {
            Auth::login($user);
        }

        $this->audit('usuario.registro', 'user', (string) $id, ['status' => $verified ? 'verificado' : 'pendiente', 'license_source' => $verification['source'] ?? 'manual']);

        if ($verification !== null) {
            Flash::success('Tu cédula fue validada con el Registro Nacional de Profesionistas. Tu cuenta está verificada y ya puedes usar el panel clínico.');
        } elseif ($verified) {
            Flash::success($next === 'panel' ? 'Tu cuenta está lista. Ya puedes usar el panel clínico.' : 'Tu cuenta está lista. Ya puedes compartir casos clínicos.');
        } else {
            Flash::success($next === 'panel' ? 'Tu cuenta fue creada. Un editor revisará tu cédula; mientras tanto puedes usar el panel clínico.' : 'Tu cuenta fue creada. Un editor revisará tu cédula profesional; mientras tanto puedes guardar borradores de casos.');
        }
        return $this->redirect($next === 'panel' ? '/panel' : '/mis-casos');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Flash::info('Cerraste sesión correctamente.');
        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('auth.forgot', [
            'title' => 'Recuperar acceso · Diagnóstico Veterinario',
            'metaDescription' => 'Solicita un enlace para restablecer tu contraseña.',
        ]);
    }

    public function sendReset(Request $request): Response
    {
        $email = Str::normalizeEmail($request->string('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->withErrors(['email' => 'Escribe un correo electrónico válido.']);
            $this->flashOld(['email' => $email]);
            return $this->redirect('/recuperar');
        }

        $user = (new UserRepository())->findByEmail($email);
        if ($user !== null) {
            $token = Str::token(32);
            Database::execute('delete from password_resets where user_id = $1 and used_at is null', [(int) $user['id']]);
            Database::execute(
                'insert into password_resets (user_id, token_hash, expires_at)
                 values ($1, $2, now() + interval \'60 minutes\')',
                [(int) $user['id'], hash('sha256', $token)]
            );
            $sent = Mailer::passwordReset($user, $token);
            if (!$sent && config('app.debug')) {
                Flash::info('Enlace de recuperación (modo desarrollo): ' . url('/recuperar/' . $token));
            }
        }

        Flash::success('Si el correo está registrado, recibirás un enlace para restablecer tu contraseña. Revisa también la carpeta de spam.');
        return $this->redirect('/recuperar');
    }

    public function showReset(Request $request, string $token): Response
    {
        $reset = $this->validReset($token);
        if ($reset === null) {
            Flash::error('El enlace de recuperación no es válido o ya venció. Solicita uno nuevo.');
            return $this->redirect('/recuperar');
        }
        return $this->view('auth.reset', [
            'title' => 'Nueva contraseña · Diagnóstico Veterinario',
            'metaDescription' => 'Crea una nueva contraseña para tu cuenta.',
            'token' => $token,
        ]);
    }

    public function reset(Request $request, string $token): Response
    {
        $reset = $this->validReset($token);
        if ($reset === null) {
            Flash::error('El enlace de recuperación no es válido o ya venció. Solicita uno nuevo.');
            return $this->redirect('/recuperar');
        }

        $password = (string) $request->post('password', '');
        $confirmation = (string) $request->post('password_confirmation', '');
        $errors = [];
        if (mb_strlen($password) < 10) {
            $errors['password'] = 'La contraseña debe tener al menos 10 caracteres.';
        } elseif ($password !== $confirmation) {
            $errors['password_confirmation'] = 'La confirmación de la contraseña no coincide.';
        }
        if ($errors !== []) {
            $this->withErrors($errors);
            return $this->redirect('/recuperar/' . rawurlencode($token));
        }

        Database::execute('update users set password_hash = $1 where id = $2', [
            password_hash($password, PASSWORD_DEFAULT),
            (int) $reset['user_id'],
        ]);
        Database::execute('update password_resets set used_at = now() where id = $1', [(int) $reset['id']]);
        Database::execute('delete from password_resets where user_id = $1 and used_at is null', [(int) $reset['user_id']]);

        $this->audit('usuario.password_restablecida', 'user', (string) $reset['user_id']);
        Flash::success('Tu contraseña se actualizó. Ya puedes iniciar sesión.');
        return $this->redirect('/acceder');
    }

    /** @return array<string, mixed>|null */
    private function validReset(string $token): ?array
    {
        return Database::first(
            'select * from password_resets
             where token_hash = $1 and used_at is null and expires_at > now()',
            [hash('sha256', $token)]
        );
    }
}
