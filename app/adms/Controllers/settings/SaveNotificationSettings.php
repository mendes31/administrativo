<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsNotificationSettingsRepository;
use App\adms\Models\Services\NotificationSettingsRegistry;

class SaveNotificationSettings
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'notification-settings');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_notification_settings', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'notification-settings');
            exit;
        }

        $posted = $_POST['enabled'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }

        $validKeys = NotificationSettingsRegistry::keys();
        $enabledKeys = [];
        foreach ($posted as $key) {
            $key = (string) $key;
            if (in_array($key, $validKeys, true)) {
                $enabledKeys[] = $key;
            }
        }

        $repo = new AdmsNotificationSettingsRepository();
        $saved = $repo->saveEnabledKeys($enabledKeys);

        if ($saved) {
            $_SESSION['msg'] = 'Configurações de notificações salvas com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível salvar todas as configurações.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'notification-settings');
        exit;
    }
}
