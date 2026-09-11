<?php

declare(strict_types=1);

namespace App\adms\Models\Services\InternalChat;

use App\adms\Models\Repository\AdmsMcpApiConfigRepository;

/**
 * Resolve provedor/chave/modelo da tela Assistente MCP, com fallback para o .env.
 *
 * @phpstan-type LlmPlan array{
 *     slot: string,
 *     transport: 'openai'|'anthropic'|'ollama',
 *     label: string,
 *     api_key: string,
 *     base_url: string,
 *     model: string
 * }
 */
final class InternalChatLlmSettings
{
    public const PROVIDERS = ['auto', 'groq', 'gemini', 'openai', 'anthropic', 'ollama'];

    /** @var array<string, mixed> */
    private array $config;

    /** @var array<string, string> */
    private array $env;

    /**
     * @param array<string, mixed>|null $config Linha de adms_mcp_api_config (null = carregar do banco)
     * @param array<string, string>|null $env Sobrescreve getenv (testes)
     */
    public function __construct(?array $config = null, ?array $env = null)
    {
        $this->env = $env ?? $this->captureEnv();
        if ($config !== null) {
            $this->config = $config;

            return;
        }

        try {
            $this->config = (new AdmsMcpApiConfigRepository())->getConfig() ?: [];
        } catch (\Throwable) {
            $this->config = [];
        }
    }

    public static function fromRepository(): self
    {
        return new self(null, null);
    }

    /**
     * @return array<string, array{label: string, signup_url: string, key_url: string, default_model: string, models: list<string>, base_url?: string}>
     */
    public static function catalog(): array
    {
        return [
            'groq' => [
                'label' => 'Groq (grátis para testes)',
                'signup_url' => 'https://console.groq.com',
                'key_url' => 'https://console.groq.com/keys',
                'base_url' => 'https://api.groq.com/openai/v1',
                'default_model' => 'openai/gpt-oss-120b',
                'models' => ['openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.6-27b'],
            ],
            'gemini' => [
                'label' => 'Google Gemini (API grátis)',
                'signup_url' => 'https://aistudio.google.com',
                'key_url' => 'https://aistudio.google.com/apikey',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'default_model' => 'gemini-2.0-flash',
                'models' => ['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-1.5-flash'],
            ],
            'openai' => [
                'label' => 'OpenAI (pago)',
                'signup_url' => 'https://platform.openai.com/signup',
                'key_url' => 'https://platform.openai.com/api-keys',
                'base_url' => 'https://api.openai.com/v1',
                'default_model' => 'gpt-4o-mini',
                'models' => ['gpt-4o-mini', 'gpt-4o'],
            ],
            'anthropic' => [
                'label' => 'Anthropic Claude (pago)',
                'signup_url' => 'https://console.anthropic.com',
                'key_url' => 'https://console.anthropic.com/settings/keys',
                'default_model' => 'claude-sonnet-4-20250514',
                'models' => ['claude-sonnet-4-20250514', 'claude-3-5-haiku-20241022'],
            ],
            'ollama' => [
                'label' => 'Ollama (local, gratuito)',
                'signup_url' => 'https://ollama.com',
                'key_url' => 'https://ollama.com',
                'base_url' => 'http://127.0.0.1:11434',
                'default_model' => 'llama3.2',
                'models' => [],
            ],
        ];
    }

    public function provider(): string
    {
        $fromDb = strtolower(trim((string) ($this->config['llm_provider'] ?? '')));
        if (in_array($fromDb, self::PROVIDERS, true)) {
            return $fromDb;
        }

        $fromEnv = strtolower(trim($this->envVal('INTERNAL_CHAT_LLM', 'auto')));
        if (in_array($fromEnv, self::PROVIDERS, true)) {
            return $fromEnv;
        }
        if ($fromEnv === 'openai' || $fromEnv === 'anthropic' || $fromEnv === 'ollama') {
            return $fromEnv;
        }

        return 'auto';
    }

    public function ollamaUrl(): string
    {
        $fromDb = rtrim(trim((string) ($this->config['llm_ollama_url'] ?? '')), '/');
        if ($fromDb !== '') {
            return $fromDb;
        }

        return rtrim(trim($this->envVal('OLLAMA_URL', '')), '/');
    }

    /**
     * Plano de chamadas na ordem em que o cliente deve tentar.
     *
     * @return list<LlmPlan>
     */
    public function resolveCallPlan(): array
    {
        $mode = $this->provider();
        $slots = $mode === 'auto'
            ? ['groq', 'gemini', 'openai', 'anthropic', 'ollama']
            : [$mode];

        $plan = [];
        foreach ($slots as $slot) {
            $item = $this->planForSlot($slot);
            if ($item !== null) {
                $plan[] = $item;
            }
        }

        return $plan;
    }

    public function isAnyConfigured(): bool
    {
        return $this->resolveCallPlan() !== [];
    }

    /**
     * Mescla POST da tela (chave em branco = manter a do banco).
     *
     * @param array<string, mixed> $posted
     */
    public function withPostedForm(array $posted): self
    {
        $merged = $this->config;
        $merged['llm_provider'] = strtolower(trim((string) ($posted['llm_provider'] ?? $merged['llm_provider'] ?? 'auto')));
        $merged['llm_groq_model'] = $this->postedModelValue($posted, 'llm_groq_model', (string) ($merged['llm_groq_model'] ?? ''));
        $merged['llm_gemini_model'] = $this->postedModelValue($posted, 'llm_gemini_model', (string) ($merged['llm_gemini_model'] ?? ''));
        $merged['llm_openai_model'] = $this->postedModelValue($posted, 'llm_openai_model', (string) ($merged['llm_openai_model'] ?? ''));
        $merged['llm_openai_base_url'] = trim((string) ($posted['llm_openai_base_url'] ?? $merged['llm_openai_base_url'] ?? ''));
        $merged['llm_anthropic_model'] = $this->postedModelValue($posted, 'llm_anthropic_model', (string) ($merged['llm_anthropic_model'] ?? ''));
        $merged['llm_ollama_url'] = trim((string) ($posted['llm_ollama_url'] ?? $merged['llm_ollama_url'] ?? ''));

        foreach (['llm_groq_api_key', 'llm_gemini_api_key', 'llm_openai_api_key', 'llm_anthropic_api_key'] as $field) {
            if (!empty($posted['clear_' . $field])) {
                $merged[$field] = '';
                continue;
            }
            $postedKey = trim((string) ($posted[$field] ?? ''));
            if ($postedKey !== '') {
                $merged[$field] = $postedKey;
            }
        }

        return new self($merged, $this->env);
    }

    /**
     * @param array<string, mixed> $posted
     */
    private function postedModelValue(array $posted, string $field, string $fallback): string
    {
        $custom = trim((string) ($posted[$field . '_custom'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }
        if (array_key_exists($field, $posted)) {
            return trim((string) $posted[$field]);
        }

        return trim($fallback);
    }

    public static function maskKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $len = strlen($value);
        if ($len <= 4) {
            return '••••';
        }

        return '••••' . substr($value, -4);
    }

    /**
     * Ollama local (porta 11434) — não confundir com API MCP/ERP antiga.
     */
    public static function isPlausibleOllamaUrl(string $url): bool
    {
        $url = strtolower(trim($url));
        if ($url === '') {
            return false;
        }
        if (str_contains($url, '11434') || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            return true;
        }

        return false;
    }

    /**
     * @return LlmPlan|null
     */
    private function planForSlot(string $slot): ?array
    {
        $catalog = self::catalog();
        $meta = $catalog[$slot] ?? null;
        if ($meta === null) {
            return null;
        }

        if ($slot === 'ollama') {
            $url = $this->ollamaUrl();
            if ($url === '') {
                return null;
            }
            $explicitOllama = $this->provider() === 'ollama';
            if (!$explicitOllama && !self::isPlausibleOllamaUrl($url)) {
                return null;
            }

            return [
                'slot' => 'ollama',
                'transport' => 'ollama',
                'label' => $meta['label'],
                'api_key' => '',
                'base_url' => $url,
                'model' => '',
            ];
        }

        if ($slot === 'anthropic') {
            $key = $this->firstNonEmpty([
                (string) ($this->config['llm_anthropic_api_key'] ?? ''),
                $this->envVal('ANTHROPIC_API_KEY', ''),
            ]);
            if ($key === '') {
                return null;
            }
            $model = $this->firstNonEmpty([
                (string) ($this->config['llm_anthropic_model'] ?? ''),
                $this->envVal('ANTHROPIC_MODEL', ''),
                $meta['default_model'],
            ]);

            return [
                'slot' => 'anthropic',
                'transport' => 'anthropic',
                'label' => $meta['label'],
                'api_key' => $key,
                'base_url' => 'https://api.anthropic.com/v1',
                'model' => $model,
            ];
        }

        $keyField = match ($slot) {
            'groq' => 'llm_groq_api_key',
            'gemini' => 'llm_gemini_api_key',
            default => 'llm_openai_api_key',
        };
        $modelField = match ($slot) {
            'groq' => 'llm_groq_model',
            'gemini' => 'llm_gemini_model',
            default => 'llm_openai_model',
        };

        $key = trim((string) ($this->config[$keyField] ?? ''));
        if ($key === '' && $slot === 'openai') {
            $key = $this->envVal('OPENAI_API_KEY', '');
        }
        if ($key === '') {
            return null;
        }

        $base = (string) ($meta['base_url'] ?? '');
        if ($slot === 'openai') {
            $base = $this->firstNonEmpty([
                (string) ($this->config['llm_openai_base_url'] ?? ''),
                $this->envVal('OPENAI_BASE_URL', ''),
                $base,
            ]);
        }
        $base = rtrim($base, '/');
        $model = $this->firstNonEmpty([
            (string) ($this->config[$modelField] ?? ''),
            $slot === 'openai' ? $this->envVal('OPENAI_MODEL', '') : '',
            $meta['default_model'],
        ]);

        return [
            'slot' => $slot,
            'transport' => 'openai',
            'label' => $meta['label'],
            'api_key' => $key,
            'base_url' => $base,
            'model' => $model,
        ];
    }

    /**
     * @param list<string> $values
     */
    private function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim($value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function envVal(string $key, string $default = ''): string
    {
        if (array_key_exists($key, $this->env)) {
            return (string) $this->env[$key];
        }

        return $default;
    }

    /**
     * @return array<string, string>
     */
    private function captureEnv(): array
    {
        $keys = [
            'INTERNAL_CHAT_LLM',
            'OPENAI_API_KEY',
            'OPENAI_BASE_URL',
            'OPENAI_MODEL',
            'ANTHROPIC_API_KEY',
            'ANTHROPIC_MODEL',
            'OLLAMA_URL',
            'OLLAMA_MODEL',
        ];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = (string) ($_ENV[$key] ?? '');
        }

        return $out;
    }
}
