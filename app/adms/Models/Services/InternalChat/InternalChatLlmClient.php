<?php

namespace App\adms\Models\Services\InternalChat;

/**
 * Cliente HTTP para LLMs no chat interno.
 * Prioridade: tela Assistente MCP (banco) → .env.
 */
class InternalChatLlmClient
{
    private InternalChatLlmSettings $settings;

    private ?string $lastError = null;

    public function __construct(?InternalChatLlmSettings $settings = null)
    {
        $this->settings = $settings ?? InternalChatLlmSettings::fromRepository();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @return array{text: string, provider: string}|null
     */
    public function complete(string $system, string $user, bool $jsonMode = false, bool $firstPlanOnly = false): ?array
    {
        $this->lastError = null;
        $plans = $this->settings->resolveCallPlan();
        if ($firstPlanOnly && $plans !== []) {
            $plans = [$plans[0]];
        }
        foreach ($plans as $plan) {
            $result = match ($plan['transport']) {
                'openai' => $this->callOpenAi($system, $user, $jsonMode, $plan),
                'anthropic' => $this->callAnthropic($system, $user, $plan),
                'ollama' => $this->callOllama($system, $user, $jsonMode, $plan),
                default => null,
            };
            if ($result !== null) {
                return $result;
            }
            if ($firstPlanOnly) {
                break;
            }
        }

        if ($this->lastError === null) {
            $this->lastError = 'Nenhum provedor de IA configurado (Groq, Gemini, OpenAI, Claude ou Ollama).';
        }

        return null;
    }

    public function isAnyConfigured(): bool
    {
        return $this->settings->isAnyConfigured();
    }

    /**
     * @param array{api_key: string, base_url: string, model: string, slot: string} $plan
     * @return array{text: string, provider: string}|null
     */
    private function callOpenAi(string $system, string $user, bool $jsonMode, array $plan): ?array
    {
        $key = trim($plan['api_key']);
        $base = rtrim($plan['base_url'], '/');
        $model = $plan['model'];
        if ($key === '' || $base === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ];
        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $body = $this->httpJson('POST', $base . '/chat/completions', $payload, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ], 45);

        if ($body === null && $jsonMode) {
            unset($payload['response_format']);
            $body = $this->httpJson('POST', $base . '/chat/completions', $payload, [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
            ], 45);
        }

        if ($body === null) {
            return null;
        }

        $text = trim((string) ($body['choices'][0]['message']['content'] ?? ''));
        if ($text === '') {
            return null;
        }

        return ['text' => $text, 'provider' => $plan['slot'] . ':' . $model];
    }

    /**
     * @param array{api_key: string, model: string, slot: string} $plan
     * @return array{text: string, provider: string}|null
     */
    private function callAnthropic(string $system, string $user, array $plan): ?array
    {
        $key = trim($plan['api_key']);
        $model = $plan['model'];
        if ($key === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 500,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $user],
            ],
        ];

        $body = $this->httpJson('POST', 'https://api.anthropic.com/v1/messages', $payload, [
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ], 45);

        if ($body === null) {
            return null;
        }

        $parts = $body['content'] ?? [];
        $text = '';
        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (($part['type'] ?? '') === 'text') {
                    $text .= (string) ($part['text'] ?? '');
                }
            }
        }
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        return ['text' => $text, 'provider' => $plan['slot'] . ':' . $model];
    }

    /**
     * @param array{base_url: string, slot: string} $plan
     * @return array{text: string, provider: string}|null
     */
    private function callOllama(string $system, string $user, bool $jsonMode, array $plan): ?array
    {
        $base = rtrim($plan['base_url'], '/');
        if ($base === '') {
            return null;
        }

        $models = $this->resolveOllamaModels();
        foreach ($models as $model) {
            $payload = [
                'model' => $model,
                'prompt' => $system . "\n\n" . $user,
                'stream' => false,
                'options' => ['num_predict' => 400],
            ];
            if ($jsonMode) {
                $payload['format'] = 'json';
            }

            $body = $this->httpJson('POST', $base . '/api/generate', $payload, [
                'Content-Type: application/json',
            ], 45);

            if ($body === null) {
                continue;
            }

            $text = trim((string) ($body['response'] ?? ''));
            if ($text === '') {
                continue;
            }

            return ['text' => $text, 'provider' => 'ollama:' . $model];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function resolveOllamaModels(): array
    {
        try {
            $repo = new \App\adms\Models\Repository\AdmsMcpApiConfigRepository();
            $chain = $repo->resolveOllamaModelChain();
            if ($chain !== []) {
                return $chain;
            }
        } catch (\Throwable) {
            // segue fallback .env
        }

        $primary = trim((string) ($_ENV['OLLAMA_MODEL'] ?? 'llama3.2'));
        return $primary !== '' ? [$primary] : ['llama3.2'];
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $headers
     * @return array<string, mixed>|null
     */
    private function httpJson(string $method, string $url, array $payload, array $headers, int $timeout): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => min(8, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $this->applyCurlSsl($ch);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            $snippet = substr((string) ($raw !== false ? $raw : $curlErr), 0, 280);
            $this->lastError = 'HTTP ' . $code . ' ' . $url . ($snippet !== '' ? ' — ' . $snippet : '');
            if ($this->isSslCertificateError($curlErr)) {
                $this->lastError = 'SSL (certificado CA ausente no PHP/WAMP). ' . $this->lastError;
            }
            error_log('InternalChatLlmClient ' . $this->lastError);

            return null;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * WAMP no Windows costuma não ter cacert.pem (erro “unable to get local issuer certificate”).
     * Se houver bundle no php.ini, usa; senão segue o padrão SAP/WhatsApp deste projeto.
     */
    private function applyCurlSsl(\CurlHandle $ch): void
    {
        $ca = $this->resolveCaBundle();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            return;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    private function resolveCaBundle(): ?string
    {
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $iniPath) {
            $path = trim((string) $iniPath);
            if ($path !== '' && is_readable($path)) {
                return $path;
            }
        }

        $candidates = [
            dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'cacert.pem',
            'C:\\wamp64\\bin\\php\\extras\\ssl\\cacert.pem',
        ];
        $phpDir = dirname((string) PHP_BINARY);
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem';
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'cacert.pem';

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function isSslCertificateError(string $curlErr): bool
    {
        return $curlErr !== '' && (stripos($curlErr, 'SSL') !== false || stripos($curlErr, 'certificate') !== false);
    }
}
