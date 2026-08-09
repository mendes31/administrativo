<?php

declare(strict_types=1);

namespace App\adms\Models\Services\InternalChat;

/**
 * Catálogo estático das tools built-in exibidas na administração do Assistente MCP.
 */
final class McpChatToolsAdminCatalog
{
    /**
     * @return list<array{tool: string, name: string, description: string, examples: list<string>}>
     */
    public static function builtinTools(): array
    {
        return [
            [
                'tool' => 'rh.count_active',
                'name' => 'Colaboradores ativos',
                'description' => 'Headcount ativo (opcionalmente por departamento).',
                'examples' => ['quantos colaboradores ativos?', 'ativos na TI'],
            ],
            [
                'tool' => 'rh.count_inactive',
                'name' => 'Colaboradores inativos',
                'description' => 'Inativos ou com data de desligamento.',
                'examples' => ['quantos inativos?', 'inativos'],
            ],
            [
                'tool' => 'rh.count_terminated_in_month',
                'name' => 'Desligados no mês',
                'description' => 'Conta por data_desligamento no mês/ano (ex.: janeiro).',
                'examples' => ['inativos em janeiro', 'desligados em março de 2026'],
            ],
            [
                'tool' => 'rh.count_blocked',
                'name' => 'Usuários bloqueados',
                'description' => 'Contas bloqueadas no Portal.',
                'examples' => ['quantos bloqueados?'],
            ],
            [
                'tool' => 'rh.count_active_by_department',
                'name' => 'Ativos por departamento',
                'description' => 'Resumo + gráfico de headcount por depto.',
                'examples' => ['headcount por departamento'],
            ],
            [
                'tool' => 'report.list',
                'name' => 'Listar relatórios do chat',
                'description' => 'Catálogo dos relatórios com «Disponível no chat».',
                'examples' => ['quais relatórios no chat?'],
            ],
            [
                'tool' => 'report.run',
                'name' => 'Executar relatório',
                'description' => 'Roda um relatório dinâmico liberado para o chat (com ACL).',
                'examples' => ['relatório [nome ou tool]'],
            ],
        ];
    }
}
