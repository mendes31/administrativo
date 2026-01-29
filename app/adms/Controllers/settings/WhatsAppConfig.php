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
        // Log em arquivo para diagnóstico (funciona em produção)
        $logFile = __DIR__ . '/../../../logs/whatsapp_config_debug.log';
        $log = function($message) use ($logFile) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
            error_log($message); // Também loga no error_log padrão
        };
        
        $log("=== WHATSAPP CONFIG INDEX INICIO ===");
        $log("Session user_id: " . ($_SESSION['user_id'] ?? 'não definido'));
        $log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'não definido'));
        
        try {
            $repo = new AdmsWhatsAppConfigRepository();
            $log("Repository instanciado com sucesso");
            
            // Tentar buscar configuração com tratamento de erro
            $whatsappConfig = [];
            try {
                $whatsappConfig = $repo->getConfig();
                $log("Config buscada: " . json_encode($whatsappConfig));
            } catch (\Exception $e) {
                $log("Erro ao buscar config WhatsApp: " . $e->getMessage());
                $log("Stack trace: " . $e->getTraceAsString());
                // Se a tabela não existir, continuar com array vazio
                $whatsappConfig = [];
            }
            
            $data = [
                'title_head' => 'Configuração de WhatsApp',
                'menu' => 'whatsapp-config',
                'buttonPermission' => ['WhatsAppConfig'],
                'whatsapp_config' => $whatsappConfig,
            ];
            
            $log("Dados preparados, chamando PageLayoutService");
            
            $pageLayout = new PageLayoutService();
            $data = array_merge($data, $pageLayout->configurePageElements($data));
            
            $log("PageLayoutService executado, chamando LoadViewService");
            $log("View path: adms/Views/settings/whatsappConfig");
            
            $loadView = new LoadViewService('adms/Views/settings/whatsappConfig', $data);
            $loadView->loadView();
            
            $log("=== WHATSAPP CONFIG INDEX SUCESSO ===");
        } catch (\Throwable $e) {
            // Log do erro completo
            $log("=== ERRO FATAL em WhatsAppConfig::index() ===");
            $log("Mensagem: " . $e->getMessage());
            $log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            $log("Stack Trace: " . $e->getTraceAsString());
            
            // Mostrar erro amigável
            $_SESSION['msg'] = 'Erro ao carregar página de configuração WhatsApp. Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            
            // Tentar redirecionar para dashboard
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }
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

