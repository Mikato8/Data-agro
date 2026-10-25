<?php

declare(strict_types=1);

namespace App\Services;

final class CedulaVerifier
{
    private bool $enabled;
    private string $url;
    private string $token;

    public function __construct()
    {
        $this->enabled = (bool) config('app.cedula.enabled', false);
        $this->url = (string) config('app.cedula.url', '');
        $this->token = (string) config('app.cedula.token', '');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->url !== '' && $this->token !== '';
    }

    /**
     * Consulta el Registro Nacional de Profesionistas y compara el nombre.
     * @return array{found: bool, name_match: bool, agronomy: bool, name: string, title: string, institution: string, curp: ?string, source: string}
     */
    public function verify(string $cedula, string $fullName): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('La verificación automática de cédula no está configurada.');
        }

        $cedula = preg_replace('/\D+/', '', $cedula) ?? '';
        if (strlen($cedula) < 6 || strlen($cedula) > 12) {
            throw new \RuntimeException('La cédula debe tener entre 6 y 12 dígitos.');
        }

        $endpoint = $this->url . (str_contains($this->url, '?') ? '&' : '?') . 'cedula=' . rawurlencode($cedula);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => (int) config('app.cedula.timeout', 15),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'DiagnosticoAgrónomo/1.0',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('No pudimos conectar con el servicio de cédulas: ' . ($error !== '' ? $error : 'error de red'));
        }
        if ($status === 401 || $status === 403) {
            throw new \RuntimeException('El servicio de cédulas rechazó las credenciales configuradas.');
        }
        if ($status === 429) {
            throw new \RuntimeException('El servicio de cédulas alcanzó su límite de consultas. Intenta más tarde.');
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('El servicio de cédulas respondió con estado HTTP ' . $status . '.');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('El servicio de cédulas devolvió una respuesta inesperada.');
        }
        if (($data['success'] ?? false) !== true || !is_array($data['data'] ?? null) || $data['data'] === []) {
            return [
                'found' => false,
                'name_match' => false,
                'agronomy' => false,
                'name' => '',
                'title' => '',
                'institution' => '',
                'curp' => null,
                'source' => 'apimarket',
            ];
        }

        $records = array_values(array_filter($data['data'], 'is_array'));
        $userTokens = $this->nameTokens($fullName);
        $cedulaKey = ltrim($cedula, '0');
        $selected = null;
        $selectedMatches = false;
        $bestScore = -1;
        foreach ($records as $record) {
            $recordName = trim(implode(' ', array_filter([
                (string) ($record['nombre'] ?? ''),
                (string) ($record['paterno'] ?? ''),
                (string) ($record['materno'] ?? ''),
            ])));
            $recordTokens = $this->nameTokens($recordName);
            $matches = $userTokens !== [] && $recordTokens !== [] && array_diff($userTokens, $recordTokens) === [];
            $title = (string) ($record['titulo'] ?? '');
            $agronomy = $this->isAgronomyTitle($title);
            $recordCedula = ltrim((string) ($record['idCedula'] ?? ''), '0');
            $sameCedula = $cedulaKey !== '' && $recordCedula === $cedulaKey;
            $score = ($sameCedula ? 4 : 0) + ($matches ? 2 : 0) + ($agronomy ? 1 : 0);
            if ($score > $bestScore) {
                $selected = $record;
                $selectedMatches = $matches;
                $bestScore = $score;
            }
        }

        if ($selected === null) {
            return [
                'found' => false,
                'name_match' => false,
                'agronomy' => false,
                'name' => '',
                'title' => '',
                'institution' => '',
                'curp' => null,
                'source' => 'apimarket',
            ];
        }

        $recordName = trim(implode(' ', array_filter([
            (string) ($selected['nombre'] ?? ''),
            (string) ($selected['paterno'] ?? ''),
            (string) ($selected['materno'] ?? ''),
        ])));
        $title = trim((string) ($selected['titulo'] ?? ''));

        return [
            'found' => true,
            'name_match' => $selectedMatches,
            'agronomy' => $this->isAgronomyTitle($title),
            'name' => $recordName,
            'title' => $title,
            'institution' => trim((string) ($selected['desins'] ?? '')),
            'curp' => isset($selected['curp']) && $selected['curp'] !== '' ? (string) $selected['curp'] : null,
            'source' => 'apimarket',
        ];
    }

    /** @return array<int, string> */
    private function nameTokens(string $value): array
    {
        $value = mb_strtolower($value);
        if (class_exists('Transliterator')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);
            if (is_string($converted)) {
                $value = $converted;
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $tokens = preg_split('/\s+/', trim($value)) ?: [];
        $titles = ['dr', 'dra', 'doctor', 'doctora', 'ing', 'ingeniero', 'ingeniera', 'agronomo', 'agronoma'];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== '' && !in_array($token, $titles, true)));
        return array_values(array_unique($tokens));
    }

    private function isAgronomyTitle(string $title): bool
    {
        $title = mb_strtoupper($title);
        if (class_exists('Transliterator')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $title);
            if (is_string($converted) && $converted !== '') {
                $title = mb_strtoupper($converted);
            }
        }
        return str_contains($title, 'AGRONOM')
            || str_contains($title, 'INGENIER')
            || str_contains($title, 'FITOTECN')
            || str_contains($title, 'AGRO');
    }
}
