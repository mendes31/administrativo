<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsWhatsAppConfigRepository;
use App\adms\Helpers\CSRFHelper;

/**
 * Salvar Configuração de WhatsApp
 */
class SaveWhatsAppConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
            exit;
        }

        error_log("=== SAVE WHATSAPP CONFIG ===");
        error_log("POST: " . print_r($_POST, true));

        // Validar CSRF Token
        if (!CSRFHelper::validateCSRFToken('form_whatsapp_config', $_POST['csrf_token'] ?? '')) {
            error_log("❌ CSRF Token inválido!");
            $_SESSION['msg'] = 'Token de segurança inválido. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
            exit;
        }

        // Validação de campos obrigatórios
        $apiUrl = trim($_POST['api_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $instanceName = trim($_POST['instance_name'] ?? '');
        $apiProvider = $_POST['api_provider'] ?? 'Evolution';

        if (empty($apiUrl)) {
            $_SESSION['msg'] = 'URL da API é obrigatória.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
            exit;
        }

        if (empty($apiKey)) {
            $_SESSION['msg'] = 'API Key é obrigatória.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
            exit;
        }

        // Para Evolution API, instance_name é obrigatório
        if ($apiProvider === 'Evolution' && empty($instanceName)) {
            $_SESSION['msg'] = 'Nome da Instância é obrigatório para Evolution API.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
            exit;
        }

        $repo = new AdmsWhatsAppConfigRepository();
        $config = [
            'api_provider' => $apiProvider,
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'api_token' => trim($_POST['api_token'] ?? ''),
            'instance_name' => $instanceName,
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'webhook_url' => trim($_POST['webhook_url'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        $ok = $repo->saveConfig($config);

        if ($ok) {
            $_SESSION['msg'] = 'Configurações de WhatsApp salvas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar configurações. Verifique o log de erros.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'whats-app-config');
        exit;
    }
}

