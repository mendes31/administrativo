<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class PushConfig
{
    public function index(): void
    {
        $repo = new AdmsPushConfigRepository();
        $config = $repo->getConfig();

        $data = [
            'title_head' => 'Configuração Push (PWA)',
            'menu' => 'push-config',
            'buttonPermission' => ['PushConfig', 'SavePushConfig', 'GeneratePushVapidKeys', 'TestPushNotification'],
            'push_config' => $config,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_push_config'),
            'csrf_generate_token' => CSRFHelper::generateCSRFToken('form_push_vapid_generate'),
            'csrf_test_token' => CSRFHelper::generateCSRFToken('form_push_test'),
            'csrf_push_subscribe' => CSRFHelper::generateCSRFToken('form_push_subscribe'),
        ];

        $cfgId = (int) ($config['id'] ?? 0);
        if ($cfgId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'push-config';
            $data['log_resumo'] = LogResumoService::getResumo('adms_push_config', $cfgId, $returnUrl);
        }

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/pushConfig', $data);
        $loadView->loadView();
    }
}
