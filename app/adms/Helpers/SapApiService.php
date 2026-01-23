<?php

namespace App\adms\Helpers;

class SapApiService
{
    /**
     * Realiza uma chamada de health-check na API configurada.
     *
     * @param array $config
     * @return array{
     *     success: bool,
     *     status_code: int|null,
     *     duration_ms: float|null,
     *     body: string|null,
     *     error: string|null
     * }
     */
    public static function healthCheck(array $config): array
    {
        $baseUrl = trim($config['base_url'] ?? '');
        $baseUrl = rtrim($baseUrl, '/');

        $endpoint = trim($config['health_endpoint'] ?? '/health');
        if ($endpoint === '') {
            $endpoint = '/health';
        }
        if ($endpoint[0] !== '/') {
            $endpoint = '/' . $endpoint;
        }

        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'status_code' => null,
                'duration_ms' => null,
                'body' => null,
                'error' => 'URL base inválida.',
            ];
        }

        $timeout = (int)($config['timeout_ms'] ?? 30000);
        if ($timeout < 1000) {
            $timeout = 1000;
        }

        $url = $baseUrl . $endpoint;
        $headers = [
            'Accept: application/json',
        ];

        if (!empty($config['api_token'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_token'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT_MS => $timeout,
            CURLOPT_CONNECTTIMEOUT_MS => min($timeout, 10000),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
        ]);

        $start = microtime(true);
        $body = curl_exec($ch);
        $durationMs = round((microtime(true) - $start) * 1000, 2);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = $body !== false && $statusCode >= 200 && $statusCode < 300;

        return [
            'success' => $success,
            'status_code' => $statusCode ?: null,
            'duration_ms' => $durationMs,
            'body' => $body !== false ? (string)$body : null,
            'error' => $success ? null : ($curlError ?: ($statusCode ? 'HTTP ' . $statusCode : 'Sem resposta da API')),
        ];
    }
}







