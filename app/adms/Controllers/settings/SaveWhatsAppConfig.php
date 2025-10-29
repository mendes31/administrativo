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
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

        error_log("=== SAVE WHATSAPP CONFIG ===");
        error_log("POST: " . print_r($_POST, true));

        // Validar CSRF Token
        if (!CSRFHelper::validateCSRFToken('form_whatsapp_config', $_POST['csrf_token'] ?? '')) {
            error_log("❌ CSRF Token inválido!");
            $_SESSION['msg'] = 'Token de segurança inválido. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

        $repo = new AdmsWhatsAppConfigRepository();
        $config = [
            'api_provider' => $_POST['api_provider'] ?? 'Evolution',
            'api_url' => trim($_POST['api_url'] ?? ''),
            'api_key' => trim($_POST['api_key'] ?? ''),
            'api_token' => trim($_POST['api_token'] ?? ''),
            'instance_name' => trim($_POST['instance_name'] ?? ''),
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

        header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
        exit;
    }
}

