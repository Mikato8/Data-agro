<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cliente del endpoint SQL sobre HTTPS de Neon.
 *
 * Permite usar PostgreSQL desde hospedajes PHP que no tienen pdo_pgsql
 * habilitado. Cada llamada es una consulta independiente; para operaciones
 * que requieren atomicidad se usan CTEs de una sola sentencia.
 */
final class HttpDatabase implements DatabaseInterface
{
    private string $endpoint;
    private string $connectionString;

    public function __construct(string $url)
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            throw new DatabaseException('La cadena de conexión de Neon no es válida.');
        }
        $this->connectionString = $url;
        $this->endpoint = 'https://' . $parts['host'] . '/sql';
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->request($sql, $params, true)['rows'];
    }

    public function first(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);
        return $rows[0] ?? null;
    }

    public function value(string $sql, array $params = []): mixed
    {
        $row = $this->first($sql, $params);
        if ($row === null) {
            return null;
        }
        return array_values($row)[0] ?? null;
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->request($sql, $params, false)['rowCount'];
    }

    public function insert(string $sql, array $params = []): ?array
    {
        return $this->first($sql, $params);
    }

    public function transaction(callable $callback): mixed
    {
        throw new DatabaseException(
            'El transporte HTTP de Neon no admite transacciones interactivas. Usa una sola sentencia con CTE o el controlador PDO.'
        );
    }

    public function raw(string $sql): void
    {
        $this->request($sql, [], false);
    }

    public function driverName(): string
    {
        return 'neon_http';
    }

    public function ping(): bool
    {
        try {
            return (int) $this->value('select 1') === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array{rows: array<int, array<string, mixed>>, rowCount: int, command: string}
     */
    private function request(string $sql, array $params, bool $readOnly): array
    {
        $payload = json_encode([
            'query' => $sql,
            'params' => array_values($params),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $attempts = $readOnly ? 2 : 1;
        $lastError = '';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $curl = curl_init($this->endpoint);
            if ($curl === false) {
                throw new DatabaseException('No fue posible inicializar la conexión HTTPS con Neon.');
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Neon-Connection-String: ' . $this->connectionString,
                    'Neon-Raw-Text-Output: true',
                    'Neon-Array-Mode: true',
                ],
            ]);

            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $errno = curl_errno($curl);
            $error = curl_error($curl);

            if ($errno !== 0 || $body === false) {
                $lastError = 'Error de red con Neon (' . $errno . ').';
                if ($attempt < $attempts) {
                    usleep(250000);
                    continue;
                }
                Logger::error('Fallo de red al consultar Neon', ['errno' => $errno, 'error' => $error]);
                throw new DatabaseException('No fue posible comunicarse con la base de datos. Intenta de nuevo.');
            }

            if ($status === 200) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    throw new DatabaseException('La respuesta de Neon no tiene el formato esperado.');
                }
                $rows = [];
                $fields = $decoded['fields'] ?? [];
                $rawRows = $decoded['rows'] ?? [];
                $names = [];
                foreach ($fields as $field) {
                    $names[] = (string) ($field['name'] ?? '');
                }
                foreach ($rawRows as $rawRow) {
                    if (!is_array($rawRow)) {
                        continue;
                    }
                    if ($names !== [] && array_is_list($rawRow)) {
                        $rows[] = array_combine($names, $rawRow) ?: [];
                    } else {
                        $rows[] = $rawRow;
                    }
                }
                return [
                    'rows' => $rows,
                    'rowCount' => (int) ($decoded['rowCount'] ?? count($rows)),
                    'command' => (string) ($decoded['command'] ?? ''),
                ];
            }

            $decoded = json_decode((string) $body, true);
            $message = is_array($decoded) ? (string) ($decoded['message'] ?? '') : '';
            $code = is_array($decoded) ? (string) ($decoded['code'] ?? '') : '';
            $lastError = $message !== '' ? $message : 'HTTP ' . $status;

            if ($readOnly && $status >= 500 && $attempt < $attempts) {
                usleep(250000);
                continue;
            }

            Logger::error('Error del endpoint SQL de Neon', [
                'status' => $status,
                'code' => $code,
                'message' => $message,
            ]);
            throw new DatabaseException('Error al ejecutar una consulta en la base de datos.', $code);
        }

        throw new DatabaseException('No fue posible ejecutar la consulta: ' . $lastError);
    }
}
