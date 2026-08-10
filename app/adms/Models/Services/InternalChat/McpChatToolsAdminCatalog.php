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
                'tool' => 'rh.lookup_person',
                'name' => 'Ficha do colaborador',
                'description' => 'Busca por nome/username/e-mail: departamento, bloqueio, tempo de empresa, status. ACL: ListUsers ou ViewUser.',
                'examples' => [
                    'departamento do Rafael',
                    'Wladimir está bloqueado?',
                    'quantos anos de empresa o Wladimir possui?',
                ],
            ],
            [
                'tool' => 'rh.count_active',
                'name' => 'Colaboradores ativos',
                'description' => 'Headcount ativo (opcionalmente por departamento). ACL: ListUsers.',
                'examples' => ['quantos colaboradores ativos?', 'ativos na TI'],
            ],
            [
                'tool' => 'rh.count_inactive',
                'name' => 'Colaboradores inativos',
                'description' => 'Inativos ou com data de desligamento (estoque atual). ACL: ListUsers.',
                'examples' => ['quantos inativos?', 'inativos'],
            ],
            [
                'tool' => 'rh.count_terminated',
                'name' => 'Desligados (total)',
                'description' => 'Total com data_desligamento (todos os períodos). ACL: ListUsers.',
                'examples' => ['quantos desligados?', 'usuários desligados'],
            ],
            [
                'tool' => 'rh.list_terminated',
                'name' => 'Lista de desligados',
                'description' => 'Nomes dos desligados (todos, por mês/ano ou departamento). Tabela + Excel/CSV. ACL: ListUsers.',
                'examples' => [
                    'lista de desligados',
                    'lista de desligados em janeiro',
                    'lista desligados Produção',
                    'lista',
                ],
            ],
            [
                'tool' => 'rh.list_hired',
                'name' => 'Lista de contratações',
                'description' => 'Nomes das admissões (pela data_admissao), por mês/ano ou departamento. ACL: ListUsers.',
                'examples' => [
                    'lista de contratações em junho/2026',
                    'lista de admissões 2025',
                    'contratações em março',
                ],
            ],
            [
                'tool' => 'rh.count_hired',
                'name' => 'Quantidade de contratações',
                'description' => 'Conta admissões pela data_admissao (opcional mês/ano). ACL: ListUsers.',
                'examples' => [
                    'quantas contratações?',
                    'quantas admissões em janeiro',
                    'admissões em 2026',
                ],
            ],
            [
                'tool' => 'rh.count_terminated_in_month',
                'name' => 'Desligados no mês',
                'description' => 'Conta por data_desligamento no mês/ano (ex.: janeiro), com breakdown por depto. ACL: ListUsers.',
                'examples' => ['inativos em janeiro', 'desligados em março de 2026'],
            ],
            [
                'tool' => 'rh.count_terminated_by_department',
                'name' => 'Desligados por departamento',
                'description' => 'Agrupa desligados por departamento (opcional: ano). ACL: ListUsers.',
                'examples' => ['desligados por departamento', 'desligados por departamento 2025'],
            ],
            [
                'tool' => 'rh.count_blocked',
                'name' => 'Usuários bloqueados',
                'description' => 'Contas bloqueadas no Portal. ACL: ListUsers.',
                'examples' => ['quantos bloqueados?'],
            ],
            [
                'tool' => 'rh.count_blocked_not_terminated',
                'name' => 'Bloqueados sem desligamento',
                'description' => 'Bloqueados e sem data_desligamento. ACL: ListUsers.',
                'examples' => ['bloqueados sem desligamento', 'bloqueados mas não desligados'],
            ],
            [
                'tool' => 'rh.count_active_by_department',
                'name' => 'Ativos por departamento',
                'description' => 'Resumo + gráfico de headcount por depto. ACL: ListUsers.',
                'examples' => ['headcount por departamento'],
            ],
            [
                'tool' => 'report.list',
                'name' => 'Listar relatórios do chat',
                'description' => 'Catálogo dos relatórios com «Disponível no chat» (ACL fina no próprio relatório).',
                'examples' => ['quais relatórios no chat?'],
            ],
            [
                'tool' => 'report.run',
                'name' => 'Executar relatório',
                'description' => 'Roda um relatório dinâmico liberado para o chat (ACL: partilha + chat_enabled).',
                'examples' => ['relatório [nome ou tool]'],
            ],
        ];
    }
}
