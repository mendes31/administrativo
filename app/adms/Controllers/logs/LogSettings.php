<?php

namespace App\adms\Controllers\logs;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsLogSettingsRepository;
use App\adms\Views\Services\LoadViewService;

class LogSettings
{
    public function index(): void
    {
        $repo = new AdmsLogSettingsRepository();
        $settings = $repo->getSettings();

        $data = [
            'title_head' => 'Configurações de Logs',
            'menu' => 'log-settings',
            'buttonPermission' => ['LogSettings'],
            'log_settings' => $settings,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_log_settings'),
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/logs/settings', $data);
        $loadView->loadView();
    }
}

