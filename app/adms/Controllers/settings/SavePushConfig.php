<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;

class SavePushConfig
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_config', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $subject = trim((string) ($_POST['vapid_subject'] ?? ''));
        if ($subject === '') {
            $_SESSION['msg'] = 'Informe o subject VAPID (mailto: ou URL).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        if (!str_starts_with($subject, 'mailto:') && !filter_var($subject, FILTER_VALIDATE_URL)) {
            $_SESSION['msg'] = 'Subject VAPID inválido. Use mailto:email@dominio ou uma URL https://';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $repo = new AdmsPushConfigRepository();
        $saved = $repo->saveSettings([
            'vapid_subject' => $subject,
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        ]);

        if ($saved) {
            $_SESSION['msg'] = 'Configuração push salva com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar configuração push.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
        exit;
    }
}
