<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsMcpApiConfigRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\InternalChat\McpChatToolsAdminCatalog;
use App\adms\Models\Services\LogResumoService;

class McpApiConfig
{
    public function index(): void
    {
        $repo = new AdmsMcpApiConfigRepository();
        $config = $repo->getConfig();

        $tab = strtolower(trim((string) ($_GET['tab'] ?? 'conexao')));
        if (!in_array($tab, ['conexao', 'tools'], true)) {
            $tab = 'conexao';
        }

        $data = [
            'title_head' => 'Assistente MCP',
            'menu' => 'mcp-api-config',
            'buttonPermission' => ['McpApiConfig', 'ListMcpChatTools', 'SaveMcpChatTool'],
            'mcp_api_config' => $config,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_mcp_api_config'),
            'csrf_token_tools' => CSRFHelper::generateCSRFToken('form_mcp_chat_tool'),
            'active_tab' => $tab,
            'builtin_tools' => McpChatToolsAdminCatalog::builtinTools(),
            'chat_reports' => (new DynamicReportsRepository())->getReportsForChatAdmin(),
        ];
        $cfgId = (int) ($config['id'] ?? 0);
        if ($cfgId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'mcp-api-config';
            $data['log_resumo'] = LogResumoService::getResumo('adms_mcp_api_config', $cfgId, $returnUrl);
        }

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/mcpApiConfig', $data);
        $loadView->loadView();
    }
}
