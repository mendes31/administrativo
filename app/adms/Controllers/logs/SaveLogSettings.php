<?php

namespace App\adms\Controllers\logs;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsLogSettingsRepository;

class SaveLogSettings
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_log_settings', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        $repo = new AdmsLogSettingsRepository();
        $saved = $repo->saveSettings([
            'session_debug_logs' => isset($_POST['session_debug_logs']) ? 1 : 0,
        ]);

        if ($saved) {
            $_SESSION['msg'] = 'Configurações de logs salvas com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível salvar as configurações de logs.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
        exit;
    }
}

