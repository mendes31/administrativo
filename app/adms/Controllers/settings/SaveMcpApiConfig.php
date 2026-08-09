<?php

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\NavbarLayoutCacheHelper;
use App\adms\Models\Repository\AdmsMcpApiConfigRepository;

class SaveMcpApiConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_mcp_api_config', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        $baseUrl = trim($_POST['base_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $isLocalInternal = in_array(strtolower($baseUrl), ['local:internal', 'local://internal'], true);
        if ($baseUrl === '' || (!$isLocalInternal && !filter_var($baseUrl, FILTER_VALIDATE_URL))) {
            $_SESSION['msg'] = 'Informe uma URL válida (https://...) ou use local:internal para o piloto RH no Portal.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        $normalizedBaseUrl = $isLocalInternal ? 'local:internal' : rtrim($baseUrl, '/');

        $repo = new AdmsMcpApiConfigRepository();
        $saved = $repo->saveConfig([
            'base_url' => $normalizedBaseUrl,
            'is_active' => $isActive,
        ]);

        if ($saved) {
            NavbarLayoutCacheHelper::clear();
            $_SESSION['msg'] = 'Configurações da API MCP salvas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar as configurações da API MCP.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
        exit;
    }
}

