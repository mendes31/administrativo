<?php

namespace App\adms\Controllers\settings;

use App\adms\Helpers\SendWhatsAppService;

/**
 * Testar Configuração de WhatsApp
 */
class TestWhatsAppConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'whatsapp-config');
            exit;
        }

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

