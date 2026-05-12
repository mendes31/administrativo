<?php

namespace App\adms\Controllers\logs;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsLogSettingsRepository;
use App\adms\Models\Repository\AdmsSlowRequestProfileRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class LogSettings
{
    public function index(): void
    {
        $repo = new AdmsLogSettingsRepository();
        $settings = $repo->getSettings();
        $profiles = [];
        try {
            $profiles = (new AdmsSlowRequestProfileRepository())->listLatest(80);
        } catch (\Throwable) {
            $profiles = [];
        }

        $data = [
            'title_head' => 'Configurações de Logs',
            'menu' => 'log-settings',
            'buttonPermission' => ['LogSettings', 'DownloadSessionDiagnosticLogs', 'ExportSlowRequestProfilesCsv'],
            'log_settings' => $settings,
            'slow_request_profiles' => $profiles,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_log_settings'),
            'csrf_download_session_logs' => CSRFHelper::generateCSRFToken('download_session_logs'),
            'csrf_download_slow_profiles' => CSRFHelper::generateCSRFToken('download_slow_profiles'),
        ];
        $cfgId = (int) ($settings['id'] ?? 0);
        if ($cfgId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'log-settings';
            $data['log_resumo'] = LogResumoService::getResumo('adms_log_settings', $cfgId, $returnUrl);
        }

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/logs/settings', $data);
        $loadView->loadView();
    }
}

