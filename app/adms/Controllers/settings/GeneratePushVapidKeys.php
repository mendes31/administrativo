<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Services\PushNotificationService;

class GeneratePushVapidKeys
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_vapid_generate', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        try {
            $keys = PushNotificationService::generateVapidKeys();
            $repo = new AdmsPushConfigRepository();
            $saved = $repo->saveVapidKeys(
                (string) ($keys['publicKey'] ?? ''),
                (string) ($keys['privateKey'] ?? '')
            );

            if ($saved) {
                $_SESSION['msg'] = 'Par de chaves VAPID gerado e salvo com sucesso.';
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['msg'] = 'Erro ao salvar chaves VAPID.';
                $_SESSION['msg_type'] = 'danger';
            }
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar chaves VAPID: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
        exit;
    }
}
