<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Modelo Ollama configurável na tela Assistente MCP (sem editar .env a cada troca).
 */
final class AddOllamaModelToMcpApiConfig extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_mcp_api_config')) {
            return;
        }

        $table = $this->table('adms_mcp_api_config');
        if (!$table->hasColumn('ollama_model')) {
            $table->addColumn('ollama_model', 'string', [
                'limit' => 120,
                'null' => true,
                'default' => null,
                'after' => 'is_active',
                'comment' => 'Modelo Ollama ativo (ex.: qwen:4b). Vazio = OLLAMA_MODEL do .env.',
            ]);
        }
        if (!$table->hasColumn('ollama_models_fallback')) {
            $table->addColumn('ollama_models_fallback', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'ollama_model',
                'comment' => 'Fallbacks separados por vírgula se o modelo principal falhar.',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_mcp_api_config')) {
            return;
        }
        $table = $this->table('adms_mcp_api_config');
        if ($table->hasColumn('ollama_models_fallback')) {
            $table->removeColumn('ollama_models_fallback');
        }
        if ($table->hasColumn('ollama_model')) {
            $table->removeColumn('ollama_model');
        }
        $table->update();
    }
}
