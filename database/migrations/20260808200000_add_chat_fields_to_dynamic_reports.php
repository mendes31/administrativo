<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Metadados para expor relatórios dinâmicos no Assistente MCP (tool report.run).
 */
final class AddChatFieldsToDynamicReports extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_dynamic_reports')) {
            return;
        }

        $table = $this->table('adms_dynamic_reports');

        if (!$table->hasColumn('chat_enabled')) {
            $table->addColumn('chat_enabled', 'boolean', [
                'default' => 0,
                'null' => false,
                'after' => 'is_active',
                'comment' => 'Disponível no Assistente MCP',
            ]);
        }
        if (!$table->hasColumn('chat_tool_name')) {
            $table->addColumn('chat_tool_name', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
                'after' => 'chat_enabled',
                'comment' => 'Identificador estável da tool (ex.: headcount_ti)',
            ]);
        }
        if (!$table->hasColumn('chat_description')) {
            $table->addColumn('chat_description', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'chat_tool_name',
                'comment' => 'Descrição para a IA / ajuda no chat',
            ]);
        }
        if (!$table->hasColumn('chat_example_prompts')) {
            $table->addColumn('chat_example_prompts', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'chat_description',
                'comment' => 'JSON: exemplos de perguntas',
            ]);
        }

        $table->update();

        if (!$table->hasIndex(['chat_enabled'])) {
            $table->addIndex(['chat_enabled'], ['name' => 'idx_dynamic_reports_chat_enabled'])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_dynamic_reports')) {
            return;
        }

        $table = $this->table('adms_dynamic_reports');
        foreach (['chat_example_prompts', 'chat_description', 'chat_tool_name', 'chat_enabled'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
