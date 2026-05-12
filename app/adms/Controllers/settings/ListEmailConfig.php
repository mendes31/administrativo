<?php
namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\AdmsEmailConfigRepository;
use App\adms\Models\Services\LogResumoService;

class ListEmailConfig
{
    public function index(): void
    {
        $repo = new AdmsEmailConfigRepository();
        $emailConfig = $repo->getConfig();
        $data = [
            'title_head' => 'Configuração de E-mail',
            'menu' => 'email-config',
            'buttonPermission' => ['ListEmailConfig'],
            'email_config' => $emailConfig,
        ];
        $cfgId = (int) ($emailConfig['id'] ?? 0);
        if ($cfgId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'list-email-config';
            $data['log_resumo'] = LogResumoService::getResumo('adms_email_config', $cfgId, $returnUrl);
        }
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        $loadView = new LoadViewService('adms/Views/settings/emailConfig', $data);
        $loadView->loadView();
    }
} 