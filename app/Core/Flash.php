<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    private static ?array $oldCache = null;
    private static ?array $messageCache = null;
    private static ?array $errorCache = null;

    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash']['messages'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    /** @return array<int, array{type: string, message: string}> */
    public static function messages(): array
    {
        if (self::$messageCache !== null) {
            return self::$messageCache;
        }
        $messages = $_SESSION['_flash']['messages'] ?? [];
        unset($_SESSION['_flash']['messages']);
        self::$messageCache = is_array($messages) ? $messages : [];
        return self::$messageCache;
    }

    /** @param array<string, string> $errors */
    public static function withErrors(array $errors): void
    {
        $_SESSION['_flash']['errors'] = $errors;
        self::$errorCache = null;
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        if (self::$errorCache !== null) {
            return self::$errorCache;
        }
        $errors = $_SESSION['_flash']['errors'] ?? [];
        unset($_SESSION['_flash']['errors']);
        self::$errorCache = is_array($errors) ? $errors : [];
        return self::$errorCache;
    }

    public static function firstError(string $field): ?string
    {
        return self::errors()[$field] ?? null;
    }

    /** @param array<string, mixed> $data */
    public static function old(array $data): void
    {
        $safe = $data;
        unset($safe['password'], $safe['password_confirmation'], $safe['_token'], $safe['_method']);
        $_SESSION['_flash']['old'] = $safe;
        self::$oldCache = null;
    }

    /** @return array<string, mixed> */
    public static function oldInput(): array
    {
        if (self::$oldCache !== null) {
            return self::$oldCache;
        }
        $old = $_SESSION['_flash']['old'] ?? [];
        unset($_SESSION['_flash']['old']);
        self::$oldCache = is_array($old) ? $old : [];
        return self::$oldCache;
    }
}
