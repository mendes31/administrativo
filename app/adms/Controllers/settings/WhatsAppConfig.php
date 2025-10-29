<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\AdmsWhatsAppConfigRepository;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para Configuração de WhatsApp
 *
 * @package App\adms\Controllers\settings
 * @author Rafael Mendes
 */
class WhatsAppConfig
{
    public function index(): void
    {
        $repo = new AdmsWhatsAppConfigRepository();
        $data = [
            'title_head' => 'Configuração de WhatsApp',
            'menu' => 'whatsapp-config',
            'buttonPermission' => ['WhatsAppConfig'],
            'whatsapp_config' => $repo->getConfig(),
        ];
        
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        
        $loadView = new LoadViewService('adms/Views/settings/whatsappConfig', $data);
        $loadView->loadView();
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

        error_log("=== WHATSAPP CONFIG SAVE DEBUG ===");
        error_log("POST Data: " . print_r($_POST, true));

        // Validar CSRF Token
        if (!CSRFHelper::validateCSRFToken('form_whatsapp_config', $_POST['csrf_token'] ?? '')) {
            error_log("❌ CSRF Token inválido!");
            $_SESSION['msg'] = 'Token de segurança inválido. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

        error_log("✅ CSRF Token válido");

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

        error_log("Config Array: " . print_r($config, true));

        $ok = $repo->saveConfig($config);

        error_log("Save Result: " . ($ok ? 'SUCCESS' : 'FAILED'));

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

    public function test(): void
    {
        $testNumber = $_POST['test_number'] ?? '';
        
        if (empty($testNumber)) {
            $_SESSION['msg'] = 'Número de teste não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

        $message = "✅ *Teste de Configuração WhatsApp*\n\n";
        $message .= "Esta mensagem foi enviada automaticamente pelo Sistema Administrativo Tiaraju.\n\n";
        $message .= "📅 Data/Hora: " . date('d/m/Y H:i:s') . "\n\n";
        $message .= "_Se você recebeu esta mensagem, a integração está funcionando corretamente!_";

        $result = SendWhatsAppService::sendMessage($testNumber, $message);

        if ($result['success']) {
            $_SESSION['msg'] = '✅ Mensagem de teste enviada com sucesso para ' . $testNumber . '!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = '❌ Erro ao enviar mensagem: ' . ($result['error'] ?? 'Desconhecido');
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
        exit;
    }
}

