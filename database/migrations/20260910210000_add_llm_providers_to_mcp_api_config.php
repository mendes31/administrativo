<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Provedores de IA (Groq, Gemini, OpenAI, Anthropic, Ollama) na tela Assistente MCP.
 */
final class AddLlmProvidersToMcpApiConfig extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_mcp_api_config')) {
            return;
        }

        $table = $this->table('adms_mcp_api_config');
        $cols = [
            'llm_provider' => [
                'limit' => 20,
                'null' => false,
                'default' => 'auto',
                'after' => 'ollama_models_fallback',
                'comment' => 'auto|groq|gemini|openai|anthropic|ollama',
            ],
            'llm_groq_api_key' => [
                'limit' => 512,
                'null' => true,
                'default' => null,
                'after' => 'llm_provider',
                'comment' => 'Chave Groq (gsk_...). Vazio = manter / .env.',
            ],
            'llm_groq_model' => [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'llm_groq_api_key',
            ],
            'llm_gemini_api_key' => [
                'limit' => 512,
                'null' => true,
                'default' => null,
                'after' => 'llm_groq_model',
            ],
            'llm_gemini_model' => [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'llm_gemini_api_key',
            ],
            'llm_openai_api_key' => [
                'limit' => 512,
                'null' => true,
                'default' => null,
                'after' => 'llm_gemini_model',
            ],
            'llm_openai_base_url' => [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'llm_openai_api_key',
            ],
            'llm_openai_model' => [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'llm_openai_base_url',
            ],
            'llm_anthropic_api_key' => [
                'limit' => 512,
                'null' => true,
                'default' => null,
                'after' => 'llm_openai_model',
            ],
            'llm_anthropic_model' => [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'llm_anthropic_api_key',
            ],
            'llm_ollama_url' => [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'llm_anthropic_model',
                'comment' => 'URL do Ollama. Vazio = OLLAMA_URL do .env.',
            ],
        ];

        foreach ($cols as $name => $opts) {
            if (!$table->hasColumn($name)) {
                $table->addColumn($name, 'string', $opts);
            }
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_mcp_api_config')) {
            return;
        }

        $table = $this->table('adms_mcp_api_config');
        $names = [
            'llm_ollama_url',
            'llm_anthropic_model',
            'llm_anthropic_api_key',
            'llm_openai_model',
            'llm_openai_base_url',
            'llm_openai_api_key',
            'llm_gemini_model',
            'llm_gemini_api_key',
            'llm_groq_model',
            'llm_groq_api_key',
            'llm_provider',
        ];
        foreach ($names as $name) {
            if ($table->hasColumn($name)) {
                $table->removeColumn($name);
            }
        }
        $table->update();
    }
}
