<?php

declare(strict_types=1);

namespace App\Core;

final class Str
{
    public static function slug(string $text, int $maxLength = 80): string
    {
        $text = trim($text);
        if (class_exists('Transliterator')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        } else {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text;
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        $text = mb_substr($text, 0, $maxLength);
        return $text !== '' ? $text : 'caso';
    }

    public static function random(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function token(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }
        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 2);
        return $visible . str_repeat('•', max(2, mb_strlen($local) - 2)) . '@' . $domain;
    }

    public static function filename(string $extension): string
    {
        return date('Ymd') . '-' . self::token(12) . '.' . $extension;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function limit(string $text, int $length): string
    {
        return mb_substr($text, 0, $length);
    }
}
