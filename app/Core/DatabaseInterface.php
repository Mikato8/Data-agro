<?php

declare(strict_types=1);

namespace App\Core;

interface DatabaseInterface
{
    /** @param array<int|string, mixed> $params
     *  @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array;

    /** @param array<int|string, mixed> $params
     *  @return array<string, mixed>|null
     */
    public function first(string $sql, array $params = []): ?array;

    /** @param array<int|string, mixed> $params */
    public function value(string $sql, array $params = []): mixed;

    /** @param array<int|string, mixed> $params */
    public function execute(string $sql, array $params = []): int;

    /** @param array<int|string, mixed> $params
     *  @return array<string, mixed>|null
     */
    public function insert(string $sql, array $params = []): ?array;

    public function transaction(callable $callback): mixed;

    public function raw(string $sql): void;

    public function driverName(): string;

    public function ping(): bool;
}
