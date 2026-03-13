<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsMcpApiConfigRepository;

class McpApiConfig
{
    public function index(): void
    {
        $repo = new AdmsMcpApiConfigRepository();
        $config = $repo->getConfig();

        $data = [
            'title_head' => 'Configuração API MCP',
            'menu' => 'mcp-api-config',
            'buttonPermission' => ['McpApiConfig'],
            'mcp_api_config' => $config,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_mcp_api_config'),
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/mcpApiConfig', $data);
        $loadView->loadView();
    }
}

