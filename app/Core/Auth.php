<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $cachedUser = null;
    private static bool $loaded = false;
    private static string $lastError = '';

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cachedUser;
        }
        self::$loaded = true;

        $id = $_SESSION['auth_id'] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        try {
            $user = Database::first(
                'select u.*, s.name as state_name
                 from users u
                 left join states s on s.code = u.state_code
                 where u.id = $1 and u.deleted_at is null',
                [(int) $id]
            );
        } catch (\Throwable $e) {
            Logger::error('No se pudo cargar la sesión del usuario', ['message' => $e->getMessage()]);
            return null;
        }

        if ($user === null || $user['status'] === 'suspendido') {
            self::logout();
            return null;
        }

        $user['id'] = (int) $user['id'];
        self::$cachedUser = $user;
        return $user;
    }

    /** @return array{ok: bool, message: string} */
    public static function attempt(string $email, string $password, bool $remember = false): array
    {
        self::$lastError = '';
        $email = Str::normalizeEmail($email);

        $attempts = (int) Database::value(
            "select count(*) from login_attempts
             where successful = false and created_at > now() - interval '15 minutes'
               and (email = $1 or ip = $2::inet)",
            [$email, self::ip()]
        );
        if ($attempts >= 12) {
            self::$lastError = 'Demasiados intentos fallidos. Espera 15 minutos antes de volver a intentar.';
            return ['ok' => false, 'message' => self::$lastError];
        }

        $user = Database::first('select * from users where email = $1 and deleted_at is null', [$email]);
        $valid = $user !== null && password_verify($password, (string) $user['password_hash']);

        Database::execute(
            'insert into login_attempts (email, ip, successful) values ($1, $2::inet, $3)',
            [$email, self::ip(), $valid]
        );

        if (!$valid) {
            self::$lastError = 'Correo o contraseña incorrectos.';
            return ['ok' => false, 'message' => self::$lastError];
        }

        if ($user['status'] === 'suspendido') {
            self::$lastError = 'Tu cuenta está suspendida. Contacta al equipo editorial.';
            return ['ok' => false, 'message' => self::$lastError];
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::execute('update users set password_hash = $1 where id = $2', [
                password_hash($password, PASSWORD_DEFAULT),
                (int) $user['id'],
            ]);
        }

        self::login($user, $remember);
        Database::execute('update users set last_login_at = now() where id = $1', [(int) $user['id']]);

        return ['ok' => true, 'message' => 'Sesión iniciada.'];
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user, bool $remember = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['auth_id'] = (int) $user['id'];
        $_SESSION['remember'] = $remember;
        $cookie = [
            'expires' => $remember ? time() + 60 * 60 * 24 * 30 : 0,
            'path' => '/',
            'secure' => (bool) config('app.session.secure', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if ($remember) {
            setcookie('dv_remember', '1', $cookie);
            setcookie(session_name(), session_id(), $cookie);
        } else {
            setcookie('dv_remember', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        }
        Csrf::regenerate();
        self::$cachedUser = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_id'], $_SESSION['remember']);
        setcookie('dv_remember', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        self::$cachedUser = null;
        self::$loaded = true;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        Csrf::regenerate();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    public static function isModerator(): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], (array) config('app.moderators', []), true);
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    public static function isVerified(): bool
    {
        $user = self::user();
        return $user !== null && $user['status'] === 'verificado';
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            $_SESSION['intended_url'] = Request::capture()->path();
            Flash::info('Inicia sesión para continuar.');
            header('Location: ' . url('/acceder'));
            exit;
        }
        return $user;
    }

    public static function requireVerified(): array
    {
        $user = self::requireLogin();
        if ($user['status'] !== 'verificado') {
            Flash::error('Tu cuenta aún está pendiente de verificación. Un editor revisará tu cédula profesional; mientras tanto puedes guardar borradores.');
            header('Location: ' . url('/perfil'));
            exit;
        }
        return $user;
    }

    public static function requireModerator(): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], (array) config('app.moderators', []), true)) {
            throw new HttpException(403);
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'admin') {
            throw new HttpException(403);
        }
        return $user;
    }

    public static function intendedUrl(): string
    {
        $url = (string) ($_SESSION['intended_url'] ?? '/');
        unset($_SESSION['intended_url']);
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return '/';
        }
        return $url;
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function ip(): string
    {
        $ip = Request::capture()->ip();
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '127.0.0.1';
    }
}
