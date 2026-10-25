<?php

declare(strict_types=1);

namespace App\Core;

final class PdoDatabase implements DatabaseInterface
{
    private \PDO $pdo;

    public function __construct(string $url)
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'], $parts['path'])) {
            throw new DatabaseException('La cadena de conexión de PostgreSQL no es válida.');
        }

        $database = ltrim($parts['path'], '/');
        $port = (int) ($parts['port'] ?? 5432);
        parse_str($parts['query'] ?? '', $query);
        $sslmode = $query['sslmode'] ?? 'require';
        $channelBinding = $query['channel_binding'] ?? null;

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $parts['host'],
            $port,
            $database,
            $sslmode
        );
        if ($channelBinding !== null) {
            $dsn .= ';channel_binding=' . $channelBinding;
        }

        $user = isset($parts['user']) ? rawurldecode($parts['user']) : null;
        $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : null;

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_TIMEOUT => 15,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException('No fue posible conectar con PostgreSQL.', (string) $e->getCode());
        }
    }

    public function select(string $sql, array $params = []): array
    {
        $statement = $this->run($sql, $params);
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function first(string $sql, array $params = []): ?array
    {
        $statement = $this->run($sql, $params);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function value(string $sql, array $params = []): mixed
    {
        $statement = $this->run($sql, $params);
        $value = $statement->fetchColumn();
        return $value === false ? null : $value;
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function insert(string $sql, array $params = []): ?array
    {
        return $this->first($sql, $params);
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function raw(string $sql): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            Logger::error('Error de PostgreSQL en SQL directo', [
                'sqlstate' => (string) $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            throw new DatabaseException('Error al ejecutar una instrucción en PostgreSQL.', (string) $e->getCode());
        }
    }

    public function driverName(): string
    {
        return 'pdo_pgsql';
    }

    public function ping(): bool
    {
        try {
            return (int) $this->value('select 1') === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param array<int|string, mixed> $params */
    private function run(string $sql, array $params): \PDOStatement
    {
        try {
            // PDO_pgsql usa marcadores posicionales (?), no $1. Se convierten
            // a marcadores nombrados para conservar el orden y los reusos.
            $bound = [];
            $sql = preg_replace_callback('/\$(\d+)/', static function (array $matches) use ($params, &$bound): string {
                $index = (int) $matches[1] - 1;
                $key = ':p' . $matches[1];
                if (!array_key_exists($key, $bound)) {
                    $bound[$key] = array_values($params)[$index] ?? null;
                }
                return $key;
            }, $sql) ?? $sql;

            $statement = $this->pdo->prepare($sql);
            foreach ($bound as $key => $value) {
                $type = match (true) {
                    is_int($value) => \PDO::PARAM_INT,
                    is_bool($value) => \PDO::PARAM_BOOL,
                    $value === null => \PDO::PARAM_NULL,
                    default => \PDO::PARAM_STR,
                };
                $statement->bindValue($key, $value, $type);
            }
            $statement->execute();
            return $statement;
        } catch (\PDOException $e) {
            Logger::error('Error de PostgreSQL', [
                'sqlstate' => (string) $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            throw new DatabaseException('Error al ejecutar una consulta en PostgreSQL.', (string) $e->getCode());
        }
    }
}
