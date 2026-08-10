<?php

namespace App\adms\Models\Services\InternalChat;

/**
 * Cliente HTTP para LLMs no chat interno.
 * Prioridade (INTERNAL_CHAT_LLM=auto): OpenAI-compatible → Anthropic → Ollama.
 */
class InternalChatLlmClient
{
    /**
     * @return array{text: string, provider: string}|null
     */
    public function complete(string $system, string $user, bool $jsonMode = false): ?array
    {
        $order = $this->resolveProviderOrder();
        foreach ($order as $provider) {
            $result = match ($provider) {
                'openai' => $this->callOpenAi($system, $user, $jsonMode),
                'anthropic' => $this->callAnthropic($system, $user),
                'ollama' => $this->callOllama($system, $user, $jsonMode),
                default => null,
            };
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    public function isAnyConfigured(): bool
    {
        return $this->resolveProviderOrder() !== [];
    }

    /**
     * @return list<string>
     */
    private function resolveProviderOrder(): array
    {
        $mode = strtolower(trim((string) ($_ENV['INTERNAL_CHAT_LLM'] ?? 'auto')));
        if ($mode === 'openai' || $mode === 'anthropic' || $mode === 'ollama') {
            return $this->providerReady($mode) ? [$mode] : [];
        }

        // auto
        $order = [];
        foreach (['openai', 'anthropic', 'ollama'] as $p) {
            if ($this->providerReady($p)) {
                $order[] = $p;
            }
        }

        return $order;
    }

    private function providerReady(string $provider): bool
    {
        return match ($provider) {
            'openai' => trim((string) ($_ENV['OPENAI_API_KEY'] ?? '')) !== '',
            'anthropic' => trim((string) ($_ENV['ANTHROPIC_API_KEY'] ?? '')) !== '',
            'ollama' => rtrim((string) ($_ENV['OLLAMA_URL'] ?? ''), '/') !== '',
            default => false,
        };
    }

    /**
     * @return array{text: string, provider: string}|null
     */
    private function callOpenAi(string $system, string $user, bool $jsonMode): ?array
    {
        $key = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
        if ($key === '') {
            return null;
        }

        $base = rtrim((string) ($_ENV['OPENAI_BASE_URL'] ?? 'https://api.openai.com/v1'), '/');
        $model = (string) ($_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini');

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

        if ($body === null) {
            return null;
        }

        $text = trim((string) ($body['choices'][0]['message']['content'] ?? ''));
        if ($text === '') {
            return null;
        }

        return ['text' => $text, 'provider' => 'openai:' . $model];
    }

    /**
     * @return array{text: string, provider: string}|null
     */
    private function callAnthropic(string $system, string $user): ?array
    {
        $key = trim((string) ($_ENV['ANTHROPIC_API_KEY'] ?? ''));
        if ($key === '') {
            return null;
        }

        $model = (string) ($_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-4-20250514');
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

        return ['text' => $text, 'provider' => 'anthropic:' . $model];
    }

    /**
     * @return array{text: string, provider: string}|null
     */
    private function callOllama(string $system, string $user, bool $jsonMode): ?array
    {
        $base = rtrim((string) ($_ENV['OLLAMA_URL'] ?? ''), '/');
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
     * Cadeia: modelo da tela MCP → fallbacks → OLLAMA_MODEL do .env.
     *
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
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            error_log('InternalChatLlmClient HTTP ' . $code . ' ' . $url . ' body=' . substr((string) $raw, 0, 300));

            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
