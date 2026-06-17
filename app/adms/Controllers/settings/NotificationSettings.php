<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsNotificationSettingsRepository;
use App\adms\Views\Services\LoadViewService;

class NotificationSettings
{
    public function index(): void
    {
        $repo = new AdmsNotificationSettingsRepository();
        $groups = $repo->getGroupedForForm();
        $enabledMap = $repo->getEnabledMap();
        $anyEnabled = in_array(true, $enabledMap, true);

        $data = [
            'title_head' => 'Configurações de Notificações',
            'menu' => 'notification-settings',
            'buttonPermission' => ['NotificationSettings'],
            'notification_groups' => $groups,
            'any_enabled' => $anyEnabled,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_notification_settings'),
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        (new LoadViewService('adms/Views/settings/notification_settings', $data))->loadView();
    }
}
