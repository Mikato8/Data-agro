<?php

declare(strict_types=1);

namespace App\Core;

final class Cache
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $path = self::path($key);
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if (is_string($raw) && $raw !== '') {
                $payload = @unserialize($raw, ['allowed_classes' => false]);
                if (is_array($payload) && ($payload['expires'] ?? 0) > time()) {
                    return $payload['value'] ?? null;
                }
            }
        }

        $value = $callback();
        if ($ttl > 0) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $payload = serialize([
                'expires' => time() + $ttl,
                'value' => $value,
            ]);
            $temp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($temp, $payload, LOCK_EX) !== false) {
                @rename($temp, $path);
            } else {
                @unlink($temp);
            }
        }
        return $value;
    }

    public static function forget(string $key): void
    {
        @unlink(self::path($key));
    }

    public static function flush(): void
    {
        foreach (glob(BASE_PATH . '/storage/cache/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private static function path(string $key): string
    {
        return BASE_PATH . '/storage/cache/' . hash('sha256', $key) . '.cache';
    }
}
