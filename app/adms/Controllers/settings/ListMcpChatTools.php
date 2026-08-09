<?php

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Catálogo de tools do Assistente MCP (built-in RH + relatórios dinâmicos).
 */
class ListMcpChatTools
{
    public function index(): void
    {
        $repo = new DynamicReportsRepository();
        $reports = $repo->getReportsForChatAdmin();

        $builtin = [
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

        $data = [
            'title_head' => 'Tools do Assistente MCP',
            'menu' => 'list-mcp-chat-tools',
            'buttonPermission' => ['ListMcpChatTools', 'SaveMcpChatTool'],
            'builtin_tools' => $builtin,
            'chat_reports' => $reports,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_mcp_chat_tool'),
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/mcpChatTools', $data);
        $loadView->loadView();
    }
}
