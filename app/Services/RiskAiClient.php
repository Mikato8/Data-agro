<?php

declare(strict_types=1);

namespace App\Services;

final class RiskAiClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('app.ai.url', ''), '/');
        $this->token = (string) config('app.ai.token', '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    /** @return array<string, mixed> */
    public function classify(string $text): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('La IA no está configurada. Define AI_SERVICE_URL en el entorno.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('La extensión cURL de PHP no está disponible.');
        }

        $payload = json_encode(['text' => $text], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init($this->baseUrl . '/classify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'DiagnosticoAgrónomo/1.0',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('No pudimos conectar con el servicio de IA: ' . ($error !== '' ? $error : 'error de red'));
        }
        if ($status === 401) {
            throw new \RuntimeException('El servicio de IA rechazó el token configurado.');
        }
        if ($status === 503) {
            throw new \RuntimeException('El modelo de IA todavía se está cargando. Intenta de nuevo en unos segundos.');
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('El servicio de IA respondió con estado HTTP ' . $status . '.');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data) || !isset($data['label'], $data['confidence'], $data['scores'])) {
            throw new \RuntimeException('El servicio de IA devolvió una respuesta inesperada.');
        }

        return $data;
    }
}
