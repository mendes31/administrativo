<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsSapApiConfigRepository;
use App\adms\Helpers\CSRFHelper;

class SaveSapApiConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_sap_api_config', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        $baseUrl = trim($_POST['base_url'] ?? '');
        $apiToken = trim($_POST['api_token'] ?? '');
        $timeout = (int)($_POST['timeout_ms'] ?? 30000);
        $pageSize = (int)($_POST['page_size'] ?? 5000);
        $healthEndpoint = trim($_POST['health_endpoint'] ?? '/health');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $_SESSION['msg'] = 'Informe uma URL base válida para a API.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        if ($apiToken === '') {
            $_SESSION['msg'] = 'Informe o token de autenticação da API.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        if ($timeout < 1000) {
            $timeout = 1000;
        }

        if ($pageSize < 1) {
            $pageSize = 1;
        }

        if ($healthEndpoint === '') {
            $healthEndpoint = '/health';
        }
        $healthEndpoint = '/' . ltrim($healthEndpoint, '/');

        $normalizedBaseUrl = rtrim($baseUrl, '/');

        $repo = new AdmsSapApiConfigRepository();
        $saved = $repo->saveConfig([
            'base_url' => $normalizedBaseUrl,
            'api_token' => $apiToken,
            'timeout_ms' => $timeout,
            'page_size' => $pageSize,
            'health_endpoint' => $healthEndpoint,
            'is_active' => $isActive,
        ]);

        if ($saved) {
            $_SESSION['msg'] = 'Configurações da API SAP salvas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar as configurações da API SAP.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
        exit;
    }
}







