<?php

declare(strict_types=1);

namespace App\Core;

final class Database
{
    private static ?DatabaseInterface $driver = null;

    /** @var array<string, mixed> */
    private static array $config = [];

    /** @param array<string, mixed> $config */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$driver = null;
    }

    public static function driver(): DatabaseInterface
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $url = (string) (self::$config['url'] ?? '');
        if ($url === '') {
            throw new \RuntimeException('Falta DATABASE_URL en la configuración.');
        }

        $requested = strtolower((string) (self::$config['driver'] ?? 'auto'));
        if ($requested === 'http') {
            self::$driver = new HttpDatabase($url);
            return self::$driver;
        }
        if ($requested === 'pdo') {
            self::$driver = new PdoDatabase($url);
            return self::$driver;
        }

        if (extension_loaded('pdo_pgsql')) {
            try {
                self::$driver = new PdoDatabase($url);
                return self::$driver;
            } catch (DatabaseException) {
                // Si el hosting bloquea el puerto 5432, se usa HTTPS.
            }
        }
        self::$driver = new HttpDatabase($url);
        return self::$driver;
    }

    public static function reset(): void
    {
        self::$driver = null;
    }

    /** @param array<int|string, mixed> $params */
    public static function select(string $sql, array $params = []): array
    {
        return self::driver()->select($sql, $params);
    }

    /** @param array<int|string, mixed> $params */
    public static function first(string $sql, array $params = []): ?array
    {
        return self::driver()->first($sql, $params);
    }

    /** @param array<int|string, mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        return self::driver()->value($sql, $params);
    }

    /** @param array<int|string, mixed> $params */
    public static function execute(string $sql, array $params = []): int
    {
        return self::driver()->execute($sql, $params);
    }

    /** @param array<int|string, mixed> $params */
    public static function insert(string $sql, array $params = []): ?array
    {
        return self::driver()->insert($sql, $params);
    }

    public static function transaction(callable $callback): mixed
    {
        return self::driver()->transaction($callback);
    }

    public static function raw(string $sql): void
    {
        self::driver()->raw($sql);
    }

    public static function driverName(): string
    {
        return self::driver()->driverName();
    }

    public static function ping(): bool
    {
        try {
            return self::driver()->ping();
        } catch (\Throwable) {
            return false;
        }
    }
}
