<?php

namespace App\adms\Controllers\crm;

use App\adms\Helpers\SendWhatsAppService;

/**
 * Controller para enviar WhatsApp
 */
class CrmSendWhatsApp
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = "Método não permitido.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-dashboard");
            exit;
        }

        $phoneNumber = $_POST['phone_number'] ?? '';
        $message = $_POST['message'] ?? '';
        $redirectTo = $_POST['redirect_to'] ?? 'crm-dashboard';

        if (empty($phoneNumber) || empty($message)) {
            $_SESSION['msg'] = "Número e mensagem são obrigatórios.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        $result = SendWhatsAppService::sendMessage($phoneNumber, $message);

        if ($result['success']) {
            $_SESSION['msg'] = "✅ Mensagem enviada com sucesso via WhatsApp!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "❌ Erro ao enviar WhatsApp: " . ($result['error'] ?? 'Desconhecido');
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . $redirectTo);
        exit;
    }
}

