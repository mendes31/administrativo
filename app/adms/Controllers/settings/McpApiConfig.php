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
            'ollama_models' => $this->fetchOllamaModels(),
            'ollama_url' => rtrim((string) ($_ENV['OLLAMA_URL'] ?? ''), '/'),
            'ollama_env_model' => trim((string) ($_ENV['OLLAMA_MODEL'] ?? 'llama3.2')),
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

    /**
     * Lista modelos instalados no Ollama (GET /api/tags).
     *
     * @return list<string>
     */
    private function fetchOllamaModels(): array
    {
        $base = rtrim((string) ($_ENV['OLLAMA_URL'] ?? ''), '/');
        if ($base === '') {
            return [];
        }

        $ch = curl_init($base . '/api/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        $models = [];
        foreach (($decoded['models'] ?? []) as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name !== '') {
                $models[] = $name;
            }
        }
        sort($models, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_unique($models));
    }
}
