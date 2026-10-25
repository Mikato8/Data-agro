<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = Str::token(32);
        }
        return $_SESSION['_csrf'];
    }

    public static function regenerate(): void
    {
        $_SESSION['_csrf'] = Str::token(32);
    }

    public static function verify(?string $token): bool
    {
        $session = $_SESSION['_csrf'] ?? '';
        return is_string($session) && is_string($token) && $token !== '' && hash_equals($session, $token);
    }

    public static function validate(Request $request): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        if (!self::verify((string) $request->input('_token', ''))) {
            throw new HttpException(419);
        }
    }
}
